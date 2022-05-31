<?php

namespace Acquia\Console\ContentHub\Command\Helpers;

use GuzzleHttp\Client;

/**
 * Responsible for fetching project version related information.
 */
class VersionFetcher {

  /**
   * Drupal.org API update url.
   */
  public const UPDATES_URL = 'https://updates.drupal.org';

  /**
   * Returns the latest supported version of the project.
   *
   * Security covered releases are considered stable.
   *
   * @return string
   *   The version. This can be in multiple format, therefore it needs
   *   to be treated accordingly. Examples: major.minor.patch, 8.x-major.minor
   */
  public function fetchProjectVersion(string $projectName): string {
    $client = new Client([
      'base_uri' => static::UPDATES_URL,
    ]);
    $response = $client->get("release-history/$projectName/current");
    $body = (string) $response->getBody();
    $xml = simplexml_load_string($body);
    $latestStableVersionXml = $xml->xpath('//release[security[@covered=1]][1]');
    if (empty($latestStableVersionXml)) {
      return '';
    }

    /** @var \SimpleXMLElement $first */
    $first = current($latestStableVersionXml);
    return $first->version;
  }

}
