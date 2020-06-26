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

}
