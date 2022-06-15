<?php

namespace Acquia\Console\ContentHub\Command\Helpers;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use Acquia\Console\ContentHub\Command\ContentHubModuleTrait;
use Acquia\Console\ContentHub\Exception\ContentHubVersionException;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubQueueCheck.
 *
 * Enables syndication through queues if it is not set.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubQueueCheck extends ContentHubCommandBase implements PlatformBootStrapCommandInterface {

  use ContentHubModuleTrait;

  /**
   * {@inheritDoc}
   */
  protected static $defaultName = 'ach:check-queue';

  /**
   * {@inheritDoc}
   */
  protected function configure() {
    $this->setDescription('Audit 1.x queues. They should be enabled to hold back ongoing syndication.')
      ->setHidden(TRUE);
  }

  /**
   * {@inheritDoc}
   */
  protected function initialize(InputInterface $input, OutputInterface $output) {
    parent::initialize($input, $output);
    if ($this->drupalServiceFactory->getModuleVersion() !== 1) {
      throw new ContentHubVersionException(1);
    }
  }

  /**
   * {@inheritDoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $config = $this->drupalServiceFactory->getDrupalService('config.factory')->getEditable('acquia_contenthub.entity_config');
    $queue_values = [];
    if ($this->isPublisher($this->drupalServiceFactory)) {
      $queue_values['export_with_queue'] = (bool) $config->get('export_with_queue');
    }

    if ($this->isSubscriber()) {
      $queue_values['import_with_queue'] = (bool) $config->get('import_with_queue');
    }

    if (empty($queue_values)) {
      $output->writeln('<warning>The site cannot be determined in terms of its type if it is subscriber or publisher.</warning>');
      return 1;
    }

    $should_save = FALSE;
    foreach ($queue_values as $key => $is_queue_enabled) {
      if ($is_queue_enabled === FALSE) {
        $config->set($key, TRUE);
        $should_save = TRUE;
      }
    }

    if ($should_save === TRUE) {
      $config->save();
    }

    return 0;
  }

}
