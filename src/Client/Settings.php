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

  protected $clientName;
  protected $hostname;
  protected $apiKey;
  protected $secretKey;
  protected $origin;
  protected $sharedSecret;
  protected $webhook;

  /**
   * Settings constructor.
   *
   * @param string $clientName
   * @param string $hostname
   * @param string $apiKey
   * @param string $secretKey
   * @param string $origin
   * @param string $sharedSecret
   * @param array $webhook
   */
  public function __construct(string $clientName, string $hostname, string $apiKey, string $secretKey, string $origin, string $sharedSecret, array $webhook) {
    $this->clientName = $clientName;
    $this->hostname = $hostname;
    $this->apiKey = $apiKey;
    $this->secretKey = $secretKey;
    $this->origin = $origin;
    $this->sharedSecret = $sharedSecret;
    $this->webhook = $webhook;
  }

  public function getClientName(): string {
    return $this->clientName;
  }

  public function getHostName(): string {
    return $this->hostname;
  }

  public function getApiKey(): string {
    return $this->apiKey;
  }

  public function getSecretKey(): string {
    return $this->secretKey;
  }

  public function getOrigin(): string {
    return $this->origin;
  }

  public function getSharedSecret(): string {
    return $this->sharedSecret;
  }

  public function getWebhook(): array {
    return $this->webhook;
  }

}
