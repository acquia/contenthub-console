<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

use Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory;
use Drupal\Core\Cache\DatabaseBackend;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\depcalc\DependencyStack;
use Drupal\depcalc\DependentEntityWrapper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

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
   * @var \Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory
   */
  protected $drupalServiceFactory;

  /**
   * Contains the parsed input if there were passed any.
   *
   * Structure: entity_type => [bundle1, bundle2].
   *
   * @var array
   */
  private $entityTypesFilter = [];

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
    $this->addOption('entity-type', 'e', InputOption::VALUE_OPTIONAL, 'Run checks only for the provided entity type. Specify bundle if needed. Example: {entity_type}:{bundle},{entity_type2}.', '');
    $this->addUsage('ach:pq:dependencies --entity-type "node:article,node:page,block_content"');
  }

  /**
   * {@inheritdoc}
   */
  protected function runCommand(InputInterface $input, PqCommandResult $result): int {
    if (!$this->depcalcModuleIsEnabled()) {
      throw ContentHubPqCommandErrors::newException(ContentHubPqCommandErrors::$depcalcIsNotEnabledError);
    }

    $this->entityTypesFilter = $this->parseInput($input);

    $contentEntities = $this->getExportEligibleContentEntities();
    $contentEntityCount = $this->countEligibleEntities($contentEntities);
    $formatted = [];
    foreach ($contentEntityCount as $key => $val) {
      $formatted[] = sprintf('%s: %s ', $key, $val);
    }
    $result->setIndicator(
      'Export Eligible Content Entities',
      implode("\n", $formatted),
      'The following entities and their count should be expected to be exported. The list does not contain dependencies. Make sure to only export desired entities.'
    );

    $depCount = $this->calculateDependenciesForAllEligibleContentEntities($contentEntities);
    $messages = [
      'positive' => 'The number of eligible dependencies. The overall entity count that would be exported, content and config entities included.',
      'negative' => PqCommandResultViolations::$dependencyCount,
    ];
    $high = $depCount >= 5000;
    $result->setIndicator(
      'Dependencies',
      $depCount,
      $high ? $messages['negative'] : $messages['positive'],
      $high
    );

    $maxRows = $this->getMaxRowsForDepcalcCache();

    $rowsShouldBeIncreased = $maxRows !== DatabaseBackend::MAXIMUM_NONE && $depCount >= $maxRows;
    $messages = [
      'positive' => 'The configured maximum number of depcalc cache bin rows is sufficient.',
      'negative' => PqCommandResultViolations::$depcalcCacheMaxRows,
    ];
    $result->setIndicator(
      'Depcalc Cache Bin Max Rows',
      $maxRows,
      $rowsShouldBeIncreased ? $messages['negative'] : $messages['positive'],
      $rowsShouldBeIncreased
    );

    return 0;
  }

  /**
   * Returns an array of all export eligible content entities.
   *
   * @return array
   *   A list of eligible entities.
   *
   * @throws \Exception
   */
  public function getExportEligibleContentEntities(): array {
    $entityTypeManager = $this->drupalServiceFactory->getDrupalService('entity_type.manager');
    $entities = [];
    foreach ($entityTypeManager->getDefinitions() as $entityType) {
      if (!$this->isEligible($entityType)) {
        continue;
      }
      $entities[] = $entityType;
    }

    return $entities;
  }

  /**
   * Counts eligible content entities.
   *
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface[] $entities
   *   The eligible entities to count.
   *
   * @return array
   *   The entities with their respective amount: entity => amount.
   *
   * @throws \Acquia\Console\ContentHub\Command\PqCommands\PqCommandException
   */
  public function countEligibleEntities(array $entities): array {
    $bundleInfo = $this->drupalServiceFactory->getDrupalService('entity_type.bundle.info');
    $entityTypeManager = $this->drupalServiceFactory->getDrupalService('entity_type.manager');
    $count = [];
    foreach ($entities as $entityType) {
      $id = $entityType->id();
      $count[$id] = $this->countEntitiesWithBundleFilter($bundleInfo, $entityTypeManager, $id);
    }
    return $count;
  }

  /**
   * Calculates dependencies of contents that are eligible for export.
   *
   * This will take a bit of time.
   *
   * @param \Drupal\Core\Entity\ContentEntityTypeInterface[] $entities
   *   The eligible entities to count.
   *
   * @return int
   *   The number of dependencies calculated.
   *
   * @throws \Exception
   */
  public function calculateDependenciesForAllEligibleContentEntities(array $entities): int {
    $depcalc = $this->drupalServiceFactory->getDrupalService('entity.dependency.calculator');
    $entityTypeManager = $this->drupalServiceFactory->getDrupalService('entity_type.manager');
    $bundleInfo = $this->drupalServiceFactory->getDrupalService('entity_type.bundle.info');
    $this->clearDepcalcCache();

    foreach ($entities as $entityType) {
      $id = $entityType->id();
      if (!$this->bundleFilterIsSet($id)) {
        $entities = $entityTypeManager->getStorage($id)->loadByProperties();
      }
      else {
        $this->validateBundles($id, $bundleInfo);
        $entities = $entityTypeManager->getStorage($id)->loadByProperties(['type' => $this->entityTypesFilter[$id]]);
      }

      $stack = new DependencyStack();
      foreach ($entities as $entity) {
        $wrapper = new DependentEntityWrapper($entity);
        $depcalc->calculateDependencies($wrapper, $stack);
      }
    }

    return $this->getDependencyNumberFromDepcalcCache();
  }

  /**
   * Counts the number of entities based on the provided bundle filters.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfo
   *   The bundle info service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param string $id
   *   The entity type id.
   *
   * @return int
   *   The number of available entities.
   *
   * @throws \Acquia\Console\ContentHub\Command\PqCommands\PqCommandException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function countEntitiesWithBundleFilter(EntityTypeBundleInfoInterface $bundleInfo, EntityTypeManagerInterface $entityTypeManager, string $id): int {
    if (!$this->bundleFilterIsSet($id)) {
      return $entityTypeManager
        ->getStorage($id)
        ->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->execute();
    }

    $this->validateBundles($id, $bundleInfo);

    return $entityTypeManager
      ->getStorage($id)
      ->getQuery()
      ->condition('type', $this->entityTypesFilter[$id], 'IN')
      ->accessCheck(FALSE)
      ->count()
      ->execute();
  }

  /**
   * Parses user input for entity-type option.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input object containing the user input.
   *
   * @return array
   *   The parsed input: entity_type => [bundle1, bundle2].
   */
  public function parseInput(InputInterface $input): array {
    $entityTypesInput = $input->getOption('entity-type');
    if (!$entityTypesInput) {
      return [];
    }

    $parsed = [];
    $entityTypes = explode(',', $entityTypesInput);
    foreach ($entityTypes as $entityType) {
      $typeAndBundle = explode(':', $entityType);
      if (!isset($typeAndBundle[1]) && !isset($parsed[$typeAndBundle[0]])) {
        $parsed[$typeAndBundle[0]] = [];
        continue;
      }
      $parsed[$typeAndBundle[0]][] = $typeAndBundle[1];
    }
    return $parsed;
  }

  /**
   * Checks if a bundle is set for the entity based on the user input.
   *
   * @param string $id
   *   The entity type id.
   *
   * @return bool
   *   TRUE if set.
   */
  protected function bundleFilterIsSet(string $id): bool {
    return isset($this->entityTypesFilter[$id]) && !empty($this->entityTypesFilter[$id]);
  }

  /**
   * Validates the filterable bundles against existing ones.
   *
   * @param string $id
   *   The entity type id.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfo
   *   The bundle info service.
   *
   * @throws \Acquia\Console\ContentHub\Command\PqCommands\PqCommandException
   */
  protected function validateBundles(string $id, EntityTypeBundleInfoInterface $bundleInfo): void {
    $bundles = array_keys($bundleInfo->getBundleInfo($id));
    $missing = array_diff($this->entityTypesFilter[$id], $bundles);
    if (!empty($missing)) {
      throw ContentHubPqCommandErrors::newException(
        ContentHubPqCommandErrors::$bundleDoesNotExistErrorWithContext,
        [implode(', ', $missing), $id]
      );
    }
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
   * Clears depcalc cache table.
   *
   * @throws \Exception
   */
  protected function clearDepcalcCache(): void {
    $cache = $this->drupalServiceFactory->getDrupalService('cache.depcalc');
    $cache->deleteAllPermanent();
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
  protected function isEligible(EntityTypeInterface $entityType): bool {
    if (!$entityType instanceof ContentEntityTypeInterface) {
      return FALSE;
    }
    if (in_array($entityType->id(), static::EXCLUDED_CONTENT_ENTITIES)) {
      return FALSE;
    }

    if (!empty($this->entityTypesFilter)) {
      return in_array($entityType->id(), array_keys($this->entityTypesFilter));
    }

    return TRUE;
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
