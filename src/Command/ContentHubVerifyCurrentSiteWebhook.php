<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubVerifyWebhooksDefaultFilters
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubVerifyCurrentSiteWebhook extends ContentHubCommandBase implements PlatformBootStrapCommandInterface {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:verify-current-site-webhook';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Verify current site webhook.');
    $this->setAliases(['ach-vcsw']);
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
    $output->writeln('Verifying current site webhook...');
    return $this->verifyCurrentSiteWebhook($output);
  }

  /**
   * Verify current site webhook.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *
   * @return int
   */
  protected function verifyCurrentSiteWebhook(OutputInterface $output) {
    $webhooks = $this->achClientService->getWebhooks();
    if (!$webhooks) {
      $output->writeln('<error>No webhooks found.</error>');
      return 1;
    }
    // Fetch webhook uuids.
    $webhook_uuid = array_column($webhooks, 'uuid');

    // Current site webhook.
    $settings = $this->achClientService->getSettings()->getWebhook();
    if (!in_array($settings['webhook_uuid'], $webhook_uuid)) {
      $output->writeln('<warning>Current site webhook ' . $settings['webhook_url'] . ' is not found in webhooks list.</warning>');
      return 1;
    }
    return 0;
  }

}
