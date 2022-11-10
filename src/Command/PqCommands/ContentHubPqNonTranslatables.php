<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

use Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Runs check against non-translatable entities.
 */
class ContentHubPqNonTranslatables extends ContentHubPqCommandBase {

  /**
   * The Drupal service factory service.
   *
   * @var \Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory
   */
  protected $serviceFactory;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:pq:non-translatables';

  /**
   * Constructs a new ContentHubPqNonTranslatables object.
   *
   * @param \Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory $drupalServiceFactory
   *   The Drupal service factory service.
   * @param string|null $name
   *   The name of this command.
   *
   * @throws \Exception
   */
  public function __construct(DrupalServiceFactory $drupalServiceFactory, string $name = NULL) {
    parent::__construct($name);
    $this->serviceFactory = $drupalServiceFactory;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this->setDescription('Analyses non-translatable antities and entity types.');
    $this->setAliases(['ach-pq-nt']);
    $this->addOption('entity-type', 'e', InputOption::VALUE_OPTIONAL, 'Run checks for the provided entity type. Example node,user,paragraph', 'node');
    $this->addUsage('ach:pq:non-translatables --entity-type "node,user,paragraph"');
  }

  /**
   * {@inheritDoc}
   *
   * @throws \Exception
   */
  protected function runCommand(InputInterface $input, PqCommandResult $result): int {
    $entityTypeOption = $input->getOption('entity-type');
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $etm */
    $etm = $this->serviceFactory->getDrupalService('entity_type.manager');
    $entityTypes = explode(',', $entityTypeOption);
    foreach ($entityTypes as $entityType) {
//      $entityTypeLabel = $this->getEntityTypeLabel($entityType, $etm);
      $bundles = $this->getBundles($entityType, $etm);
      $kriName = 'Non-translatable bundles';
      if (empty($bundles)) {
        $result->setIndicator(
          $kriName,
          '',
          'No non-translatable entities detected.'
        );
        return 0;
      }
      $entities = $this->loadEntities($etm, $entityType);
      $nonTranslatables = [];
      foreach ($entities as $entity) {
        if (!$entity->isTranslatable()) {
          $nonTranslatables[$entity->bundle()]['count']++;
        }
      }
      $formatted = [];
      foreach ($nonTranslatables as $bundleId => $bundle) {
        $formatString = $this->toRed('%s: %s');
        $formatted[] = sprintf($formatString, $bundles[$bundleId], $bundle['count']);
      }

      $kriMessage = !empty($formatted) ? PqCommandResultViolations::$asymmetricParagraphs : 'Content structure is fine, safe to proceed';
      $result->setIndicator(
        $kriName,
        implode("\n", $formatted),
        $kriMessage,
        !empty($formatted)
      );
    }

    return 0;
  }

  /**
   * Loads all the paragraphs and returns them.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $etm
   *   Entity type manager.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   Paragraph entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function loadEntities(EntityTypeManagerInterface $etm, string $entityType): array {
    $entity_storage = $etm->getStorage($entityType);
    if (is_null($entity_storage)) {
      return [];
    }
    return $entity_storage->loadByProperties();
  }

  /**
   * Returns bundles for entity type.
   *
   * @param string $entityType
   *   Entity type id.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $etm
   *   Entity type manager.
   *
   * @return array
   *   The list of bundles.
   *
   * @throws \Exception
   */
  public function getBundles(string $entityType, EntityTypeManagerInterface $etm): array {
    $bundleIds = [];
    $entityDefinition = $etm->getDefinition($entityType, FALSE);
    if (!$entityDefinition) {
      return $bundleIds;
    }
    $bundleStorage = $entityDefinition->getBundleEntityType();

    $bundles = $etm->getStorage($bundleStorage)->loadMultiple();
    foreach ($bundles as $bundle) {
      $bundleIds[$bundle->id()] = $bundle->label();
    }
    return $bundleIds;
  }

}
