<?php

namespace Acquia\Console\ContentHub\Client;

use Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory;

/**
 * Class ContentHubClientFactory.
 *
 * @package Acquia\Console\ContentHub\Client
 */
class ContentHubClientFactory {

  /**
   * Returns the applicable Content Hub Client.
   *
   * @param \Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory $factory
   *   Drupal Service Factory to use Drupal static functions.
   *
   * @return \Acquia\Console\ContentHub\Client\ContentHubServiceInterface
   *   Acquia Content Hub Client.
   *
   * @throws \Exception
   */
  public function getClient(DrupalServiceFactory $factory): ContentHubServiceInterface {
    if (!$factory->isContentHubEnabled()) {
      throw new \Exception('No ContentHub version found in your Drupal instance.');
    }

    return $factory->getModuleVersion() === 1 ?
      ContentHubServiceVersion1::new() :
      ContentHubServiceVersion2::new();
  }
}
