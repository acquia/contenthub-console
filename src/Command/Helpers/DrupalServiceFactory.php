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
   * Checks whether a particular module is present in the codebase.
   *
   * @param string $module
   *   The module to check if it is present.
   *
   * @return bool
   *   TRUE if module exists in the codebase, FALSE otherwise.
   *
   * @throws \Exception
   */
  public function isModulePresentInCodebase(string $module): bool {
    return $this->getDrupalService('extension.list.module')->exists($module);
  }

  /**
   * Checks if a particular module is enabled.
   *
   * @param string $module
   *   The module to enable.
   *
   * @return bool
   *   TRUE if module is enabled, FALSE otherwise.
   *
   * @throws \Exception
   */
  public function isModuleEnabled(string $module): bool {
    return $this->getDrupalService('module_handler')->moduleExists($module);
  }

  /**
   * Enables a module in the site.
   *
   * @param array $modules
   *   The modules to enable.
   *
   * @return bool
   *   TRUE if the modules were successfully enabled, FALSE otherwise.
   *
   * @throws \Exception
   */
  public function enableModules(array $modules): bool {
    return $this->getDrupalService('module_installer')->install($modules);
  }

  /**
   * Get module version.
   *
   * @return int
   *   2 if module 2.x version is available, otherwise returns 1. Returns 0 if Content Hub is not installed.
   *
   * @throws \Exception
   */
  public function getModuleVersion(): int {
    return $this->hasDrupalService('acquia_contenthub.client.factory') ? 2 :
      ($this->hasDrupalService('acquia_contenthub.client_manager') ? 1 : 0);
  }

}
