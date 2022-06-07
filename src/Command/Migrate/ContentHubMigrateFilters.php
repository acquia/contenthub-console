<?php

namespace Acquia\Console\ContentHub\Command\Migrate;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use Acquia\Console\Helpers\PlatformCommandExecutioner;
use Acquia\Console\ContentHub\Command\Helpers\DrushWrapper;
use Acquia\Console\Helpers\Command\PlatformCmdOutputFormatterTrait;
use Acquia\Console\ContentHub\Exception\ContentHubVersionException;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use EclipseGc\CommonConsole\Platform\PlatformCommandTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubMigrateFilters.
 *
 * @package Acquia\Console\ContentHub\Command\Migrate
 */
class ContentHubMigrateFilters extends ContentHubCommandBase implements PlatformBootStrapCommandInterface {

  use PlatformCmdOutputFormatterTrait;
  use PlatformCommandTrait;

  /**
   * The platform command executioner.
   *
   * @var \Acquia\Console\Helpers\PlatformCommandExecutioner
   */
  protected $platformCommandExecutioner;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:migrate:filters';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setDescription('Migrates filters from 1.x to 2.x.')
      ->setHidden(TRUE)
      ->setAliases(['ach-mf']);
  }

  /**
   * {@inheritdoc}
   */
  protected function initialize(InputInterface $input, OutputInterface $output) {
    parent::initialize($input, $output);
    if ($this->achClientService->getVersion() !== 2) {
      throw new ContentHubVersionException(2);
    }
  }

  /**
   * ContentHubMigrationFilters constructor.
   *
   * @param \Acquia\Console\Helpers\PlatformCommandExecutioner $platform_command_executioner
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
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $module_handler = \Drupal::moduleHandler();
    if (!$module_handler->moduleExists('acquia_contenthub_subscriber')) {
      return 0;
    }
    $output->writeln('Migrating filters...');
    $drush_options = ['--drush_command' => 'ach-subscriber-upgrade'];
    if ($uri = $input->getOption('uri')) {
      $drush_options['--uri'] = $uri;
    }
    $raw = $this->platformCommandExecutioner->runWithMemoryOutput(DrushWrapper::$defaultName, NULL, $drush_options);
    $exit_code = $raw->getReturnCode();
    $this->getDrushOutput($raw, $output, $exit_code, reset($drush_options));
    if ($exit_code > 0) {
      $output->writeln('<error>Error during filter migration.</error>');
      return 1;
    }

    $output->writeln('<info>Successfully migrated filters.</info>');
    return 0;
  }

}
