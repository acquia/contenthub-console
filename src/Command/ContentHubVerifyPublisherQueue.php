<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubVerifyPublisherQueue
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubVerifyPublisherQueue extends ContentHubCommandBase implements PlatformBootStrapCommandInterface {

  use ContentHubModuleTrait;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:audit:publisher-queue';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Checks whether 2.x publisher queue is empty and the tracking tables entities status.');
    $this->setAliases(['ach-apq']);
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
  protected function execute(InputInterface $input, OutputInterface $output): int {
    if (!$this->isPublisher()) {
      return 1;
    }
    $export_queue = \Drupal::service('acquia_contenthub_publisher.acquia_contenthub_export_queue');
    $count = $export_queue->getQueueCount();
    if (!empty($count)) {
      $output->writeln(sprintf('<warning>Publisher queues are not empty. Current number of queue items: %u</warning>', $count));
//      return 1;
    }
    return $this->verifyExportedEntitiesStatus($output);

  }

  /**
   * Verify that all exported entities are marked with "confirmed" status or not.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The Output interface.
   *
   * @return int
   *   The status.
   */
  protected function verifyExportedEntitiesStatus(OutputInterface $output): int {
    $query = \Drupal::database()
      ->select('acquia_contenthub_publisher_export_tracking', 'exp')
      ->fields('exp', ['status'])
      ->condition('exp.status', 'queued')
      ->execute();
    $query->allowRowCount = TRUE;
    $count = $query->rowCount();

    if ($count) {
      $verb = ($count == 1) ? 'is' : 'are';
      $output->writeln(sprintf("<warning>%u entities within the tracking table %s marked as queued.</warning>", $count, $verb));
      return 1;
    }
    return 0;
  }

}
