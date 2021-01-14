<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubVerifyPublisherQueue.
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
    $this->setDescription('Checks whether the publisher queue is empty and there are no queued entities in the publisher tracking table.');
    $this->setAliases(['ach-apq']);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $module_version = $this->drupalServiceFactory->getModuleVersion();
    if (!$module_version) {
      return 0;
    }
    if (!$this->isPublisher($this->drupalServiceFactory)) {
      return 0;
    }
    $publisher_queue_service = 'acquia_contenthub_publisher.acquia_contenthub_export_queue';
    if ($module_version === 1) {
      $publisher_queue_service = 'acquia_contenthub.acquia_contenthub_export_queue';
    }
    $export_queue = $this->drupalServiceFactory->getDrupalService($publisher_queue_service);
    $count = $export_queue->getQueueCount();
    if (!empty($count)) {
      $output->writeln(sprintf('<warning>Publisher queues are not empty. Current number of queue items: %u</warning>', $count));
      return 1;
    }
    return $this->verifyExportedEntitiesStatus($output, $module_version);

  }

  /**
   * Verify that all exported entities are marked with "confirmed" status or not.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The Output interface.
   * @param int $module_version
   *   The module version.
   *
   * @return int
   *   The status.
   *
   * @throws \Exception
   */
  protected function verifyExportedEntitiesStatus(OutputInterface $output, int $module_version): int {

    $tracking_table = 'acquia_contenthub_publisher_export_tracking';
    $field = 'status';
    $status = 'queued';
    if ($module_version === 1) {
      $tracking_table = 'acquia_contenthub_entities_tracking';
      $field = 'status_export';
      $status = 'QUEUED';
    }
    $database = $this->drupalServiceFactory->getDrupalService('database');
    $query = $database
      ->select($tracking_table, 'exp')
      ->fields('exp', [$field])
      ->condition("exp.{$field}", $status);
    if ($module_version == 1) {
      $query->condition('exp.status_import', '');
    }
    $query->execute();
    $query->allowRowCount = TRUE;
    $count = $query->rowCount();

    if ($count) {
      $verb = ($count == 1) ? 'is' : 'are';
      $output->writeln(sprintf("<warning>%u entities in the tracking table %s marked as '%s'.</warning>", $count, $verb, $status));
      return 1;
    }
    return 0;
  }

}
