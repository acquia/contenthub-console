<?php

namespace Acquia\Console\ContentHub\Client;

/**
 * Interface ContentHubServiceInterface
 *
 * @package Acquia\ContentHubCli\Client\ContentHub
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
   */
  public static function new(): self;

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
   * Checks if client successfully registered.
   *
   * @return bool
   *   True if client registered.
   */
  public function checkClient(): bool;

  /**
   * Purges Content Hub subscription.
   *
   * @return mixed
   *   The response of the purge operation.
   */
  public function purge();

}
