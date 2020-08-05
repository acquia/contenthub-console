<?php

namespace Acquia\Console\ContentHub\Command;

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
   *  Module id.
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
   * @return bool
   *   TRUE if it is a publisher.
   */
  public function isPublisher(): bool {
    $result = \Drupal::database()
      ->select('acquia_contenthub_entities_tracking', 'exp')
      ->fields('exp', ['status_export'])
      ->condition('exp.status_export', '', '<>')
      ->execute()
      ->fetchField();
    return (bool) $result;
  }

}
