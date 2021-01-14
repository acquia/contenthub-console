<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory;

/**
 * Trait ContentHubModuleTrait.
 *
 * Trait contains functions which can work properly within a Drupal environment.
 *
 * @package Acquia\Console\ContentHub\Command
 */
trait ContentHubModuleTrait {

  /**
   * Checks if the given module is enabled or not.
   *
   * @param $module
   *   Module id.
   *
   * @return bool
   *   TRUE if enabled, FALSE otherwise.
   */
  public function isModuleEnabled($module): bool {
    return \Drupal::moduleHandler()->moduleExists($module);
  }

  /**
   * Query entities in tracking table by a given status.
   *
   * @param string $table
   *   Tracking table to do the query against.
   * @param array $status
   *   Array which contains the status strings to filter by.
   *
   * @return array
   *   Array containing uuids of processed entities.
   */
  public function queryTrackingTableByStatus(string $table, array $status): array {
    $database = \Drupal::database();
    $query = $database
      ->select($table, 't')
      ->fields('t', ['entity_uuid']);
    $query->condition('t.status', $status, 'IN');
    $entites = $query->execute()->fetchAllAssoc('entity_uuid');

    return array_keys($entites);
  }

  /**
   * Checks if the given site can be considered as publisher.
   *
   * @param \Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory $drupalServiceFactory
   *   The Drupal service factory.
   *
   * @return bool
   *   TRUE if it is a publisher.
   *
   * @throws \Exception
   */
  public function isPublisher(DrupalServiceFactory $drupalServiceFactory): bool {
    if ($drupalServiceFactory->getModuleVersion() === 2 && !$drupalServiceFactory->getDrupalService('database')->schema()->tableExists('acquia_contenthub_entities_tracking')) {
      return $drupalServiceFactory->getDrupalService('module_handler')->moduleExists('acquia_contenthub_publisher');
    }

    $result = $drupalServiceFactory->getDrupalService('database')
      ->select('acquia_contenthub_entities_tracking', 'exp')
      ->fields('exp', ['status_export'])
      ->condition('exp.status_export', '', '<>')
      ->execute()
      ->fetchField();
    return (bool) $result;
  }

  /**
   * Checks if the given site can be considered as subscriber.
   *
   * @return bool
   *   TRUE if it is a subscriber.
   */
  public function isSubscriber(): bool {
    return \Drupal::moduleHandler()->moduleExists('acquia_contenthub_subscriber');
  }

  /**
   * Gets the current webhook from Drupal config depending on module version.
   *
   * @param \Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory $drupalServiceFactory
   *   Drupal Service Factory to use Drupal static functions.
   *
   * @return array
   *   Associative Array containing webhook uuid and webhook url.
   *
   * @throws \Exception
   */
  public function getCurrentSiteWebhookFromConfig(DrupalServiceFactory $drupalServiceFactory) : array {
    $ach_config = $drupalServiceFactory->getDrupalService('config.factory')->get('acquia_contenthub.admin_settings');

    // Get webhook uuid and url specific to module version.
    if ($drupalServiceFactory->getModuleVersion() === 2) {
      $webhook_uuid = $ach_config->get('webhook.uuid');
      $webhook_url = $ach_config->get('webhook.url');
    }
    else {
      $webhook_uuid = $ach_config->get('webhook_uuid');
      $webhook_url = $ach_config->get('webhook_url');
    }
    return ['webhook_uuid' => $webhook_uuid, 'webhook_url' => $webhook_url];
  }

}
