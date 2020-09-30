<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubVerifyWebhooksDefaultFilters
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubVerifyWebhooksDefaultFilters extends ContentHubCommandBase implements PlatformBootStrapCommandInterface {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:verify-webhooks:default-filters';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Verify webhooks default filters in 2.x after migration.');
    $this->setAliases(['ach-vw-df']);
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
    $output->writeln('Verifying default filters...');
    $verify_default_filters = $this->verifyWebhookDefaultFilters($output);
    $verify_filter_migration = $this->verifyFiltersMigration($output);
    if ($verify_default_filters || $verify_filter_migration) {
      return 1;
    }
    return 0;
  }

  /**
   * Verify webhooks default filters.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *
   * @return int
   */
  protected function verifyWebhookDefaultFilters(OutputInterface $output) {
    $webhooks = $this->achClientService->getWebhooks();
    if (!$webhooks) {
      $output->writeln('<error>No webhooks found.</error>');
      return 1;
    }

    $filters = $check_filters = [];
    // Fetch webhook filters.
    $webhook_filters = array_column($webhooks, 'filters');
    foreach ($webhook_filters as $webhook_filter) {
      $filters = array_merge($filters, $webhook_filter);
    }

    // Fetch all filters and separate default filters.
    $all_filters = $this->achClientService->listFilters();
    $all_filters = array_column($all_filters['data'], 'name', 'uuid');
    $default_filters  = preg_grep("/" . 'default_filter' . "/i", $all_filters);
    foreach ($default_filters as $filter_uuid => $filter_name) {
      if (!in_array($filter_uuid, $filters)) {
        $check_filters[] = [$filter_uuid, $filter_name];
      }
    }

    if ($check_filters) {
      $output->writeln('<warning>Some default filters are not attached to any webhook.<warning>');
      $table = new Table($output);
      $table->setHeaders(['UUID, Name']);
      $table->addRows($check_filters);
      $table->render();
      return 1;
    }
    $output->writeln('<info>All default filters are successfully attached to their corresponding webhooks.</info>');
    return 0;
  }

  /**
   * Verify filters migration from 1.x to 2.x
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *
   * @return int
   */
  protected function verifyFiltersMigration(OutputInterface $output) {
    $unmigrated_filters = \Drupal::state()->get('acquia_contenthub_subscriber_82002_unmigrated_filters');
    $contenthub_filters = \Drupal::state()->get('acquia_contenthub_subscriber_82002_acquia_contenthub_filters');
    if (empty($unmigrated_filters) && empty($contenthub_filters)) {
      $output->writeln('<info>All filters have been successfully migrated from 1.x to 2.x</info>');
      return 0;
    }
    $output->writeln('<warning>Not all filters have been successfully migrated from 1.x to 2.x. Below is a list of the filters that were not migrated:</warning>');
    $table = new Table($output);
    $table->setHeaders(['Unsuccessful migrated filters']);
    $table->addRows([$unmigrated_filters]);
    $table->render();
    return 1;
  }

}
