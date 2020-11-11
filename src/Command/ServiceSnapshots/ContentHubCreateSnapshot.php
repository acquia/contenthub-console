<?php

namespace Acquia\Console\ContentHub\Command\ServiceSnapshots;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubCreateSnapshot.
 *
 * @package Acquia\Console\ContentHub\Command\ServiceSnapshots
 */
class ContentHubCreateSnapshot extends ContentHubCommandBase implements PlatformBootStrapCommandInterface {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:create-snapshot';

  /**
   * {@inheritdoc}
   */
  public function getPlatformBootstrapType(): string {
    return 'drupal8';
  }

  protected function configure() {
    $this->setDescription('Create Acquia Content Hub snapshots.')
      ->setAliases(['ach-cs']);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $snapshot = $this->achClientService->createSnapshot();
    if ($snapshot['success']) {
      $output->writeln(sprintf('<info>Snapshot created successfully: %s</info>', $snapshot['data']));
      return 0;
    }
    $output->writeln(sprintf('<error>Could not create snapshot: %s</error>', $snapshot['error']['message']));
    return 1;
  }

}
