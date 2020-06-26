<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use DateTime;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubAuditWebhookStatus.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubAuditWebhookStatus extends ContentHubCommandBase {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:audit:webhook-status';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Prints webhook information and unsuppress disabled webhooks.');
    $this->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Print webhooks information, without removing suppression.');
    $this->setAliases(['ach-ws']);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $webhooks = $this->achClientService->getWebhooks();
    if (!$webhooks) {
      $output->writeln('No webhooks found.');
      return 2;
    }

    $output->writeln('Webhook status:');
    $this->getTableToRender($webhooks, $output);

    if (!$input->getOption('dry-run')) {
      foreach ($webhooks as $webhook) {
        if ($this->isSuppressed($webhook['suppressed_until'])) {
          $output->writeln("Removing suppression from webhook: {$webhook['client_name']}: {$webhook['uuid']}");
          $this->achClientService->removeWebhookSuppression($webhook['uuid']);
        }
      }
    }

    return 0;
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
   * Decides webhook is suppressed or not based on timestamp.
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
