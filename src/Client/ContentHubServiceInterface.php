<?php

namespace Acquia\Console\ContentHub\Client;

/**
 * Interface ContentHubServiceInterface
 *
 * @package Acquia\Console\ContentHub\Client
 */
interface ContentHubServiceInterface {

  /**
   * Returns a new instance of ContentHubServiceInterface.
   */
  public static function new(): self;

  /**
   * Get webhook information from service.
   *
   * @return array
   *   Webhook information.
   */
  public function getWebhooks(): array ;
}
