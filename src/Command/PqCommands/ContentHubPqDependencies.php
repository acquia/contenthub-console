<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

use Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory;
use Drupal\Core\Cache\DatabaseBackend;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Site\Settings;
use Drupal\depcalc\DependencyStack;
use Drupal\depcalc\DependentEntityWrapper;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Runs checks against entity dependencies.
 */
class ContentHubPqDependencies extends ContentHubPqCommandBase {

  /**
   * Entities that are not eligible for export directly.
   */
  public const EXCLUDED_CONTENT_ENTITIES = [
    'paragraph',
  ];

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

    $this->setDescription('Checks the amount of content entities and the overall dependency count');
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
    foreach ($content as $key => $val) {
      $formatted[] = sprintf('%s: %s ', $key, $val);
    }
    $result->setIndicator(
      'Export Eligible Content Entities',
      implode("\n", $formatted),
      'The following entities and their count should be expected to be exported. The list does not contain dependencies. Make sure to only export desired entities.'
    );

    $depCount = $this->calculateDependenciesForAllEligibleContentEntities();
    $result->setIndicator(
      'Dependencies',
      $depCount,
      'The number of eligible dependencies. The overall entity count that would be exported, content and config entities included',
    );

    $rowsShouldBeIncreased = $depCount >= $this->getMaxRowsForDepcalcCache();
    $message = 'The number of rows depcalc is allowed to cache ';
    $result->setIndicator(
      'Depcalc Cache Bin Max Rows',
      $this->getMaxRowsForDepcalcCache(),
      $rowsShouldBeIncreased ? $message . 'is too low!' : $message . 'is sufficient.',
      $rowsShouldBeIncreased
    );

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
      if (!$this->isEligible($entityType)) {
        continue;
      }

      $id = $entityType->id();
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
      if (!$this->isEligible($entityType)) {
        continue;
      }
      $entities = $entityTypeManager->getStorage($entityType->id())->loadByProperties();
      $stack = new DependencyStack();
      foreach ($entities as $entity) {
        $wrapper = new DependentEntityWrapper($entity);
        $depcalc->calculateDependencies($wrapper, $stack);
      }
    }

    return $this->getDependencyNumberFromDepcalcCache();
  }

  /**
   * Returns the maximum allowed rows in depcalc cache table.
   *
   * @return int
   *   The max rows set for depcalc cache or the default rows.
   */
  public function getMaxRowsForDepcalcCache(): int {
    $max_rows_settings = Settings::getInstance()->get('database_cache_max_rows');
    if (isset($max_rows_settings['bins']['depcalc'])) {
      return $max_rows_settings['bins']['depcalc'];
    }
    if (isset($max_rows_settings['default'])) {
      return $max_rows_settings['default'];
    }
    return DatabaseBackend::DEFAULT_MAX_ROWS;
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
    $database = $this->drupalServiceFactory->getDrupalService('database');
    return (int) $database->select('cache_depcalc')->countQuery()->execute()->fetchField();
  }

  /**
   * Determines whether an entity is eligible for export or not.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   The entity in question.
   *
   * @return bool
   *   TRUE if it is eligible.
   */
  protected function isEligible(EntityTypeInterface $entityType) {
    return $entityType instanceof ContentEntityTypeInterface &&
      !in_array($entityType->id(), static::EXCLUDED_CONTENT_ENTITIES);
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
