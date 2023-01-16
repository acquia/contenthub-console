<?php

namespace Acquia\Console\ContentHub\Client;

/**
 * Interface ContentHubServiceInterface.
 *
 * @package Acquia\Console\ContentHub\Client
 */
interface ContentHubServiceInterface {

  /**
   * Returns the version of the Content Hub client library.
   *
   * @return int
   *   The version number.
   */
  public function getVersion(): int;

  /**
   * Returns a new instance of ContentHubServiceInterface.
   *
   * @return static
   *   The ContentHubService instance.
   */
  public static function new(): self;

  /**
   * Registers a new client into Content Hub.
   *
   * After registration the new client is going to be used.
   *
   * @param string $name
   *   The name of the new client.
   * @param string $api_key
   *   The Content Hub api key.
   * @param string $secret_key
   *   The Content Hub secret key.
   * @param string $hostname
   *   The Content Hub hostname.
   */
  public function register(string $name, string $api_key, string $secret_key, string $hostname);

  /**
   * Delete a registered client by its name.
   *
   * @param string $name
   *   The name of the client.
   *
   * @return array
   *   The response array.
   */
  public function deleteClientByName(string $name): array;

  /**
   * Gathers Acquia ContentHub subscription clients.
   *
   * @return array
   *   Client information.
   */
  public function getClients(): array;

  /**
   * Returns webhook information from service.
   *
   * @return array
   *   Webhook information.
   */
  public function getWebhooks(): array;

  /**
   * Asks service to ping all registered webhooks and returns with response.
   *
   * @return array
   *   Status information.
   */
  public function getWebhooksStatus(): array;

  /**
   * Returns the remote settings.
   *
   * @return \Acquia\Console\ContentHub\Client\Settings
   *   The Content Hub client settings.
   */
  public function getSettings(): Settings;

  /**
   * Checks if client successfully registered.
   *
   * @return bool
   *   True if client registered.
   */
  public function checkClient(): bool;

  /**
   * Purges Content Hub subscription.
   *
   * @return array
   *   The response of the purge operation.
   */
  public function purge(): array;

  /**
   * Deletes a webhook by its uuid.
   *
   * @return array
   *   The response of the operation.
   */
  public function deleteWebhook($uuid): array;

  /**
   * Returns filter information from service.
   *
   * @return array
   *   Filter information.
   */
  public function listFilters(): array;

  /**
   * Returns snapshots.
   *
   * @return array
   *   Array of snapshots.
   */
  public function getSnapshots(): array;

  /**
   * Create snapshots.
   *
   * @return array
   *   Returns created snapshot.
   */
  public function createSnapshot(): array;

  /**
   * Delete snapshot.
   *
   * @param string $name
   *   Snapshot name.
   *
   * @return array
   *   Array of response.
   */
  public function deleteSnapshot(string $name): array;

  /**
   * Restore snapshot.
   *
   * @param string $name
   *   Snapshot name.
   *
   * @return array
   *   Array of response.
   */
  public function restoreSnapshot(string $name): array;

}
