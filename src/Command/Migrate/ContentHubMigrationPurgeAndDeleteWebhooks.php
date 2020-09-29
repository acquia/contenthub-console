<?php

namespace Acquia\Console\ContentHub\Command\Migrate;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use Acquia\Console\ContentHub\Command\Helpers\PlatformCommandExecutionTrait;
use Acquia\Console\ContentHub\Exception\ContentHubVersionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Extension\MissingDependencyException;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubMigrationPrepareUpgrade.
 *
 * @package Acquia\Console\ContentHub\Command\Migration
 */
class ContentHubMigrationPurgeAndDeleteWebhooks extends ContentHubCommandBase implements PlatformBootStrapCommandInterface {

  use PlatformCommandExecutionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:migrate:purge-delwh';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Purges Content Hub Subscription and Delete Webhooks.')
      ->setAliases(['ach-pdw']);
  }

  /**
   * {@inheritDoc}
   */
  protected function initialize(InputInterface $input, OutputInterface $output) {
    parent::initialize($input, $output);

    if ($this->achClientService->getVersion() !== 1) {
      throw new ContentHubVersionException(1);
    }
  }

  /**
   * {@inheritDoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Extension\MissingDependencyException
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $ret = $this->purgeSubscription($output);
    $ret += $this->deleteWebhooks($output);
    return $ret;
  }


  /**
   * Purges entities from Content Hub subscription.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output stream to write status to.
   *
   * @return int
   *   Exit Code: 0 if succeeds, 1 otherwise.
   */
  protected function purgeSubscription(OutputInterface $output): int {
    $output->writeln('Purging subscription...');
    try {
      $this->achClientService->purge();
    }
    catch (\Exception $e) {
      $output->writeln("<error>{$e->getMessage()}</error>");
      return 1;
    }
    $output->writeln('<info>Subscription has been successfully purged.</info>');
    return 0;
  }

  /**
   * Deletes every webhook.
   */
  protected function deleteWebhooks(OutputInterface $output): int {
    $output->writeln('Deleting webhooks...');
    $webhooks = $this->achClientService->getWebhooks();
    if (empty($webhooks)) {
      $output->writeln('<warning>No webhooks to delete.</warning>');
    }

    $ret = 0;
    foreach ($webhooks as $webhook) {
      try {
        $this->achClientService->deleteWebhook($webhook['uuid']);
      }
      catch (\Exception $e) {
        $output->writeln("<error>Could not delete webhook with id {$webhook['uuid']}. Reason: {$e->getMessage()}</error>");
        $ret = 1;
      }
    }
    $output->writeln('<info>Webhook deletion process has been finished.</info>');
    return $ret;
  }

}
