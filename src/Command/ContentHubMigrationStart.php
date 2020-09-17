<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\Acsf\Command\AcsfCronCreate;
use Acquia\Console\Acsf\Command\AcsfDatabaseBackupCreate;
use Acquia\Console\Acsf\Platform\ACSFPlatform;
use Acquia\Console\Cloud\Command\AcquiaCloudCronCreate;
use Acquia\Console\Cloud\Command\DatabaseBackup\AcquiaCloudDatabaseBackupCreate;
use Acquia\Console\Cloud\Platform\AcquiaCloudPlatform;
use Acquia\Console\ContentHub\Command\Helpers\PlatformCmdOutputFormatterTrait;
use Acquia\Console\ContentHub\Command\Helpers\PlatformCommandExecutionTrait;
use Acquia\Console\ContentHub\Command\Migrate\ContentHubMigrateClientRegistrar;
use Acquia\Console\ContentHub\Command\Migrate\ContentHubMigrateFilters;
use Acquia\Console\ContentHub\Command\Migrate\ContentHubMigrationPrepareUpgrade;
use Acquia\Console\ContentHub\Command\Migrate\ContentHubMigrationPublisherUpgrade;
use EclipseGc\CommonConsole\Platform\PlatformCommandTrait;
use EclipseGc\CommonConsole\PlatformCommandInterface;
use EclipseGc\CommonConsole\PlatformInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Class ContentHubVersion.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubMigrationStart extends Command implements PlatformCommandInterface {

  use PlatformCommandTrait;
  use PlatformCommandExecutionTrait;
  use PlatformCmdOutputFormatterTrait;

  /**
   * {@inheritdoc}
   */
  public static $defaultName = 'ach:migration:start';

  /**
   * {@inheritdoc}
   */
  public function configure() {
    $this->setDescription('Starts Migration Process.');
    $this->setAliases(['ach-mstart'])
      ->addOption(
        'uninstall-modules',
        'um',
        InputOption::VALUE_OPTIONAL,
        'List of modules to uninstall as part of the preparation process.'
      )
      ->addOption(
        'lift-support',
        'ls',
        InputOption::VALUE_NONE,
        'Enable acquia_lift_publisher module.'
      )
      ->addOption(
        'restart',
        'res',
        InputOption::VALUE_NONE,
        'Restart from scratch.'
      );
  }

  /**
   * {@inheritdoc}
   */
  public static function getExpectedPlatformOptions(): array {
    return ['source' => PlatformCommandInterface::ANY_PLATFORM];
  }

  /**
   * ContentHubSubscriptionSet constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The dispatcher service.
   * @param string|null $name
   *   The name of the command.
   */
  public function __construct(EventDispatcherInterface $dispatcher, string $name = NULL) {
    parent::__construct($name);
    $this->dispatcher = $dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $output->writeln('Welcome to the Acquia Content Hub Migration Beta!');
    $output->writeln('This command line utility is designed to help you migrate from Content Hub 1.x to 2.x.');
    $output->writeln('If you encounter any issues, please file a ticket with Acquia Support.');

    // Reading Application and Platform.
    $application = $this->getApplication();
    $platform = $this->getPlatform('source');
    $helper = $this->getHelper('question');

    // Reset stage tracking if restart option is used.
    if ($input->getOption('restart')) {
      $platform->set('acquia.content_hub.migration.stage', 0)->save();
    }

    // Set Content Hub Credentials for Migration.
    $this->setContentHubCredentialsForMigration($application, $platform, $input, $output);
    $stage = $platform->get('acquia.content_hub.migration.stage');

    // Running Content Hub Audit in the current platform.
    $pass = $this->executeStage($stage, 0);
    $this->executeContentHubAuditCommand($platform, $input, $output, $helper, $pass);

    // Check whether we are doing the migration in the same subscription or in a new one.
    $pass = $this->executeStage($stage, 1);
    $this->executeContentHubRegistrar($application, $platform, $input, $output, $helper, $pass);

    // Generate Database Backups.
    $pass = $this->executeStage($stage, 2);
    $this->executeDatabaseBackups($application, $platform, $input, $output, $pass);

    // Prepare Upgrade Command.
    $pass = $this->executeStage($stage, 3);
    $this->executePrepareUpgradeCommand($platform, $input, $output, $helper, $pass);

    // Make sure Content Hub version 2 is deployed in all sites.
    $quest = new ConfirmationQuestion('Please deploy Content Hub 2.x in the environment, including the Diff module and press a key when ready..');
    $helper->ask($input, $output, $quest);
    $version_checker = $application->find(ContentHubVersion::getDefaultName());
    $version_checker->addPlatform($input->getArgument('alias'), $platform);
    $version_checker->run(new ArrayInput(['alias' => $input->getArgument('alias'), '--clear-cache' => true]), $output);

    // Run Publisher Upgrade Command.
    $pass = $this->executeStage($stage, 4);
    $this->executePublisherUpgradeCommand($platform, $input, $output, $helper, $pass);

    // Creates Scheduled Jobs for Publisher/Subscriber Queues.
    $pass = $this->executeStage($stage, 5);
    $this->createContentHubScheduledJobs($application, $platform, $input, $output, $helper, $pass);

    $quest = new ConfirmationQuestion('Please wait until ALL publisher queues have finished exporting data. Press a key when ready.');
    $helper->ask($input, $output, $quest);

    // Run Subscriber Upgrade Command.
    $pass = $this->executeStage($stage, 6);
    $this->executeSubscriberUpgradeCommand($platform, $input, $output, $helper, $pass);

    // Finalize process.
    $output->writeln('Migration Process has been completed successfully. Please check your sites.');
    return 0;
  }

  /**
   * Determines whether to execute a certain stage or to pass to the next one.
   *
   * @param null|int $stage
   *   The stage variable or NULL if no stage is saved.
   * @param int $step
   *   The stage number to check for.
   * @return bool
   *   TRUE if the stage passed is NULL or the stage to check (step) is higher or equal to the passed stage.
   */
  protected function executeStage($stage = NULL, $step = 0) {
    if (!isset($stage)) {
      return TRUE;
    }
    return ($step >= $stage);
  }

  /**
   * Sets Content Hub Credentials for Migration.
   *
   * @param \Symfony\Component\Console\Application $application
   *   The Console Application.
   * @param PlatformInterface $platform
   *   The Platform.
   * @param InputInterface $input
   *   The Input interface.
   * @param OutputInterface $output
   *   The Output interface.
   *
   * @throws \Symfony\Component\Console\Exception\ExceptionInterface
   */
  protected function setContentHubCredentialsForMigration(Application $application, PlatformInterface $platform, InputInterface $input, OutputInterface $output) {
    if (!$platform->get('acquia.content_hub.migration')) {
      $output->writeln('Subscription details are not set for Migration. Setting up Content Hub credentials for Migration...' );
      $subscription_setter = $application->find(ContentHubSubscriptionSet::getDefaultName());
      $subscription_setter->addPlatform($input->getArgument('alias'), $platform);
      $subscription_setter->run(new ArrayInput(['alias' => $input->getArgument('alias'), '--migration' => TRUE]), $output);
    }
  }

  /**
   * Executes the Content Hub Audit Command.
   *
   * @param PlatformInterface $platform
   *   The Platform.
   * @param InputInterface $input
   *   The Input interface.
   * @param OutputInterface $output
   *   The Output interface.
   * @param HelperInterface $helper
   *   The helper Question.
   * @param bool $execute
   *   TRUE if we need to execute this stage, false otherwise.
   */
  protected function executeContentHubAuditCommand(PlatformInterface $platform, InputInterface $input, OutputInterface $output, HelperInterface $helper, bool $execute) {
    $ready = FALSE;
    while (!$ready && $execute) {
      $raw = $this->runWithMemoryOutput(ContentHubAudit::getDefaultName(), [
        '--early-return' => true,
      ]);
      $lines = explode(PHP_EOL, trim($raw));
      foreach ($lines as $line) {
        $this->fromJson($line, $output);
      }
      if ($raw->getReturnCode()) {
        $question = new Question('Please resolve the problems highlighted and make sure the code is up-to-date! Then you can proceed.');
        $helper->ask($input, $output, $question);
        continue;
      }
      $ready = TRUE;
      $platform->set('acquia.content_hub.migration.stage', 1)->save();
    }
  }

  /**
   * Registers the Sites to a new Content Hub Subscription.
   *
   * @param \Symfony\Component\Console\Application $application
   *   The Console Application.
   * @param PlatformInterface $platform
   *   The Platform.
   * @param InputInterface $input
   *   The Input interface.
   * @param OutputInterface $output
   *   The Output interface.
   * @param HelperInterface $helper
   *   The helper Question.
   * @param bool $execute
   *   TRUE if we need to execute this stage, false otherwise.
   *
   * @throws \Symfony\Component\Console\Exception\ExceptionInterface
   */
  protected function executeContentHubRegistrar(Application $application, PlatformInterface $platform, InputInterface $input, OutputInterface $output, HelperInterface $helper, bool $execute) {
    if ($execute) {
      $quest = new ConfirmationQuestion('Are we doing a migration in the same subscription? (Y/N): ');
      $answer = $helper->ask($input, $output, $quest);
      if ($answer != TRUE) {
        // Create clients and update filters in the subscription.
        $client_registrar = $application->find(ContentHubMigrateClientRegistrar::getDefaultName());
        $client_registrar->addPlatform($input->getArgument('alias'), $platform);
        $client_registrar->run(new ArrayInput(['alias' => $input->getArgument('alias')]), $output);
        $platform->set('acquia.content_hub.migration.stage', 2)->save();
      }
    }
  }

  /**
   * Generates Database Backups.
   *
   * @param \Symfony\Component\Console\Application $application
   *   The Console Application.
   * @param PlatformInterface $platform
   *   The Platform.
   * @param InputInterface $input
   *   The Input interface.
   * @param OutputInterface $output
   *   The Output interface.
   * @param bool $execute
   *   TRUE if we need to execute this stage, false otherwise.
   *
   * @throws \Symfony\Component\Console\Exception\ExceptionInterface
   */
  protected function executeDatabaseBackups(Application $application, PlatformInterface $platform, InputInterface $input, OutputInterface $output, bool $execute) {
    $platform_type = $platform->getPlatformId();
    if ($execute) {
      $backup_command = NULL;
      $cmd_input = [
        'alias' => $input->getArgument('alias'),
        '--all' => true,
      ];
      $output->writeln('Starting backups for all sites in the platform.');
      switch ($platform_type) {
        case AcquiaCloudPlatform::PLATFORM_NAME:
          $backup_command = AcquiaCloudDatabaseBackupCreate::getDefaultName();
          $cmd_input['--wait'] = true;
          break;

        case ACSFPlatform::PLATFORM_NAME:
          $backup_command = AcsfDatabaseBackupCreate::getDefaultName();
          break;
      }
      if (empty($backup_command)) {
        $output->writeln('This platform does not support site backups.');
      } else {
        $backup_executor = $application->find($backup_command);
        $backup_executor->addPlatform($input->getArgument('alias'), $platform);
        $status = $backup_executor->run(new ArrayInput($cmd_input), $output);
        if ($status === 0) {
          if ($platform_type == AcquiaCloudPlatform::PLATFORM_NAME) {
            $output->writeln('Site backups completed.');
          }
          else {
            $output->writeln('Site backups queued.');
            $output->writeln('Please ensure the backup jobs have finished before proceeding to the next step.');
          }
        }
      }
      $platform->set('acquia.content_hub.migration.stage', 3)->save();
    }
  }

  /**
   * Executes the Prepare Upgrade Command.
   *
   * @param PlatformInterface $platform
   *   The Platform.
   * @param InputInterface $input
   *   The Input interface.
   * @param OutputInterface $output
   *   The Output interface.
   * @param HelperInterface $helper
   *   The helper Question.
   * @param bool $execute
   *   TRUE if we need to execute this stage, false otherwise.
   */
  protected function executePrepareUpgradeCommand(PlatformInterface $platform, InputInterface $input, OutputInterface $output, HelperInterface $helper, bool $execute) {
    $ready = FALSE;
    // Removes Legacy Content Hub Filter Resource, purges subscription and deletes existing webhooks.
    while (!$ready && $execute) {
      $quest = new ConfirmationQuestion('About to start upgrade preparation. Press a key when ready.');
      $helper->ask($input, $output, $quest);
      $raw = $this->runWithMemoryOutput(ContentHubMigrationPrepareUpgrade::getDefaultName());
      $lines = explode(PHP_EOL, trim($raw));
      foreach ($lines as $line) {
        $this->fromJson($line, $output);
      }
      if ($raw->getReturnCode()) {
        $question = new Question('Please resolve the issues found, then you can proceed.');
        $helper->ask($input, $output, $question);
        continue;
      }
      $ready = TRUE;
      $platform->set('acquia.content_hub.migration.stage', 4)->save();
    }
  }

  /**
   * Executes the Publisher Upgrade Command.
   *
   * @param PlatformInterface $platform
   *   The Platform.
   * @param InputInterface $input
   *   The Input interface.
   * @param OutputInterface $output
   *   The Output interface.
   * @param HelperInterface $helper
   *   The helper Question.
   * @param bool $execute
   *   TRUE if we need to execute this stage, false otherwise.
   */
  protected function executePublisherUpgradeCommand(PlatformInterface $platform, InputInterface $input, OutputInterface $output, HelperInterface $helper, bool $execute) {
    $ready = FALSE;
    // Run database updates and installs acquia_contenthub_publisher module.
    while (!$ready && $execute) {
      $quest = new ConfirmationQuestion('Starting publisher upgrade. Press a key when ready.');
      $helper->ask($input, $output, $quest);
      $raw = $this->runWithMemoryOutput(ContentHubMigrationPublisherUpgrade::getDefaultName());
      $lines = explode(PHP_EOL, trim($raw));
      foreach ($lines as $line) {
        $this->fromJson($line, $output);
      }
      if ($raw->getReturnCode()) {
        $question = new Question('Please resolve the issues found, then you can proceed.');
        $helper->ask($input, $output, $question);
        continue;
      }
      $ready = TRUE;
      $platform->set('acquia.content_hub.migration.stage', 5)->save();
    }
  }

  /**
   * Creates Content Hub Scheduled Jobs for Publisher/Subscriber Queues.
   *
   * @param \Symfony\Component\Console\Application $application
   *   The Console Application.
   * @param PlatformInterface $platform
   *   The Platform.
   * @param InputInterface $input
   *   The Input interface.
   * @param OutputInterface $output
   *   The Output interface.
   * @param HelperInterface $helper
   *   The helper Question.
   * @param bool $execute
   *   TRUE if we need to execute this stage, false otherwise.
   *
   * @throws \Symfony\Component\Console\Exception\ExceptionInterface
   */
  protected function createContentHubScheduledJobs(Application $application, PlatformInterface $platform, InputInterface $input, OutputInterface $output, HelperInterface $helper, bool $execute) {
    $status = 1;
    $platform_type = $platform->getPlatformId();
    while ($status !== 0 && $execute) {
      $quest = new ConfirmationQuestion('We are about to create scheduled jobs to run the queues.. Press a key when ready.');
      $helper->ask($input, $output, $quest);
      switch ($platform_type) {
        case AcquiaCloudPlatform::PLATFORM_NAME:
          $scheduled_jobs = $application->find(AcquiaCloudCronCreate::getDefaultName());
          $cmd_input['--wait'] = true;
          break;

        case ACSFPlatform::PLATFORM_NAME:
          $scheduled_jobs = $application->find(AcsfCronCreate::getDefaultName());
          break;
      }
      if ($scheduled_jobs) {
        $scheduled_jobs->addPlatform($input->getArgument('alias'), $platform);
        $status = $scheduled_jobs->run(new ArrayInput(['alias' => $input->getArgument('alias')]), $output);
      }
      $platform->set('acquia.content_hub.migration.stage', 6)->save();
    }
  }

  /**
   * Executes the Subscriber Upgrade Command.
   *
   * @param PlatformInterface $platform
   *   The Platform.
   * @param InputInterface $input
   *   The Input interface.
   * @param OutputInterface $output
   *   The Output interface.
   * @param HelperInterface $helper
   *   The helper Question.
   * @param bool $execute
   *   TRUE if we need to execute this stage, false otherwise.
   */
  protected function executeSubscriberUpgradeCommand(PlatformInterface $platform, InputInterface $input, OutputInterface $output, HelperInterface $helper, bool $execute) {
    $ready = FALSE;
    // Migrates Filters and adds imported entities to the subscribers' interest list.
    while (!$ready && $execute) {
      $quest = new ConfirmationQuestion('Starting subscriber upgrade. Press a key when ready.');
      $helper->ask($input, $output, $quest);
      $raw = $this->runWithMemoryOutput(ContentHubMigrateFilters::getDefaultName());
      $lines = explode(PHP_EOL, trim($raw));
      foreach ($lines as $line) {
        $this->fromJson($line, $output);
      }
      if ($raw->getReturnCode()) {
        $question = new Question('Please resolve the issues found, then you can proceed.');
        $helper->ask($input, $output, $question);
        continue;
      }
      $ready = TRUE;
      $platform->set('acquia.content_hub.migration.stage', 7)->save();
    }
  }

}

