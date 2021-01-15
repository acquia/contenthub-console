<?php

namespace Acquia\Console\ContentHub\Client;

/**
 * Class Settings.
 *
 * Defines a unified settings structure.
 *
 * @package Acquia\Console\ContentHub\Client
 */
class Settings {

  /**
   * Client Name.
   *
   * @var string
   */
  protected $clientName;

  /**
   * HostName.
   *
   * @var string
   */
  protected $hostname;

  /**
   * API Key.
   *
   * @var string
   */
  protected $apiKey;

  /**
   * Secret key.
   *
   * @var string
   */
  protected $secretKey;

  /**
   * Origin UUID.
   *
   * @var string
   */
  protected $origin;

  /**
   * Shared secret key.
   *
   * @var string
   */
  protected $sharedSecret;

  /**
   * Webhook.
   *
   * @var array
   */
  protected $webhook;

  /**
   * Settings constructor.
   *
   * @param string $client_name
   *   Client Name.
   * @param string $hostname
   *   HostName.
   * @param string $api_key
   *   Api key.
   * @param string $secret_key
   *   Secret key.
   * @param string $origin
   *   Origin UUID.
   * @param string $shared_secret
   *   Shared secret key.
   * @param array $webhook
   *   Webhook.
   */
  public function __construct(string $client_name, string $hostname, string $api_key, string $secret_key, string $origin, string $shared_secret, array $webhook) {
    $this->clientName = $client_name;
    $this->hostname = $hostname;
    $this->apiKey = $api_key;
    $this->secretKey = $secret_key;
    $this->origin = $origin;
    $this->sharedSecret = $shared_secret;
    $this->webhook = $webhook;
  }

  /**
   * Get client name.
   *
   * @return string
   *   Client name.
   */
  public function getClientName(): string {
    return $this->clientName;
  }

  /**
   * Get hostname.
   *
   * @return string
   *   Hostname.
   */
  public function getHostName(): string {
    return $this->hostname;
  }

  /**
   * Get API key.
   *
   * @return string
   *   API key.
   */
  public function getApiKey(): string {
    return $this->apiKey;
  }

  /**
   * Get Secret key.
   *
   * @return string
   *   Secret key.
   */
  public function getSecretKey(): string {
    return $this->secretKey;
  }

  /**
   * Get Origin UUID.
   *
   * @return string
   *   Origin UUID.
   */
  public function getOrigin(): string {
    return $this->origin;
  }

  /**
   * Get Shared Secret key.
   *
   * @return string
   *   Shared Secret key.
   */
  public function getSharedSecret(): string {
    return $this->sharedSecret;
  }

  /**
   * Get webhook.
   *
   * @return array
   *   Webhook.
   */
  public function getWebhook(): array {
    return $this->webhook;
  }

}
