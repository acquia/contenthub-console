<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\Acsf\Command\AcsfCronCreate;
use Acquia\Console\Acsf\Command\AcsfDatabaseBackupCreate;
use Acquia\Console\Acsf\Platform\ACSFPlatform;
use Acquia\Console\Cloud\Command\AcquiaCloudCronCreate;
use Acquia\Console\Cloud\Command\AcquiaCloudCronCreateMultiSite;
use Acquia\Console\Cloud\Command\DatabaseBackup\AcquiaCloudDatabaseBackupCreate;
use Acquia\Console\Cloud\Command\DatabaseBackup\AcquiaCloudMultisiteDatabaseBackupCreate;
use Acquia\Console\Cloud\Platform\AcquiaCloudMultiSitePlatform;
use Acquia\Console\Cloud\Platform\AcquiaCloudPlatform;
use Acquia\Console\ContentHub\Client\PlatformCommandExecutioner;
use Acquia\Console\ContentHub\Command\Helpers\PlatformCmdOutputFormatterTrait;
use Acquia\Console\ContentHub\Command\Migrate\ContentHubMigrateEnableUnsubscribe;
use Acquia\Console\ContentHub\Command\Migrate\ContentHubMigrateFilters;
use Acquia\Console\ContentHub\Command\Migrate\ContentHubMigrationPrepareUpgrade;
use Acquia\Console\ContentHub\Command\Migrate\ContentHubMigrationPublisherUpgrade;
use Acquia\Console\ContentHub\Command\Migrate\ContentHubMigrationPurgeAndDeleteWebhooks;
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
class ContentHubUpgradeStart extends Command implements PlatformCommandInterface {

  use PlatformCommandTrait;
  use PlatformCmdOutputFormatterTrait;

  /**
   * The platform command executioner.
   *
   * @var \Acquia\Console\ContentHub\Client\PlatformCommandExecutioner
   */
  protected $platformCommandExecutioner;

  /**
   * {@inheritdoc}
   */
  public static $defaultName = 'ach:upgrade:start';

  /**
   * {@inheritdoc}
   */
  public function configure() {
    $this->setDescription('Starts the Upgrade Process from Acquia Content Hub 1.x to 2.x.');
    $this->setAliases(['ach-ustart'])
      ->addOption(
        'uninstall-modules',
        'um',
        InputOption::VALUE_OPTIONAL,
        'List of modules to uninstall as part of the preparation process.'
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
   * ContentHubUpgradeStart constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The dispatcher service.
   * @param \Acquia\Console\ContentHub\Client\PlatformCommandExecutioner $platform_command_executioner
   *   The platform command executioner.
   * @param string|null $name
   *   The name of the command.
   */
  public function __construct(EventDispatcherInterface $dispatcher, PlatformCommandExecutioner $platform_command_executioner, string $name = NULL) {
    parent::__construct($name);
    $this->dispatcher = $dispatcher;
    $this->platformCommandExecutioner = $platform_command_executioner;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $output->writeln('Welcome to the Acquia Content Hub Upgrade Beta!');
    $output->writeln('This command line utility is designed to help you upgrade from Content Hub 1.x to 2.x.');
    $output->writeln('If you encounter any issues, please file a ticket with Acquia Support.');

    // Reading Application and Platform.
    $application = $this->getApplication();
    $platform = $this->getPlatform('source');
    $helper = $this->getHelper('question');

    // Reset stage tracking if restart option is used.
    if ($input->getOption('restart')) {
      $platform->set('acquia.content_hub.upgrade.stage', 0)->save();
    }

    // Set Content Hub Credentials for Upgrade.
    $this->setContentHubCredentialsForMigration($application, $platform, $input, $output);
    $this->setAcquiaLiftUsage($platform, $input, $output);
    $stage = $platform->get('acquia.content_hub.upgrade.stage');

    // Running Content Hub Audit in the current platform.
    $pass = $this->executeStage($stage, 0);
    $this->executeContentHubAuditCommand($platform, $input, $output, $helper, $pass);

    // Generate Database Backups.
    $pass = $this->executeStage($stage, 1);
    $this->executeDatabaseBackups($application, $platform, $input, $output, $pass);

    // Purge Subscription and delete Webhooks.
    $pass = $this->executeStage($stage, 2);
    $this->purgeSubscriptionDeleteWebhooks($platform, $input, $output, $helper, $pass);

    // Prepare Upgrade Command.
    $pass = $this->executeStage($stage, 3);
    $this->executePrepareUpgradeCommand($platform, $input, $output, $helper, $pass);

    // Make sure Content Hub version 2 is deployed in all sites.
    $is_lift_customer = $platform->get(ContentHubLiftVersion::ACQUIA_LIFT_USAGE);
    $quest = new ConfirmationQuestion('Please deploy Content Hub 2.x in the environment, including the Diff module and press a key when ready..');
    if ($is_lift_customer) {
      $output->writeln('Please include the up-to-date version of Acquia Lift (8.x-4.x) in the deploy!');
    }
    $helper->ask($input, $output, $quest);
    $this->executeContentHubVersionCheck($application, $platform, $input, $output);

    // Run Publisher Upgrade Command.
    $pass = $this->executeStage($stage, 4);
    $this->executePublisherUpgradeCommand($platform, $input, $output, $helper, $pass);

    // Creates Scheduled Jobs for Publisher/Subscriber Queues.
    $pass = $this->executeStage($stage, 5);
    $this->createContentHubScheduledJobs($application, $platform, $input, $output, $helper, $pass);

    $quest = new ConfirmationQuestion('Please wait until ALL publisher queues have finished exporting data. Press a key when ready.');
    $helper->ask($input, $output, $quest);

    // Run validations on the publisher queues.
    $pass = $this->executeStage($stage, 6);
    $this->executeValidatePublisherQueues($platform, $input, $output, $helper, $pass);

    // Run Subscriber Upgrade Command.
    $pass = $this->executeStage($stage, 7);
    $this->executeSubscriberUpgradeCommand($platform, $input, $output, $helper, $pass);

    // Enable Unsubscribe module if there are imported entities with local changes / auto-update disabled.
    $pass = $this->executeStage($stage, 8);
    $this->executeEnableUnsubscribeCommand($platform, $input, $output, $helper, $pass);

    // Run Validations on the upgraded subscription.
    $pass = $this->executeStage($stage, 9);
    $this->executeValidateSiteWebhooksCommand($platform, $input, $output, $helper, $pass);

    // Validate that default filters are attached to webhooks and all filters have been upgraded.
    $pass = $this->executeStage($stage, 10);
    $this->executeValidateDefaultFiltersCommand($platform, $input, $output, $helper, $pass);

    // Run validations on the interest list diff.
    $pass = $this->executeStage($stage, 11);
    $this->executeValidateInterestListDiff($platform, $input, $output, $helper, $pass);

    // Finalize process.
    $output->writeln('<warning>The Curation module has been enabled on publisher sites. You can manually enable it on subscriber sites if desired.</warning>');
    $output->writeln('<info>Content Hub Upgrade process has been completed successfully. Please check your sites.</info>');
    $output->writeln('<warning>The Diff module is no longer required by Content Hub and may not be required by your application. Please check and remove if applicable.</warning>');
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
   * Sets Content Hub Credentials.
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
    if (!$platform->get('acquia.content_hub')) {
      $output->writeln('Content Hub Subscription details are not set. Setting up Content Hub credentials for the Upgrade process...');
      $subscription_setter = $application->find(ContentHubSubscriptionSet::getDefaultName());
      $subscription_setter->addPlatform($input->getArgument('alias'), $platform);
      $subscription_setter->run(new ArrayInput(['alias' => $input->getArgument('alias')]), $output);
    }
  }

  /**
   * Set information into configuration about Acquia Lift usage.
   *
   * @param \EclipseGc\CommonConsole\PlatformInterface $platform
   *   The Platform.
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The Input interface.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The Output interface.
   */
  protected function setAcquiaLiftUsage(PlatformInterface $platform, InputInterface $input, OutputInterface $output): void {
    $platform->set(ContentHubLiftVersion::ACQUIA_LIFT_USAGE, FALSE);

    $raw = $this->platformCommandExecutioner->runWithMemoryOutput(ContentHubLiftVersion::getDefaultName(), $platform);
    $lines = explode(PHP_EOL, trim($raw));

    foreach ($lines as $line) {
      $data = $this->fromJson($line, $output);
      if (!$data) {
        continue;
      }

      if ($data->configured === TRUE) {
        $platform->set(ContentHubLiftVersion::ACQUIA_LIFT_USAGE, TRUE);
      }
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
      $raw = $this->platformCommandExecutioner->runWithMemoryOutput(ContentHubAudit::getDefaultName(), $platform, [
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
      $platform->set('acquia.content_hub.upgrade.stage', 1)->save();
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

        case AcquiaCloudMultiSitePlatform::PLATFORM_NAME:
          $backup_command = AcquiaCloudMultisiteDatabaseBackupCreate::getDefaultName();
          break;
      }
      if (empty($backup_command)) {
        $output->writeln('<warning>This platform does not support site backups.</warning>');
      } else {
        $backup_executor = $application->find($backup_command);
        $backup_executor->addPlatform($input->getArgument('alias'), $platform);
        $status = $backup_executor->run(new ArrayInput($cmd_input), $output);
        if ($status === 0) {
          if ($platform_type == AcquiaCloudPlatform::PLATFORM_NAME) {
            $output->writeln('<info>Site backups completed.</info>');
          }
          else {
            $output->writeln('Site backups queued.');
            $output->writeln('<warning>Please ensure the backup jobs have finished before proceeding to the next step.</warning>');
          }
        }
      }
      $platform->set('acquia.content_hub.upgrade.stage', 2)->save();
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
   *
   * @return int
   *
   * @throws \Symfony\Component\Console\Exception\ExceptionInterface
   */
  protected function purgeSubscriptionDeleteWebhooks(PlatformInterface $platform, InputInterface $input, OutputInterface $output, HelperInterface $helper, bool $execute) {
    $ready = FALSE;
    while (!$ready && $execute) {
      $sites = $this->getPlatformSites('source');
      if (empty($sites)) {
        $output->writeln('<Error>There are no sites in this platform.</Error>');
        return 1;
      }
      // Getting URL of first site in the platform.
      $site_info = reset($sites);
      $raw = $this->platformCommandExecutioner->runWithMemoryOutput(ContentHubMigrationPurgeAndDeleteWebhooks::getDefaultName(), $platform, [
        '--uri' => $site_info['uri'],
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
      $platform->set('acquia.content_hub.upgrade.stage', 3)->save();
    }
  }

  /**
   * Executes a Content Hub version check and does not let go until the correct version of CH 2.x is deployed.
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
  protected function executeContentHubVersionCheck(Application $application, PlatformInterface $platform, InputInterface $input, OutputInterface $output) {
    $version_checker = $application->find(ContentHubVersion::getDefaultName());
    $version_checker->addPlatform($input->getArgument('alias'), $platform);
    $version_checker->run(new ArrayInput(['alias' => $input->getArgument('alias')]), $output);
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
      $raw = $this->platformCommandExecutioner->runWithMemoryOutput(ContentHubMigrationPrepareUpgrade::getDefaultName(), $platform);
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
      $platform->set('acquia.content_hub.upgrade.stage', 4)->save();
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
      $raw = $this->platformCommandExecutioner->runWithMemoryOutput(ContentHubMigrationPublisherUpgrade::getDefaultName(), $platform, [
        '--lift-support' => $platform->get(ContentHubLiftVersion::ACQUIA_LIFT_USAGE)
      ]);
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
      $platform->set('acquia.content_hub.upgrade.stage', 5)->save();
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

        case AcquiaCloudMultiSitePlatform::PLATFORM_NAME:
          $scheduled_jobs = $application->find(AcquiaCloudCronCreateMultiSite::getDefaultName());
          break;

        case ACSFPlatform::PLATFORM_NAME:
          $scheduled_jobs = $application->find(AcsfCronCreate::getDefaultName());
          break;
      }
      if ($scheduled_jobs) {
        $scheduled_jobs->addPlatform($input->getArgument('alias'), $platform);
        $status = $scheduled_jobs->run(new ArrayInput(['alias' => $input->getArgument('alias')]), $output);
      }
      $platform->set('acquia.content_hub.upgrade.stage', 6)->save();
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
      $raw = $this->platformCommandExecutioner->runWithMemoryOutput(ContentHubMigrateFilters::getDefaultName(), $platform);
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
      $platform->set('acquia.content_hub.upgrade.stage', 8)->save();
    }
  }

  /**
   * Enables the Unsubscribe module if there are imported entities with local changes / auto-update disabled.
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
  protected function executeEnableUnsubscribeCommand(PlatformInterface $platform, InputInterface $input, OutputInterface $output, HelperInterface $helper, bool $execute) {
    $ready = FALSE;
    // Migrates Filters and adds imported entities to the subscribers' interest list.
    while (!$ready && $execute) {
      $quest = new ConfirmationQuestion('The Unsubscribe module will be enabled if there are imported entities with local changes / auto-update disabled. Press a key when ready.');
      $helper->ask($input, $output, $quest);
      $raw = $this->platformCommandExecutioner->runWithMemoryOutput(ContentHubMigrateEnableUnsubscribe::getDefaultName(), $platform);
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
      $platform->set('acquia.content_hub.upgrade.stage', 9)->save();
    }
  }

  /**
   * Validates that the site has a registered webhook in Content Hub.
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
  protected function executeValidateSiteWebhooksCommand(PlatformInterface $platform, InputInterface $input, OutputInterface $output, HelperInterface $helper, bool $execute) {

    $ready = FALSE;
    // Migrates Filters and adds imported entities to the subscribers' interest list.
    while (!$ready && $execute) {
      $output->writeln('Validating site has a registered webhook.');
      $raw = $this->platformCommandExecutioner->runWithMemoryOutput(ContentHubVerifyCurrentSiteWebhook::getDefaultName(), $platform);
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
      $platform->set('acquia.content_hub.upgrade.stage', 10)->save();
    }
  }

  /**
   * Validates default filters and migrations from 1.x filters.
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
  protected function executeValidateDefaultFiltersCommand(PlatformInterface $platform, InputInterface $input, OutputInterface $output, HelperInterface $helper, bool $execute) {

    $ready = FALSE;
    // Migrates Filters and adds imported entities to the subscribers' interest list.
    while (!$ready && $execute) {
      $output->writeln('Validating filters migration...');
      // Getting URL of first site in the platform.
      $sites = $this->getPlatformSites('source');
      $site_info = reset($sites);
      $raw = $this->platformCommandExecutioner->runWithMemoryOutput(ContentHubVerifyWebhooksDefaultFilters::getDefaultName(), $platform, [
        '--uri' => $site_info['uri'],
      ]);
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
      $platform->set('acquia.content_hub.upgrade.stage', 11)->save();
    }
  }

  /**
   * Validates 2.x publisher queues.
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
  protected function executeValidatePublisherQueues(PlatformInterface $platform, InputInterface $input, OutputInterface $output, HelperInterface $helper, bool $execute) {
    $ready = FALSE;
    while (!$ready && $execute) {
      $quest = new ConfirmationQuestion('Validating publisher queues... Press any key to continue');
      $helper->ask($input, $output, $quest);

      $raw = $this->platformCommandExecutioner->runWithMemoryOutput(ContentHubVerifyPublisherQueue::getDefaultName(), $platform);
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
      $platform->set('acquia.content_hub.upgrade.stage', 7)->save();
    }
  }

  /**
   * Validates interest list diff.
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
  protected function executeValidateInterestListDiff(PlatformInterface $platform, InputInterface $input, OutputInterface $output, HelperInterface $helper, bool $execute) {
    $ready = FALSE;
    while (!$ready && $execute) {
      $quest = new ConfirmationQuestion('Validating interest list diff...');
      $helper->ask($input, $output, $quest);

      $raw = $this->platformCommandExecutioner->runWithMemoryOutput(ContentHubInterestListDiff::getDefaultName(), $platform);
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
      $platform->set('acquia.content_hub.upgrade.stage', 12)->save();
    }
  }
}
