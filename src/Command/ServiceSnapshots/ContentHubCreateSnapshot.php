<?php

namespace Acquia\Console\ContentHub\Command\ServiceSnapshots;

use Acquia\Console\ContentHub\Client\ContentHubClientFactory;
use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use Acquia\Console\Helpers\Command\PlatformCmdOutputFormatterTrait;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubCreateSnapshot.
 *
 * @package Acquia\Console\ContentHub\Command\ServiceSnapshots
 */
class ContentHubCreateSnapshot extends ContentHubCommandBase implements PlatformBootStrapCommandInterface {

  use PlatformCmdOutputFormatterTrait;

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

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Create Acquia Content Hub snapshots.')
      ->setAliases(['ach-cs'])
      ->setHidden(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $snapshot = $this->achClientService->createSnapshot();
    if ($snapshot['success']) {
      $output->writeln($this->toJsonSuccess([
        'snapshot_id' => $snapshot['data'],
        'module_version' => $this->drupalServiceFactory->getModuleVersion(),
      ]));
      return 0;
    }

    $output->writeln($this->toJsonError(
      "<error>Something went wrong during snapshot creation: {$snapshot['error']['message']}</error>")
    );

    return 1;
  }

}
