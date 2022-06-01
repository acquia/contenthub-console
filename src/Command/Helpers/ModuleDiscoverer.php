<?php

namespace Acquia\Console\ContentHub\Command\Helpers;

/**
 * Responsible for module scans.
 */
class ModuleDiscoverer {

  /**
   * The Drupal service factory service.
   *
   * @var \Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory
   */
  protected $drupalServiceFactory;

  /**
   * Constructs a new ModuleDiscoverer object.
   *
   * @param \Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory $drupalServiceFactory
   *   The Drupal service factory service.
   */
  public function __construct(DrupalServiceFactory $drupalServiceFactory) {
    $this->drupalServiceFactory = $drupalServiceFactory;
  }

  /**
   * Returns available modules based on which belong to core and which not.
   *
   * @return array
   *   List of available modules decided into two groups, core and non-core.
   *
   * @throws \Exception
   */
  public function getAvailableModules(): array {
    $moduleList = $this->drupalServiceFactory->getDrupalService('extension.list.module');
    $all = $moduleList->getList();
    $coreModules = [];
    $nonCoreModules = [];

    foreach ($all as $module) {
      if ($module->getType() !== 'module') {
        continue;
      }

      if (strpos($module->getPath(), 'core/modules') !== FALSE) {
        $coreModules[$module->getName()] = $module->getName();
      }
      else {
        $nonCoreModules[$module->getName()] = $module->getName();
      }
    }

    return [
      'core' => $coreModules,
      'non-core' => $nonCoreModules,
    ];
  }

  /**
   * Returns the list of modules that content hub provides support for.
   *
   * @return string[]
   *   The list of modules.
   */
  public static function getSupportedModules(): array {
    return [
      'webforms',
      'paragraphs',
      'crop',
      'focal_point',
      'redirect',
      'metatags',
      'entity_queue',
      'depcalc',
      's3fs',
    ];
  }

}
