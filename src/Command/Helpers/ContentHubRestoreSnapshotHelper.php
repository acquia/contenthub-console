<?php

namespace Acquia\Console\ContentHub\Command\Helpers;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubRestoreSnapshotHelper.
 *
 * @package Acquia\Console\ContentHub\Command\Helpers
 */
class ContentHubRestoreSnapshotHelper extends ContentHubCommandBase implements PlatformBootStrapCommandInterface {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:restore-snapshot-helper';

  /**
   * {@inheritdoc}
   */
  public function getPlatformBootstrapType(): string {
    return 'drupal8';
  }

  /**
   * {@inheritDoc}
   */
  protected function configure() {
    $this->setDescription('Acquia Content Hub restore snapshots helper.')
      ->setHidden(TRUE)
      ->addOption('name', 'na', InputOption::VALUE_REQUIRED, 'Snapshot name');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $name = $input->getOption('name');
    $restore_snapshot = $this->achClientService->restoreSnapshot($name);
    return $restore_snapshot['success'] ? 0 : 1;
  }

}
