<?php

namespace Acquia\Console\ContentHub\Client;

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ContentHubClientFactory.
 *
 * @package Acquia\Console\ContentHub\Client
 */
class ContentHubClientFactory {

  /**
   * Returns the applicable Content Hub Client.
   *
   * @return \Acquia\Console\ContentHub\Client\ContentHubServiceInterface|null
   *   Acquia Content Hub Client.
   * @throws \Exception
   */
  public function getClient(): ?ContentHubServiceInterface {
    if (!class_exists('Drupal')) {
      throw new \Exception('No Drupal instance found.');
    }

    if (!$this->isContentHubEnabled()) {
      throw new \Exception('No ContentHub version found in your Drupal instance.');
    }

    return $this->getModuleVersion() === 1 ?
      ContentHubServiceVersion1::new() :
      ContentHubServiceVersion2::new();
  }

  /**
   * Checks if Acquia ContentHub module is present.
   *
   * @return bool
   *   True if Acquia ContentHub module is enabled.
   */
  protected function isContentHubEnabled(): bool {
    return \Drupal::moduleHandler()->moduleExists('acquia_contenthub');
  }

  /**
   * Get module version.
   *
   * @return int
   *   2 if module 2.x version is available, otherwise returns 1.
   */
  public function getModuleVersion(): int {
    return \Drupal::hasService('acquia_contenthub.client.factory') ? 2 : 1;
  }

}
