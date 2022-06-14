<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

use Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\depcalc\DependencyStack;
use Drupal\depcalc\DependentEntityWrapper;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Runs checks against entity dependencies.
 */
class ContentHubPqDependencies extends ContentHubPqCommandBase {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:pq:dependencies';

  /**
   * The Drupal service factory service.
   *
   * @var \Acquia\Console\ContentHub\Command\Helpers\ModuleDiscoverer
   */
  protected $drupalServiceFactory;

  /**
   * Constructs a new ContentHubPqDependencies object.
   *
   * @param \Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory $drupalServiceFactory
   *   The Drupal service factory service.
   * @param string|null $name
   *   The name of the command.
   */
  public function __construct(DrupalServiceFactory $drupalServiceFactory, string $name = NULL) {
    parent::__construct($name);

    $this->drupalServiceFactory = $drupalServiceFactory;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();

    $this->setDescription('Checks the amount of dependencies and the overall dependency count');
  }

  /**
   * {@inheritdoc}
   */
  protected function runCommand(InputInterface $input, PqCommandResult $result): int {
    if (!$this->depcalcModuleIsEnabled()) {
      throw ContentHubPqCommandErrors::newException(ContentHubPqCommandErrors::$depcalcIsNotEnabledError);
    }

    $content = $this->getExportEligibleContentEntities();
    $formatted = [];
    array_walk($content, function ($val, $key) use ($formatted) {
      $formatted[] = sprintf('%s: %s ', $val, $key);
    });
    $result->setIndicator(
      'Export Eligible Content Entities',
      implode("\n", $formatted),
      'The following entity number should be expected of being exported. The list does not contain dependencies.'
    );

    $depCount = $this->calculateDependenciesForAllEligibleContentEntities();
    $result->setIndicator('Dependencies', $depCount, 'The number of eligible dependencies. The overall entity count that would be exported.');

    return 0;
  }

  /**
   * Returns an array of all export eligible content entities.
   *
   * @return array
   *   An associative array: entityType => n.o. entities
   *
   * @throws \Exception
   */
  public function getExportEligibleContentEntities(): array {
    $entityTypeManager = $this->drupalServiceFactory->getDrupalService('entity_type.manager');
    $entities = [];
    $defs = $entityTypeManager->getDefinitions();
    foreach ($defs as $entityType) {
      $id = $entityType->id();
      if (!$entityType instanceof ContentEntityType || in_array($id, $this->excludedContentEntities())) {
        continue;
      }

      $entities[$id] = $entityTypeManager
        ->getStorage($id)
        ->getQuery()
        ->count()
        ->execute();
    }

    return $entities;
  }

  /**
   * Calculates dependencies of contents that are eligible for export.
   *
   * This will take a bit of time.
   *
   * @return int
   *   The number of dependencies calculated.
   *
   * @throws \Exception
   */
  public function calculateDependenciesForAllEligibleContentEntities(): int {
    $depcalc = $this->drupalServiceFactory->getDrupalService('entity.dependency.calculator');
    $entityTypeManager = $this->drupalServiceFactory->getDrupalService('entity_type.manager');
    $defs = $entityTypeManager->getDefinitions();
    foreach ($defs as $entityType) {
      $id = $entityType->id();
      if (!$entityType instanceof ContentEntityType || in_array($id, $this->excludedContentEntities())) {
        continue;
      }
      $entities = $entityTypeManager->getStorage($id)->loadByProperties();
      $stack = new DependencyStack();
      foreach ($entities as $entity) {
        $wrapper = new DependentEntityWrapper($entity);
        $depcalc->calculateDependencies($wrapper, $stack);
      }
    }

    return $this->getDependencyNumberFromDepcalcCache();
  }

  /**
   * Returns the number of dependencies from depcalc cache table.
   *
   * @return int
   *   The number of dependencies.
   *
   * @throws \Exception
   */
  protected function getDependencyNumberFromDepcalcCache(): int {
    $database = $this->drupalServiceFactory->getDrupalService('connection');
    return $database->select('cache_depcalc')->count()->execute();
  }

  /**
   * Entities that are not eligible for export directly.
   *
   * @return string[]
   *   The excluded content entities.
   */
  protected function excludedContentEntities(): array {
    return [
      'paragraph',
    ];
  }

  /**
   * Checks whether depcalc module is enabled.
   *
   * @return bool
   *   TRUE if enabled.
   *
   * @throws \Exception
   */
  protected function depcalcModuleIsEnabled(): bool {
    return $this->drupalServiceFactory->isModuleEnabled('depcalc');
  }

}
