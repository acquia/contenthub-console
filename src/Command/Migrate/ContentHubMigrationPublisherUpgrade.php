<?php

namespace Acquia\Console\ContentHub\Command\Migrate;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use Acquia\Console\ContentHub\Client\PlatformCommandExecutioner;
use Acquia\Console\ContentHub\Command\ContentHubModuleTrait;
use Acquia\Console\ContentHub\Command\Helpers\DrushWrapper;
use Acquia\Console\ContentHub\Command\Helpers\PlatformCmdOutputFormatterTrait;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubMigrationPublisherUpgrade.
 *
 * @package Acquia\Console\ContentHub\Command\Migration
 */
class ContentHubMigrationPublisherUpgrade extends ContentHubCommandBase implements PlatformBootStrapCommandInterface {

  use PlatformCmdOutputFormatterTrait;
  use ContentHubModuleTrait;

  /**
   * The platform command executioner.
   *
   * @var \Acquia\Console\ContentHub\Client\PlatformCommandExecutioner
   */
  protected $platformCommandExecutioner;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:migrate:publisher-upgrade';

  /**
   * {@inheritDoc}
   */
  protected function configure() {
    $this->setDescription('Runs database updates and upgrades publisher sites to Content Hub 2.x.')
      ->setHidden(TRUE)
      ->setAliases(['ach-mu'])
      ->addOption(
        'lift-support',
        'ls',
        InputOption::VALUE_NONE,
        'Enable acquia_lift_publisher module.'
      );
  }

  /**
   * ContentHubMigrationPublisherUpgrade constructor.
   *
   * @param \Acquia\Console\ContentHub\Client\PlatformCommandExecutioner $platform_command_executioner
   *   The platform command executioner.
   * @param string|null $name
   *   The name of the command.
   */
  public function __construct(PlatformCommandExecutioner $platform_command_executioner, string $name = NULL) {
    parent::__construct($name);
    $this->platformCommandExecutioner = $platform_command_executioner;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $output->writeln('Initiating module upgrade process...');
    $drush_options = ['--drush_command' => 'cr'];
    if ($uri = $input->getOption('uri')) {
     $drush_options['--uri'] = $uri;
    }
    $this->executeDrushCommand($drush_options, $output);
    $this->updateDatabases($input, $output);
    $this->executeDrushCommand($drush_options, $output);
    if ($input->getOption('lift-support')) {
      $this->enableAcquiaLiftPublisherModule($output);
      $this->setAcquiaLiftCdfVersion();
    }
    $this->upgradePublishers($output, $uri);
  }

  /**
   * Runs database schema updates.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output stream to write to.
   *
   * @throws \Exception
   */
  protected function updateDatabases(InputInterface $input, OutputInterface $output): void {
    $output->writeln('Running database updates...');
    $drush_options = ['--drush_command' => 'updatedb', '--drush_args' => ['-y']];
    if ($uri = $input->getOption('uri')) {
      $drush_options['--uri'] = $uri;
    }
    $this->executeDrushCommand($drush_options, $output);
  }

  /**
   * Enables acquia_lift_publisher module.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output stream to write to.
   */
  protected function enableAcquiaLiftPublisherModule(OutputInterface $output): void {
    $output->writeln('Installing acquia_lift_publisher module...');
    try {
      \Drupal::service('module_installer')->install(['acquia_lift_publisher']);
    }
    catch (\Exception $e) {
      $output->writeln("<error>Module could not be installed. {$e->getMessage()}</error>");
      return;
    }
  }

  /**
   * Sets cdf_version settings for Acquia Lift module.
   */
  protected function setAcquiaLiftCdfVersion(): void {
    $config = \Drupal::configFactory()->getEditable('acquia_lift.settings');
    $config->set('advanced.cdf_version', 2)->save();
  }

  /**
   * Identifies if a site is a publisher and runs the publisher upgrade command.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output stream to write to.
   * @param string $uri
   *   The uri of the site.
   *
   * @return int
   *   The return code.
   *
   * @throws \Exception
   */
  protected function upgradePublishers(OutputInterface $output, $uri = ''): int {
    if ($this->isPublisher($this->drupalServiceFactory)) {
      $output->writeln('The site is a publisher, enabling acquia_contenthub_publisher and acquia_contenthub_curation module...');
      \Drupal::service('module_installer')->install(['acquia_contenthub_publisher']);
      \Drupal::service('module_installer')->install(['acquia_contenthub_curation']);
    }

    // It is possible that it was already enabled, therefore we need to make
    // sure if that is the case.
    if (\Drupal::moduleHandler()->moduleExists('acquia_contenthub_publisher')) {
      $output->writeln('Running publisher upgrades...');
      $drush_options = ['--drush_command' => 'ach-publisher-upgrade'];
      if ($uri) {
        $drush_options['--uri'] = $uri;
      }
      $exit_code = $this->executeDrushCommand($drush_options, $output);
      if ($exit_code === 0) {
        $output->writeln('Done');
        return 0;
      }
      return 1;
    }
    return 0;
  }

  /**
   * Helper function to execute drush command.
   *
   * @param $drush_options
   *   Drush options with drush command and args.
   * @param $output
   *   Output stream.
   *
   * @return int
   *   Exit code from drush execution.
   */
  private function executeDrushCommand($drush_options, $output) {
    $raw = $this->platformCommandExecutioner->runWithMemoryOutput(DrushWrapper::$defaultName,NULL, $drush_options);
    $exit_code = $raw->getReturnCode();
    $this->getDrushOutput($raw, $output, $exit_code, reset($drush_options));
    return $exit_code;
  }

}
