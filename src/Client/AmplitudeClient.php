<?php

namespace Acquia\Console\ContentHub\Client;

use Consolidation\Config\Config;
use Symfony\Component\Yaml\Yaml;
use Zumba\Amplitude\Amplitude;

/**
 * Provides an amplitude client to log events.
 */
class AmplitudeClient {

  /**
   * Config directory to load Amplitude API Key YAML.
   *
   * @var array
   */
  protected $apiKeyDir = [
    '/config',
    'amplitude_api_key.yml',
  ];

  /**
   * Amplitude API Key.
   *
   * @var string
   */
  private string $amplitudeApiKey = '';

  /**
   * The Zumba Amplitude client.
   *
   * @var \Zumba\Amplitude\Amplitude
   */
  private Amplitude $amplitude;

  /**
   * Constructs an instance of Amplitude Client.
   *
   * @param string $amplitude_user_id
   *   The Amplitude user ID, usually the Content Hub Service
   *   Client UUID which will be specific to subscription.
   */
  public function __construct(string $amplitude_user_id) {
    $this->loadAmplitudeApiConfig();
    $this->amplitude = (new Amplitude())->init($this->amplitudeApiKey, $amplitude_user_id);
  }

  /**
   * Determines whether or not amplitude client is ready.
   *
   * @return bool
   *   TRUE if Amplitude is ready or FALSE if not.
   */
  public function isReady(): bool {
    return (bool) $this->amplitude;
  }

  /**
   * Logs an event.
   *
   * @param string $name
   *   The event name, e.g., "Fixture created".
   * @param array $properties
   *   An associative array of key/value pairs corresponding to properties or
   *   attributes of the event.
   *
   * @see https://help.amplitude.com/hc/en-us/articles/115000465251#how-should-i-name-my-events
   * @see https://help.amplitude.com/hc/en-us/articles/115000465251#event-properties
   */
  public function logEvent(string $name, array $properties = []): void {
    if (!$this->amplitude) {
      return;
    }
    $properties['timestamp'] = time();
    $this->amplitude->logEvent($name, $properties);
  }

  /**
   * Sets the amplitude key from the configuration yaml.
   *
   * @throws \Exception
   */
  private function loadAmplitudeApiConfig(): void {
    if (!file_exists(__DIR__ . implode(DIRECTORY_SEPARATOR, $this->apiKeyDir))) {
      throw new \Exception('Amplitude API Key config YAML doesn\'t exist.');
    }
    $config = new Config(Yaml::parse(file_get_contents(__DIR__ . implode(DIRECTORY_SEPARATOR, $this->apiKeyDir))));
    if ($config->has('amplitudeApiKey') && $config->get('amplitudeApiKey')) {
      $this->amplitudeApiKey = (string) $config->get('amplitudeApiKey');
    }
  }

}
