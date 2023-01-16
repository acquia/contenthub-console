<?php

namespace Acquia\Console\ContentHub\Client;

use Acquia\ContentHubClient\ContentHubClient;
use Acquia\ContentHubClient\Settings as ChSettings;
use EclipseGc\CommonConsole\ServiceRegistry;

class LocalContentHubService implements ContentHubServiceInterface {

  /**
   * @var \Acquia\ContentHubClient\ContentHubClient
   */
  protected $client;

  public function __construct(?ContentHubClient $client) {
    $this->client = $client;
  }

  public function getVersion(): int {
    return 3;
  }

  public static function new(): ContentHubServiceInterface {
    return self::fromEnv();
  }

  public function register(string $name, string $api_key, string $secret_key, string $hostname) {
    /** @var \Psr\Log\LoggerInterface $logger */
    $logger = ServiceRegistry::get('console.logger');
    /** @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $dispatcher */
    $dispatcher = ServiceRegistry::get('event_dispatcher');
    $this->client = ContentHubClient::register($logger, $dispatcher, $name, $hostname, $api_key, $secret_key);
  }

  public function deleteClientByName(string $name): array {
    return $this->client::getResponseJson($this->client->delete('settings/client/name/' . $name));
  }

  public function getClients(): array {
    return $this->client->getClients();
  }

  public function getWebhooks(): array {
    return $this->client->getWebHooks();
  }

  public function getWebhooksStatus(): array {
    return $this->client->getWebhookStatus();
  }

  public function getSettings(): Settings {
    $settings = $this->client->getSettings();
    $webhook = [
      'url' => $settings->getWebhook('url'),
      'uuid' => $settings->getWebhook('uuid'),
      'settings_url' => $settings->getWebhook('settings_url'),
    ];
    return new Settings($settings->getName(), $settings->getUrl(), $settings->getApiKey(), $settings->getSecretKey(), $settings->getUuid(), $settings->getSharedSecret(), $webhook);
  }

  public function checkClient(): bool {
    return $this->client::getResponseJson($this->client->ping()) ?? FALSE;
  }

  public function purge(): array {
    return $this->client->purge();
  }

  public function deleteWebhook($uuid): array {
    return $this->client::getResponseJson($this->client->deleteWebhook($uuid));
  }

  public function listFilters(): array {
    return $this->client->listFilters();
  }

  public function getSnapshots(): array {
    return $this->client->getSnapshots();
  }

  public function createSnapshot(): array {
    return $this->client->createSnapshot();
  }

  public function deleteSnapshot(string $name): array {
    return $this->client->deleteSnapshot($name);
  }

  public function restoreSnapshot(string $name): array {
    return $this->client->restoreSnapshot($name);
  }

  protected static function fromEnv() {
    $name = getenv('CHUC_CLIENT_NAME');
    $uuid = getenv('CHUC_CLIENT_UUID');
    $api_key = getenv('CHUC_API_KEY');
    $secret = getenv('CHUC_SECRET_KEY');
    $hostname = getenv('CHUC_HOSTNAME');

    /** @var \Psr\Log\LoggerInterface $logger */
    $logger = ServiceRegistry::get('console.logger');
    /** @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $dispatcher */
    $dispatcher = ServiceRegistry::get('event_dispatcher');
    $settings = new ChSettings(
      $name, $uuid, $api_key, $secret, $hostname, NULL, []
    );
    $config = [
      'base_url' => $settings->getUrl(),
      'client-user-agent' => 'CHUC',
    ];
    return new static(
      new ContentHubClient(
        $logger, $settings, $settings->getMiddleware(), $dispatcher, $config
      )
    );
  }

}