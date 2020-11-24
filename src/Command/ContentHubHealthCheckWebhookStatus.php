<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use DateTime;
use GuzzleHttp\Client;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubAuditWebhookStatus.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubHealthCheckWebhookStatus extends ContentHubCommandBase {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:health-check:webhook-status';

  /**
   * The applicable Content Hub Client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $guzzleClient;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Prints webhook information.');
    $this->addOption('fix', 'f', InputOption::VALUE_NONE, 'Print webhooks information and unsuppress disabled webhooks.');
    $this->setAliases(['ach-hc-ws']);
  }

  /**
   * {@inheritdoc}
   */
  protected function initialize(InputInterface $input, OutputInterface $output) {
    parent::initialize($input, $output);
    if (empty($this->guzzleClient)) {
      $this->guzzleClient = new Client();
    }
  }

  /**
   * Sets guzzleClient instance.
   *
   * @param \GuzzleHttp\Client
   */
  public function setGuzzleClient(Client $guzzleClient): void {
    $this->guzzleClient = $guzzleClient;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $webhooks = $this->achClientService->getWebhooks();
    if (!$webhooks) {
      $output->writeln('No webhooks found.');
      return 1;
    }

    $output->writeln('Webhook status:');
    $this->getTableToRender($webhooks, $output);

    if (!$input->getOption('fix')) {
      return 0;
    }

    foreach ($webhooks as $webhook) {
      if (!$this->isSuppressed($webhook['suppressed_until'])) {
        continue;
      }

      if ($this->isWebhookOnline($webhook['url'], $output)) {
        $output->writeln("Removing suppression from webhook: {$webhook['client_name']}: {$webhook['uuid']}");
        $response = $this->achClientService->removeWebhookSuppression($webhook['uuid']);
        if (!$response['success']) {
          $output->writeln("<error>Something went wrong during suppression removal! {$response['error']['message']}</error>");
        }

        continue;
      }

      $output->writeln("<error>Webhook is offline, cannot remove suppression. ID: {$webhook['uuid']}</error>");
    }

    return 0;
  }

  /**
   * Checks if webhook is online or not.
   *
   * @param string $webhook
   *   Webhook url.
   * @param OutputInterface $output
   *   Output.
   *
   * @return bool
   *   Return TRUE if get request come back with 200 HTTP status code.
   */
  protected function isWebhookOnline(string $webhook, OutputInterface $output): bool {
    try {
      $response = $this->guzzleClient->request('options', $webhook);
    } catch (\Exception $exception) {
      $output->writeln($exception->getMessage());
      return FALSE;
    }

    if ($response->getStatusCode() === 200) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Create Table object from settings information.
   *
   * @param array $webhooks
   *   Webhooks from response.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   Output interface.
   *
   * @throws \Exception
   */
  protected function getTableToRender(array $webhooks, OutputInterface $output): void {
    $table = new Table($output);
    $table->setHeaders(['Client name', 'Status', 'Suppressed until']);

    $rows = [];
    foreach ($webhooks as $webhook) {
      $rows[] = [
        $webhook['client_name'],
        $webhook['status'],
        $this->isSuppressed($webhook['suppressed_until']) ?
          $this->formatTimestamp($webhook['suppressed_until'])
          : 'Not suppressed',
      ];
    }
    $table->addRows($rows);
    $table->render();
  }

  /**
   * Decides if webhook is suppressed or not based on timestamp.
   *
   * @param int $timestamp
   *  Field (suppressed_until) value from response.
   *
   * @return bool
   *   TRUE if suppressed, FALSE otherwise.
   */
  protected function isSuppressed(int $timestamp): bool {
    return $timestamp > time();
  }

  /**
   * Format timestamp into a user friendly format.
   *
   * @param int $timestamp
   *  Field (suppressed_until) value from response.
   *
   * @return string
   * @throws \Exception
   */
  protected function formatTimestamp(int $timestamp): string {
    $date = new DateTime();
    $date->setTimestamp($timestamp);
    return $date->format('Y-m-d H:i:s');
  }

}
