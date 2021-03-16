<?php

namespace Acquia\Console\ContentHub\Client;

use Acquia\Console\ContentHub\Command\ContentHubModuleTrait;
use GuzzleHttp\ClientInterface;

/**
 * Class ContentHubServiceVersion2.
 *
 * @package Acquia\Console\ContentHub\Client
 */
class ContentHubServiceVersion2 implements ContentHubServiceInterface {

  use ContentHubModuleTrait;
  use ContentHubServiceCommonTrait;

  /**
   * The Content Hub client version 2.x.
   *
   * @var \Acquia\ContentHubClient\ContentHubClient
   */
  protected $client;

  /**
   * ContentHubServiceVersion2 constructor.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The guzzle client.
   *
   * @throws \Exception
   */
  public function __construct(ClientInterface $client) {
    $this->client = $client;
  }

  /**
   * {@inheritDoc}
   */
  public static function new(): ContentHubServiceInterface {
    $client = \Drupal::service('acquia_contenthub.client.factory')->getClient();
    if (!$client) {
      throw new \Exception("The client was not instantiated. Your credentials are likely missing. Please register this site with ContentHub before continuing.");
    }
    return new self($client);
  }

  /**
   * {@inheritDoc}
   */
  public function getVersion(): int {
    return 2;
  }

  /**
   * {@inheritDoc}
   */
  public function register(string $name, string $api_key, string $secret_key, string $hostname) {
    /** @var \Drupal\acquia_contenthub\Client\ClientFactory $client_factory */
    $client_factory = \Drupal::service('acquia_contenthub.client.factory');
    $this->client = $client_factory->registerClient($name, $hostname, $api_key, $secret_key, 'v2');
  }

  /**
   * {@inheritDoc}
   */
  public function getWebhooks(): array {
    $webhooks = [];
    foreach ($this->client->getWebHooks() as $webhook) {
      $webhooks[] = $webhook->getDefinition();
    }

    return $webhooks;
  }

  /**
   * {@inheritDoc}
   */
  public function getFilters(): array {
    $webhooks = [];
    foreach ($this->client->getFilters() as $webhook) {
      $webhooks[] = $webhook->getDefinition();
    }

    return $webhooks;
  }

  /**
   * {@inheritDoc}
   */
  public function getClients(): array {
    $clients = [];
    foreach ($this->client->getWebHooks() as $webhook) {
      $clients[$webhook->getClientName()] = $webhook->getUrl();
    }

    return $clients;
  }

  /**
   * {@inheritDoc}
   */
  public function checkClient() :bool {
    return \Drupal::service('acquia_contenthub.connection_manager')->checkClient() ? TRUE : FALSE;
  }

  /**
   * Gets confirmed and exported entities from tracking table.
   *
   * @return array
   *   Entity UUIDs.
   */
  public function getExportedEntitiesFromTrackingTable(): array {
    $entities = [];
    if ($this->isModuleEnabled('acquia_contenthub_publisher')) {
      $entities = $this->queryTrackingTableByStatus(
        'acquia_contenthub_publisher_export_tracking',
        ['confirmed', 'exported']
      );
    }

    return $entities;
  }

  /**
   * Gets imported entities from tracking table.
   *
   * @return array
   *   Entity UUIDs.
   */
  public function getImportedEntitiesFromTrackingTable(): array {
    $entities = [];
    if ($this->isModuleEnabled('acquia_contenthub_subscriber')) {
      $entities = $this->queryTrackingTableByStatus(
        'acquia_contenthub_subscriber_import_tracking',
        ['imported']
      );
    }

    return $entities;
  }

  /**
   * Gets interest list from service.
   *
   * @return array
   *   Interest list.
   */
  public function getInterestList(): array {
    $settings = $this->client->getSettings();
    $webhook = $settings->getWebhook('uuid');

    return $this->client->getInterestsByWebhook($webhook);
  }

  /**
   * Gathers differences between tracking tables and interest list.
   *
   * @return array
   *   Entity UUIDs.
   */
  public function getTrackingAndInterestDiff(): array {
    $tracked_entities = array_merge(
      $this->getExportedEntitiesFromTrackingTable(),
      $this->getImportedEntitiesFromTrackingTable()
    );

    $interest_list = $this->getInterestList();

    if (empty($tracked_entities) && empty($interest_list)) {
      return [];
    }

    return [
      'tracking_diff' => array_diff($tracked_entities, $interest_list),
      'interest_diff' => array_diff($interest_list, $tracked_entities),
    ];
  }

  /**
   * Removes suppression from webhook.
   *
   * @param string $webhook_uuid
   *   Webhook UUID.
   */
  public function removeWebhookSuppression(string $webhook_uuid) {
    return $this->client->unSuppressWebhook($webhook_uuid);
  }

  /**
   * {@inheritDoc}
   */
  public function purge(): array {
    $response = $this->client->purge();
    if (!(isset($response['success'])) || $response['success'] !== TRUE) {
      throw new \Exception('Error trying to purge your subscription. You might require elevated keys to perform this operation.');
    }

    if (!empty($response['error']['code']) && !empty($response['error']['message'])) {
      throw new \Exception(sprintf('Error trying to purge your subscription. Status code %s. %s',
        $response['error']['code'],
        $response['error']['message']
      ));
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
    $data = (array) json_decode($response, TRUE);
    return $this->checkResponseSuccess($data);
  }

  /**
   * {@inheritDoc}
   */
  public function getSettings(): Settings {
    $settings = $this->client->getSettings()->toArray();
    return new Settings(
      $settings['name'],
      $settings['url'],
      $settings['apiKey'],
      $settings['secretKey'],
      $settings['uuid'],
      $settings['sharedSecret'],
      [
        'webhook_uuid' => $settings['webhook']['uuid'],
        'webhook_url' => $settings['webhook']['url'],
      ]
    );
  }

  /**
   * {@inheritDoc}
   */
  public function listFilters(): array {
    return $this->client->listFilters();
  }

  /**
   * {@inheritDoc}
   */
  public function getSnapshots(): array {
    return $this->client->getSnapshots();
  }

  /**
   * {@inheritDoc}
   */
  public function createSnapshot(): array {
    return $this->client->createSnapshot();
  }

  /**
   * {@inheritDoc}
   */
  public function deleteSnapshot(string $name): array {
    return $this->client->deleteSnapshot($name);
  }

  /**
   * {@inheritDoc}
   */
  public function restoreSnapshot($name): array {
    return $this->client->restoreSnapshot($name);
  }

  /**
   * {@inheritdoc}
   */
  public function getWebhooksStatus(): array {
    return $this->client->getWebhookStatus();
  }

  /**
   * Returns remote settings for current subscription.
   *
   * @return array
   *   Array of /v2/settings.
   */
  public function getRemoteSettings(): array {
    return $this->client->getRemoteSettings();
  }

}
