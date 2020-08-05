<?php

namespace Acquia\Console\ContentHub\Command\Migrate;

use Acquia\Console\ContentHub\Client\ContentHubClientFactory;
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
    $module_handler = \Drupal::moduleHandler();
    if (!$module_handler->moduleExists('acquia_contenthub_subscriber')) {
      throw new \Exception('Content Hub module is not enabled!');
    }

    if ($this->achClientService->getVersion() !== 2) {
      throw new ContentHubVersionException(2);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $output->writeln('Migrating filters...');
    $res = $this->execDrush(['ach-subscriber-upgrade'], $input->getOption('uri') ?: '');
    if ($res->stderr) {
      $output->writeln(sprintf('<error>Error during filter migration: %s</error>', $res->stderr));
      return 1;
    }

    $output->writeln(sprintf('<info>Successfully migrated filters: %s</info>', $res->stdout));
    return 0;
  }

}
