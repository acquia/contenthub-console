<?php

namespace Acquia\Console\ContentHub\Command\Helpers;

/**
 * Class DrupalServiceFactory.
 *
 * @package Acquia\Console\ContentHub\Command\Helpers
 */
class DrupalServiceFactory {

  /**
   * Wrapper around Drupal service containers.
   *
   * @param string $serviceId
   *   Service id.
   *
   * @return object
   *   Returns an instance of a given service.
   */
  public function getDrupalService(string $serviceId): object {
    return \Drupal::getContainer()->get($serviceId);
  }

}
