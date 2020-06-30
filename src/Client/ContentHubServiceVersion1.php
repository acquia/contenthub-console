<?php

namespace Acquia\Console\ContentHub\Client;

use GuzzleHttp\ClientInterface;

/**
 * Class ContentHubServiceVersion1
 *
 * @package Acquia\Console\ContentHub\Client
 */
class ContentHubServiceVersion1 implements ContentHubServiceInterface {

  /**
   * Http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * ContentHubServiceVer1 constructor.
   *
   * @param \GuzzleHttp\ClientInterface $client
   */
  public function __construct(ClientInterface $client) {
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function new(): ContentHubServiceInterface {
    return new self(\Drupal::service('acquia_contenthub.client_manager')->getConnection());
  }

  /**
   * {@inheritdoc}
   */
  public function getClients(): array {
    return \Drupal::service('acquia_contenthub.acquia_contenthub_subscription')->getClients();
  }

  /**
   * {@inheritdoc}
   */
  public function checkClient(): bool {
    return \Drupal::service('acquia_contenthub.client_manager')->isConnected();
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion(): int {
    return 1;
  }

  /**
   * {@inheritdoc}
   */
  public function getWebhooks(): array {
    $webhooks = [];
    $settings = $this->client->getSettings();
    if (!empty($settings['webhooks'])) {
      $webhooks = $settings['webhooks'];
    }

    return $webhooks;
  }

  /**
   * {@inheritdoc}
   */
  public function purge() {
    $response = \Drupal::service('acquia_contenthub.client_manager')->createRequest('purge');
    if (!isset($response['success']) || $response['success'] !== TRUE) {
      throw new \Exception("Purge failed. Reason: {$response['error']['message']}");
    }
    return $response;
  }

}
