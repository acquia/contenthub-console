<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

use Acquia\Console\ContentHub\Command\Helpers\ColorizedOutputTrait;
use GuzzleHttp\Client;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Provides general checks for the drupal sites.
 */
class ContentHubPqGeneral extends ContentHubPqCommandBase {

  use ColorizedOutputTrait;

  public const UPDATES_URL = 'https://updates.drupal.org';

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:pq:general';

  /**
   * {@inheritdoc}
   */
  protected function runCommand(InputInterface $input, PqCommandResult $result): int {
    $supportedVersion = $this->getOldestSupportedDrupalVersion();
    $siteDrupalVersion = $this->getSiteDrupalVersion();
    $kriName = 'Drupal Version';
    $kriVal = sprintf('Supported Drupal version: >=%s - Site Drupal version: %s', $supportedVersion, $siteDrupalVersion);

    if ($siteDrupalVersion < $supportedVersion) {
      $result->setIndicator($kriName,
        $kriVal,
        ContentHubPqCommandErrors::$drupalCompatibilityError['message'],
        TRUE,
      );
      return ContentHubPqCommandErrors::$drupalCompatibilityError['code'];
    }

    $result->setIndicator($kriName, $kriVal, 'Current Drupal version is supported!');

    return 0;
  }

  public function getOldestSupportedDrupalVersion(): string {
    $client = new Client([
      'base_uri' => static::UPDATES_URL,
    ]);
    $response = $client->get('release-history/drupal/current');
    $body = (string) $response->getBody();
    $xml = simplexml_load_string($body);
    $latestStableDrupalVersionXml = $xml->xpath('//release[security[@covered=1]][1]');
    if (empty($latestStableDrupalVersionXml)) {
      throw new \Exception('Could not return latest drupal version');
    }

    /** @var \SimpleXMLElement $first */
    $first = current($latestStableDrupalVersionXml);
    $version = $first->version;
    [$major, $minor] = explode('.', $version);
    return sprintf('%s.%s.%s', $major, (int) $minor - 1, 0);
  }

  public function getSiteDrupalVersion(): string {
    return \Drupal::VERSION;
  }

}
