<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use Acquia\Console\ContentHub\Exception\ContentHubVersionException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubWebhookStatus.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubWebhookStatus extends ContentHubCommandBase {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:webhook:status';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Uses the Content Hub Service to collect information about the status of webhooks.');
    $this->setAliases(['ach-ws']);
  }

  /**
   * {@inheritDoc}
   */
  protected function initialize(InputInterface $input, OutputInterface $output) {
    parent::initialize($input, $output);

    // @todo If LCH-4575 gets fixed this method can be removed.
    if ($this->achClientService->getVersion() !== 2) {
      throw new ContentHubVersionException(2);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $webhooks = $this->achClientService->getWebhooks();
    if (empty($webhooks)) {
      $output->writeln('No webhooks found.');
      return 0;
    }

    $webhook_status = $this->achClientService->getWebhooksStatus();
    if (!$webhook_status['success']) {
      $output->writeln('Request for webhook status check was unsuccessful.');
      return 1;
    }

    if (empty($webhook_status['data'])) {
      $output->writeln('No webhook found.');
      return 0;
    }

    $webhooks_urls = [];
    foreach ($webhooks as $webhook) {
      $webhooks_urls[$webhook['uuid']] = $webhook['url'];
    }

    $exit_code = 0;
    $rows = [];
    foreach ($webhook_status['data'] as $webhook) {
      $reason = $webhook['reason'] ?? 'Webhook is nonfunctional; No reason given.';
      $rows[] = [
        'uuid' => $webhook['uuid'],
        'url' => $webhooks_urls[$webhook['uuid']],
        'status' => $webhook['status'] === 200 ? "<info>{$webhook['status']}</info>" : "<error>{$webhook['status']}</error>",
        'note' => $webhook['status'] === 200 ? "<info>Webhook is functional</info>" : "<error>{$reason}</error>",
      ];

      if ($webhook['status'] !== 200) {
        $exit_code = 2;
      }
    }

    $table = new Table($output);
    $table->setHeaders(['Webhook uuid', 'Url', 'Status code', 'Note']);
    $table->setRows($rows);
    $table->render();

    return $exit_code;
  }
}
