<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\Helpers\Command\PlatformCmdOutputFormatterTrait;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubQueue.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubQueue extends Command implements PlatformBootStrapCommandInterface {

  use ContentHubModuleTrait;
  use PlatformCmdOutputFormatterTrait;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:queue';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Gather Content Hub Queue information');
    $this->setHidden(TRUE);
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
    if ($this->isModuleEnabled('acquia_contenthub_publisher')) {
      $export_queue = \Drupal::service('acquia_contenthub_publisher.acquia_contenthub_export_queue');
      $output->writeln($this->toJsonSuccess([
        'queue_name' => 'publish_export',
        'count' => $export_queue->getQueueCount(),
        'base_url' => $input->getOption('uri'),
      ]));
    }

    if ($this->isModuleEnabled('acquia_contenthub_subscriber')) {
      $import_queue = \Drupal::service('acquia_contenthub_subscriber.acquia_contenthub_import_queue');
      $output->writeln($this->toJsonSuccess([
        'queue_name' => 'subscriber_import',
        'import' => $import_queue->getQueueCount(),
        'base_url' => $input->getOption('uri'),
      ]));
    }
    return 0;
  }

}
