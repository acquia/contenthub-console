<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

use Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Runs check against single translation entities.
 */
class ContentHubPqDiscoverTranslations extends ContentHubPqCommandBase {

  /**
   * The KRI message for descovering translations..
   */
  public const KRI_MESSAGE = 'There are single translation entites for this entity type,' . PHP_EOL
  . 'this can lead to enabling of language on susbscriber site.';

  /**
   * The Drupal service factory service.
   *
   * @var \Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory
   */
  protected $serviceFactory;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:pq:discover-translations';

  /**
   * Constructs a new ContentHubPqDiscoverTranslations object.
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
    $this->setDescription('Analyzes single translations entities and bundles.');
    $this->setAliases(['ach-pq-dt']);
    $this->addOption('entity-type', 'e', InputOption::VALUE_OPTIONAL, 'Run checks for the provided content entity type. Example node,user,paragraph');
    $this->addUsage('ach:pq:discover-translations --entity-type "node,paragraph"');
  }

  /**
   * {@inheritDoc}
   *
   * @throws \Exception
   */
  protected function runCommand(InputInterface $input, PqCommandResult $result): int {
    /** @var \Drupal\Core\Language\LanguageManagerInterface $languageManager */
    $languageManager = $this->serviceFactory->getDrupalService('language_manager');
    $languages = $languageManager->getLanguages();
    if (count($languages) < 2) {
      $result->setIndicator(
        'Single translated entities',
        '',
        'No multiple languages detected.'
      );
      return 0;
    }

    $entityTypeOption = $input->getOption('entity-type');
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $etm */
    $etm = $this->serviceFactory->getDrupalService('entity_type.manager');
    /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfo */
    $bundleInfo = $this->serviceFactory->getDrupalService('entity_type.bundle.info');
    $entityTypeDefinitions = $etm->getDefinitions();
    $entityTypes = empty($entityTypeOption) ? array_keys($entityTypeDefinitions) : explode(',', $entityTypeOption);
    foreach ($entityTypes as $entityType) {
      // Skip config entity types.
      if (array_key_exists($entityType, $entityTypeDefinitions) && $entityTypeDefinitions[$entityType]->entityClassImplements(ConfigEntityInterface::class)) {
        continue;
      }
      $entityTypeLabel = $this->getEntityTypeLabel($entityType, $etm);
      $bundles = $this->getBundles($entityType, $bundleInfo);
      $kriName = 'Content Entity Type: ' . $entityTypeLabel;
      if (empty($bundles)) {
        $result->setIndicator(
          $kriName,
          'Defauld language:' . $languageManager->getDefaultLanguage()->getId(),
          'No non-translatable entities detected.'
        );
        continue;
      }

      $entities = $this->loadEntities($etm, $entityType);
      $singleTranslation = [];
      foreach ($entities as $entity) {
        if ($entity->isTranslatable() && count($entity->getTranslationLanguages()) === 1) {
          $singleTranslation[$entity->bundle()]['langcode'] = $entity->language()->getId();
        }
      }

      $formatted = [];
      foreach ($singleTranslation as $bundleId => $bundle) {
        $formatString = $this->toYellow('%s: %s');
        $formatted[] = sprintf($formatString, $bundles[$bundleId]['label'], $bundle['langcode']);
      }

      $kriMessage = !empty($formatted) ? self::KRI_MESSAGE : 'No single-translation entities.';
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
   * Returns bundles for entity type.
   *
   * @param string $entityType
   *   Entity type id.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfo
   *   Bundle information.
   *
   * @return array
   *   The list of bundles.
   *
   * @throws \Exception
   */
  public function getBundles(string $entityType, EntityTypeBundleInfoInterface $bundleInfo): array {
    $bundleIds = [];
    $bundles = $bundleInfo->getBundleInfo($entityType);
    foreach ($bundles as $bundleId => $bundle) {
      $bundleIds[$bundleId]['label'] = $bundle['label'];
      $bundleIds[$bundleId]['translatable'] = $bundle['translatable'];
    }
    return $bundleIds;
  }

  /**
   * Returns entity type label.
   *
   * @param string $entityType
   *   Entity type id.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   *
   * @return string
   *   Entity type label.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntityTypeLabel(string $entityType, EntityTypeManagerInterface $entityTypeManager): string {
    $entityDefinition = $entityTypeManager->getDefinition($entityType);
    return !$entityDefinition ? '' : $entityDefinition->getLabel()->__toString();
  }

  /**
   * Loads all the entities and returns them.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $etm
   *   Entity type manager.
   * @param string $entityType
   *   The entity type.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   Entities of given entity type.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function loadEntities(EntityTypeManagerInterface $etm, string $entityType): array {
    $entity_storage = $etm->getStorage($entityType);
    return $entity_storage->loadByProperties();
  }

}
