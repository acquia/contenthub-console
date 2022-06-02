<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

use Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory;
use Acquia\Console\ContentHub\Command\Helpers\ModuleDiscoverer;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Runs a check against possibly unsupported entity types.
 */
class ContentHubPqEntityTypes extends ContentHubPqCommandBase {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:pq:entity-types';

  /**
   * The module discoverer service.
   *
   * @var \Acquia\Console\ContentHub\Command\Helpers\ModuleDiscoverer
   */
  protected $moduleDiscoverer;

  /**
   * The Drupal service factory service.
   *
   * @var \Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory
   */
  protected $serviceFactory;

  /**
   * Constructs a new ContentHubPqEntityTypes object.
   *
   * @param \Acquia\Console\ContentHub\Command\Helpers\ModuleDiscoverer $moduleDiscoverer
   *   The module discoverer service.
   * @param \Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory $drupalServiceFactory
   *   The Drupal service factory service.
   * @param string|null $name
   *   The name of this command.
   */
  public function __construct(ModuleDiscoverer $moduleDiscoverer, DrupalServiceFactory $drupalServiceFactory, string $name = NULL) {
    parent::__construct($name);

    $this->moduleDiscoverer = $moduleDiscoverer;
    $this->serviceFactory = $drupalServiceFactory;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this->setDescription('Checks after unsupported entity types');
  }

  /**
   * {@inheritdoc}
   */
  protected function runCommand(InputInterface $input, PqCommandResult $result): int {
    $unsupportedEntityTypes = $this->getAllUnsupportedEntityTypes();
    $kriName = 'Unsupported Entity Types';
    if (!empty($unsupportedEntityTypes)) {
      $result->setIndicator(
        $kriName,
        implode(', ', $unsupportedEntityTypes),
        PqCommandResultViolations::$unsupportedEntityTypes,
        TRUE,
      );
      return 0;
    }

    $result->setIndicator(
      $kriName,
      '',
      'No unsupported entity types detected!'
    );

    return 0;

  }

  /**
   * Returns unsupported entity types.
   *
   * @return array
   *   The list of entity types.
   *
   * @throws \Exception
   */
  public function getAllUnsupportedEntityTypes(): array {
    $modules = $this->moduleDiscoverer->getAvailableModules();
    $supported = array_merge($modules['core'], ModuleDiscoverer::getSupportedModules());
    // Makes the process  more efficient at the check phase.
    $supported = array_flip($supported);

    $nonCoreEntityTypes = [];
    $etm = $this->serviceFactory->getDrupalService('entity_type.manager');
    $definitions = $etm->getDefinitions();
    foreach ($definitions as $definition) {
      $class = $definition->getClass();
      $module = explode('\\', $class)[1];

      if (isset($supported[$module]) || $module === 'Core') {
        continue;
      }

      $nonCoreEntityTypes[] = $definition->id();
    }

    return $nonCoreEntityTypes;
  }

}
