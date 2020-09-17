<?php

namespace Acquia\Console\ContentHub\Command\Migrate;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use Acquia\Console\ContentHub\Command\Helpers\PlatformCommandExecutionTrait;
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

  use PlatformCommandExecutionTrait;
  use PlatformCommandTrait;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:migrate:filters';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Migrate filters from 1.x to 2.x.');
    $this->setAliases(['ach-mf']);
  }

  /**
   * {@inheritdoc}
   */
  public function getPlatformBootstrapType(): string {
    return 'drupal8';
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
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $module_handler = \Drupal::moduleHandler();
    if (!$module_handler->moduleExists('acquia_contenthub_subscriber')) {
      return 0;
    }
    $output->writeln('Migrating filters...');
    $res = $this->execDrushWithOutput($output, ['ach-subscriber-upgrade'], $input->getOption('uri') ?: '');
    if ($res !== 0) {
      $output->writeln('<error>Error during filter migration.</error>');
      return 1;
    }

    $output->writeln('<info>Successfully migrated filters.</info>');
    return 0;
  }

}
