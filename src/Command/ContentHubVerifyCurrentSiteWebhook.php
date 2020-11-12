<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use Acquia\Console\ContentHub\Command\ContentHubModuleTrait;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubVerifyWebhooksDefaultFilters
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubVerifyCurrentSiteWebhook extends ContentHubCommandBase implements PlatformBootStrapCommandInterface {

  use ContentHubModuleTrait;

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
   *   Returns 1 if current site webhook not found in ACH Service otherwise 0.
   *
   * @throws \Exception
   */
  protected function verifyCurrentSiteWebhook(OutputInterface $output) : int {
    $webhooks = $this->achClientService->getWebhooks();
    if (!$webhooks) {
      $output->writeln('<error>No webhooks found.</error>');
      return 1;
    }

    // Fetch webhook uuids.
    $webhook_uuids = array_column($webhooks, 'uuid');
    // Fetch webhook urls.
    $webhook_urls = array_column($webhooks, 'url');

    // Current site webhook.
    $current_webhook = $this->getCurrentSiteWebhookFromConfig();

    // Check both webhook uuid and url.
    if (!(in_array($current_webhook['webhook_uuid'], $webhook_uuids) && in_array($current_webhook['webhook_url'], $webhook_urls))) {
      $output->writeln(sprintf('<error>Current site\'s webhook "%s" is not registered in the Content Hub service.</error>', $current_webhook['webhook_url']));
      return 1;
    }
    $output->writeln('<info>The current site\'s webhook is correctly registered in the Content Hub service.</info>');
    return 0;
  }

}
