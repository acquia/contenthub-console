<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

use Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
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
    $this->setDescription('Analyzes non-translatable entities and bundles.');
    $this->setAliases(['ach-pq-nt']);
    $this->addOption('entity-type', 'e', InputOption::VALUE_OPTIONAL, 'Run checks for the provided content entity type. Example node,user,paragraph');
    $this->addUsage('ach:pq:non-translatables --entity-type "node,paragraph"');
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
    /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info */
    $bundle_info = $this->serviceFactory->getDrupalService('entity_type.bundle.info');
    $entity_type_definitions = $etm->getDefinitions();
    $entityTypes = empty($entityTypeOption) ? array_keys($entity_type_definitions) : explode(',', $entityTypeOption);
    foreach ($entityTypes as $entityType) {
      // Skip config entity types.
      if (array_key_exists($entityType, $entity_type_definitions) && $entity_type_definitions[$entityType]->entityClassImplements(ConfigEntityInterface::class)) {
        continue;
      }
      $entityTypeLabel = $this->getEntityTypeLabel($entityType, $etm);
      $bundles = $this->getBundles($entityType, $bundle_info);
      $kriName = 'Content Entity Type: ' . $entityTypeLabel;
      if (empty($bundles)) {
        $result->setIndicator(
          $kriName,
          '',
          'No non-translatable entities detected.'
        );
        continue;
      }

      $formatted = [];
      foreach ($bundles as $bundleId => $bundle) {
        if (!$bundle['translatable']) {
          $formatString = $this->toRed('%s: %s');
          $formatted[] = sprintf($formatString, $bundle['label'], 'Non-translatable');
        }
      }
      $kriMessage = !empty($formatted) ? PqCommandResultViolations::$nonTranslatables : 'No non-translatable entities.';
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
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   Bundle information.
   *
   * @return array
   *   The list of bundles.
   *
   * @throws \Exception
   */
  public function getBundles(string $entityType, EntityTypeBundleInfoInterface $bundle_info): array {
    $bundleIds = [];
    $bundles = $bundle_info->getBundleInfo($entityType);
    foreach ($bundles as $bundle_id => $bundle) {
      $bundleIds[$bundle_id]['label'] = $bundle['label'];
      $bundleIds[$bundle_id]['translatable'] = $bundle['translatable'];
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

}
