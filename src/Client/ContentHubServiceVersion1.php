<?php

namespace Acquia\Console\ContentHub\Client;

use GuzzleHttp\ClientInterface;

/**
 * Class ContentHubServiceVersion1
 *
 * @package Acquia\Console\ContentHub\Client
 */
class ContentHubServiceVersion1 implements ContentHubServiceInterface {

  use ContentHubServiceCommonTrait;

  /**
   * The Content Hub client version 1.x.
   *
   * @var \Acquia\ContentHubClient\ContentHub
   */
  protected $client;

  /**
   * ContentHubServiceVer1 constructor.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The guzzle client.
   */
  public function __construct(ClientInterface $client) {
    $this->client = $client;
  }

  /**
   * {@inheritDoc}
   */
  public static function new(): ContentHubServiceInterface {
    /** @var \Drupal\acquia_contenthub\Client\ClientManagerInterface $client_manager */
    $client_manager = \Drupal::service('acquia_contenthub.client_manager');
    if (!$client_manager->getConnection()) {
      $config = \Drupal::config('acquia_contenthub.admin_settings');
      if ($config->isNew()) {
        throw new \Exception('Client could not be instantiated. acquia_contenthub.admin_settings config is empty.');
      }

      $client_manager->resetConnection([
        'api' => $config->get('api_key'),
        'secret' => $config->get('secret_key'),
        'hostname' => $config->get('hostname'),
        'origin' => $config->get('origin'),
      ]);
    }
    return new self($client_manager->getConnection());
  }

  /**
   * {@inheritDoc}
   */
  public function getClients(): array {
    return \Drupal::service('acquia_contenthub.acquia_contenthub_subscription')->getClients();
  }

  /**
   * {@inheritDoc}
   */
  public function checkClient(): bool {
    return \Drupal::service('acquia_contenthub.client_manager')->isConnected();
  }

  /**
   * {@inheritDoc}
   */
  public function getVersion(): int {
    return 1;
  }

  /**
   * {@inheritDoc}
   */
  public function register(string $name, string $api_key, string $secret_key, string $hostname) {
    $client_manager = \Drupal::getContainer()
      ->get('acquia_contenthub.client_manager');
    $client_manager->resetConnection([
      'hostname' => $hostname,
      'api' => $api_key,
      'secret' => $secret_key,
    ]);
    /** @var \Acquia\ContentHubClient\ContentHub $client */
    $client = $client_manager->getConnection();
    $client->register($name);
    $this->client = $client;
  }

  /**
   * {@inheritDoc}
   */
  public function getSettings(): Settings {
    $settings = \Drupal::config('acquia_contenthub.admin_settings');
    return new Settings(
      $settings->get('client_name'),
      $settings->get('hostname'),
      $settings->get('api_key'),
      $settings->get('secret_key'),
      $settings->get('origin'),
      '',
      [
        'webhook_uuid' => $settings->get('webhook_uuid') ?? '',
        'webhook_url' => $settings->get('webhook_url') ?? '',
      ]
    );
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
   * {@inheritDoc}
   */
  public function purge(): array {
    $response = \Drupal::service('acquia_contenthub.client_manager')->createRequest('purge');
    if (!isset($response['success']) || $response['success'] !== TRUE) {
      throw new \Exception("Purge failed. Reason: {$response['error']['message']}");
    }
    return $response;
  }

  /**
   * {@inheritDoc}
   *
   * @throws \Exception
   */
  public function deleteWebhook($uuid): array {
    $response = $this->client->deleteWebhook($uuid);
    $data = (array) json_decode($response->getBody(), TRUE);
    return $this->checkResponseSuccess($data);
  }

}
