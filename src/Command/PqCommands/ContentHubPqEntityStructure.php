<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

use Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Runs a check against entity types with possibly complex structure.
 */
class ContentHubPqEntityStructure extends ContentHubPqCommandBase {

  /**
   * Paragraph field type.
   */
  public const PARAGRAPH_FIELD = 'paragraph';

  /**
   * Entity reference field type.
   */
  public const ENTITY_REF = 'entity_reference';

  /**
   * Entity reference revision field type.
   */
  public const ENTITY_REF_REV = 'entity_reference_revisions';

  /**
   * Formatted text field types.
   */
  public const FORMATTED_TEXT_FIELDS = [
    'text_with_summary',
    'text',
    'text_long',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:pq:entity-structure';

  /**
   * The Drupal service factory service.
   *
   * @var \Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory
   */
  protected $serviceFactory;

  /**
   * Constructs a new ContentHubPqEntityTypes object.
   *
   * @param \Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory $drupalServiceFactory
   *   The Drupal service factory service.
   * @param string|null $name
   *   The name of this command.
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
    $this->setDescription('Analyses content type structure and sorts bundles based on their complexity.');
    $this->addOption('entity-type', 'e', InputOption::VALUE_OPTIONAL, 'Run checks for the provided entity type. Example node,user,paragraph', 'node');
    $this->addUsage('ach:pq:dependencies --entity-type "node,user,paragraph"');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function runCommand(InputInterface $input, PqCommandResult $result): int {
    $entityTypeOption = $input->getOption('entity-type');
    $etm = $this->serviceFactory->getDrupalService('entity_type.manager');
    $entityTypes = explode(',', $entityTypeOption);
    foreach ($entityTypes as $entityType) {
      $entityTypeLabel = $this->getEntityTypeLabel($entityType, $etm);
      $bundles = $this->getBundles($entityType, $etm);
      // Entity type itself acts as a bundle
      // in this case to get field definition.
      if (empty($bundles)) {
        $bundles[$entityType] = $entityTypeLabel;
      }
      $fieldData = $this->getFieldTypes($entityType, array_keys($bundles));
      $kriName = 'Content Type:' . $entityTypeLabel;
      $riskyBundles = $this->analyseFieldData($bundles, $fieldData);

      $formatted = [];
      foreach ($fieldData as $bundleId => $bundleData) {
        $formatted[$bundleData['complex_fields']['count'] ?? 0] = sprintf('%s: %s ', $bundles[$bundleId], $bundleData['complex_fields']['count'] ?? 0);
      }
      ksort($formatted);

      $kriMessage = !empty($formatted) ? PqCommandResultViolations::$riskyBundles : 'Content structure is fine, safe to proceed';
      $result->setIndicator(
        $kriName,
        implode("\n", $formatted),
        $kriMessage
      );

      if (!empty($riskyBundles)) {
        $result->setIndicator(
          'Content type:' . $entityTypeLabel . ' with paragraphs',
          implode("\n", $riskyBundles),
          PqCommandResultViolations::$paragraphBundles,
          TRUE
        );
      }
    }
    return 0;

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
    $entityDefinition = $etm->getDefinition($entityType);
    $bundleStorage = $entityDefinition->getBundleEntityType();
    if ($bundleStorage) {
      $bundles = $etm->getStorage($bundleStorage)->loadMultiple();
      foreach ($bundles as $bundle) {
        $bundleIds[$bundle->id()] = $bundle->label();
      }
    }
    return $bundleIds;
  }

  /**
   * Returns list of fields for each bundle.
   *
   * @param string $entityType
   *   Entity type id.
   * @param array $bundles
   *   Array of bundle ids.
   *
   * @return array
   *   Array containing field info for each bundle.
   *   ['article' => [], 'page' => []].
   *
   * @throws \Exception
   */
  public function getFieldTypes(string $entityType, array $bundles): array {
    $fieldData = [];
    /** @var \Drupal\Core\Entity\EntityFieldManager $fieldManager */
    $fieldManager = $this->serviceFactory->getDrupalService('entity_field.manager');
    foreach ($bundles as $bundle) {
      $fieldDefinitions = $fieldManager->getFieldDefinitions($entityType, $bundle);
      $data = [];
      foreach ($fieldDefinitions as $fieldName => $fieldDefinition) {
        $fieldType = $fieldDefinition->getType();
        if ($fieldType === self::ENTITY_REF || $fieldType === self::ENTITY_REF_REV) {
          $data[$fieldType]['count']++;
          $settings = $fieldDefinition->getSettings();
          $handler = $settings['handler'];
          // Default handler means it's a standard field with
          // no target configuration for fields
          // e.g. uid, revision uid etc.
          // which means it's not risky.
          if ($handler !== 'default') {
            $targetEntity = $settings['target_type'];
            $targetBundles = array_keys($settings['handler_settings']['target_bundles']);
            $data[$fieldType][$fieldName]['target_entity'] = $targetEntity;
            $data[$fieldType][$fieldName]['target_bundles'] = $targetBundles;
          }
          continue;
        }
        if (in_array($fieldDefinition->getType(), self::FORMATTED_TEXT_FIELDS, TRUE)) {
          $data['formatted_text_fields']['count']++;
        }
      }
      $fieldData[$bundle] = $data;
    }
    return $fieldData;
  }

  /**
   * Analyses field data for each bundle and identifies risky ones.
   *
   * @param array $bundles
   *   Bundle list.
   * @param array $fieldData
   *   Array of field data keyed by bundle id.
   *
   * @return array
   *   List of bundles which are risky.
   */
  protected function analyseFieldData(array $bundles, array &$fieldData): array {
    $riskyBundles = [];
    foreach ($bundles as $bundleId => $bundleLabel) {
      $bundleData = $fieldData[$bundleId];
      $entityRefFieldData = $bundleData[self::ENTITY_REF] ?? [];
      $this->checkRiskiness($riskyBundles, $fieldData, $entityRefFieldData, $bundleId, $bundleLabel);
      $entityRefRevFieldData = $bundleData[self::ENTITY_REF_REV] ?? [];
      $this->checkRiskiness($riskyBundles, $fieldData, $entityRefRevFieldData, $bundleId, $bundleLabel);
    }
    return $riskyBundles;
  }

  /**
   * Iterates over each field and checks whether bundle is risky.
   *
   * @param array $riskyBundles
   *   Array of risky bundles.
   * @param array $fieldData
   *   Overall field data for all bundles.
   * @param array $entityFieldData
   *   Field data for corresponding entity ref or entity ref rev field.
   * @param string $bundleId
   *   Bundle Id.
   * @param string $bundleLabel
   *   Bundle Label.
   */
  protected function checkRiskiness(array &$riskyBundles, array &$fieldData, array $entityFieldData, string $bundleId, string $bundleLabel): void {
    $entityFieldCount = $entityFieldData ? $entityFieldData['count'] : 0;
    if ($entityFieldCount > 0) {
      unset($entityFieldData['count']);
      $complexEntityFieldsExist = $entityFieldData ? count($entityFieldData) : 0;
      if ($complexEntityFieldsExist > 0) {
        foreach ($entityFieldData as $entityField) {
          $fieldData[$bundleId]['complex_fields']['count']++;
          // If a bundle has a paragraph field which can
          // ultimately increase complexity.
          if ($entityField['target_entity'] === self::PARAGRAPH_FIELD) {
            $riskyBundles[$bundleId] = $bundleLabel;
          }
        }
      }
    }
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
    if ($entityDefinition) {
      return $entityDefinition->getLabel()->__toString();
    }
    return '';
  }

}
