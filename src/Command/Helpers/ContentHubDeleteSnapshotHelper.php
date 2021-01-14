<?php

namespace Acquia\Console\ContentHub\Command\Helpers;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubDeleteSnapshotHelper.
 *
 * @package Acquia\Console\ContentHub\Command\Helpers
 */
class ContentHubDeleteSnapshotHelper extends ContentHubCommandBase implements PlatformBootStrapCommandInterface {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:delete-snapshot-helper';

  /**
   * {@inheritdoc}
   */
  public function getPlatformBootstrapType(): string {
    return 'drupal8';
  }

  /**
   *
   */
  protected function configure() {
    $this->setDescription('Acquia Content Hub delete snapshots helper.')
      ->setHidden(TRUE)
      ->addOption('name', 'na', InputOption::VALUE_REQUIRED, 'Snapshot name');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $name = $input->getOption('name');
    $restore_snapshot = $this->achClientService->deleteSnapshot($name);
    return $restore_snapshot['success'] ? 0 : 1;
  }

}
