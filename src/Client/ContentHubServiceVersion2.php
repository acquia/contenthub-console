<?php

namespace Acquia\Console\ContentHub\Client;

use Acquia\Console\ContentHub\Command\ContentHubModuleTrait;
use GuzzleHttp\ClientInterface;

/**
 * Class ContentHubServiceVersion2
 *
 * @package Acquia\Console\ContentHub\Client
 */
class ContentHubServiceVersion2 implements ContentHubServiceInterface {

  use ContentHubModuleTrait;

  /**
   * Http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * ContentHubServiceVersion2 constructor.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *
   * @throws \Exception
   */
  public function __construct(ClientInterface $client) {
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function new(): ContentHubServiceInterface {
    $client = \Drupal::service('acquia_contenthub.client.factory')->getClient();
    if (!$client) {
      throw new \Exception("The client was not instantiated. Your credentials are likely missing. Please register this site with ContentHub before continuing.");
    }
    return new self($client);
  }

  /**
   * {@inheritdoc}
   */
  public function getWebhooks(): array {
    $webHooks = [];
    foreach ($this->client->getWebHooks() as $webHook) {
      $webHooks[] = $webHook->getDefinition();
    }

    return $webHooks;
  }

  /**
   * {@inheritdoc}
   */
  public function getClients(): array {
    $clients = [];
    foreach ($this->client->getWebHooks() as $webhook) {
      $clients[$webhook->getClientName()] = $webhook->getUrl();
    }

    return $clients;
  }

  /**
   * {@inheritdoc}
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
    $this->client->unSuppressWebhook($webhook_uuid);
  }

}
