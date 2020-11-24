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
   *
   * @throws \Exception
   */
  public function getDrupalService(string $serviceId): object {
    $this->checkDrupal();
    return \Drupal::service($serviceId);
  }

  /**
   * Wrapper around Drupal hasService.
   *
   * @param string $serviceId
   *   Service id.
   *
   * @return bool
   *   Returns true if service exists else false.
   *
   * @throws \Exception
   */
  public function hasDrupalService(string $serviceId): bool {
    $this->checkDrupal();
    return \Drupal::hasService($serviceId);
  }

  /**
   * Checks if we are in a drupal environment.
   *
   * @throws \Exception
   */
  protected function checkDrupal(): void {
    if (!class_exists('Drupal')) {
      throw new \Exception('No Drupal instance found.');
    }
  }

  /**
   * Checks if Acquia ContentHub module is present.
   *
   * @return bool
   *   True if Acquia ContentHub module is enabled.
   *
   * @throws \Exception
   */
  public function isContentHubEnabled(): bool {
    return $this->getDrupalService('module_handler')->moduleExists('acquia_contenthub');
  }

  /**
   * Get module version.
   *
   * @return int
   *   2 if module 2.x version is available, otherwise returns 1.
   *
   * @throws \Exception
   */
  public function getModuleVersion(): int {
    return $this->hasDrupalService('acquia_contenthub.client.factory') ? 2 : 1;
  }
}
