<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

use Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Runs a check against possibly unsupported entity types.
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
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function runCommand(InputInterface $input, PqCommandResult $result): int {
    $bundles = $this->getNodeBundles();
    $fieldData = $this->getFieldTypes(array_keys($bundles));
    $kriName = 'Risky Content Types';
    $riskyBundles = $this->analyseFieldData($bundles, $fieldData);

    $formatted = [];
    foreach ($fieldData as $bundleId => $bundleData) {
      $formatted[$bundleData['complex_fields']['count'] ?? 0] = sprintf('%s: %s ', $bundles[$bundleId], $bundleData['complex_fields']['count'] ?? 0);
    }
    ksort($formatted);

    $formatted = array_merge(['Content Type: Number of Complex fields'], $formatted);

    $kriMessage = PqCommandResultViolations::$riskyBundles;
    $result->setIndicator(
      $kriName,
      implode("\n", $formatted),
      $kriMessage
    );

    if (!empty($riskyBundles)) {
      $result->setIndicator(
        'Content types with paragraphs',
        implode("\n", $riskyBundles),
        PqCommandResultViolations::$paragraphBundles,
        TRUE
      );
    }
    return 0;

  }

  /**
   * Returns node bundles.
   *
   * @return array
   *   The list of bundle ids.
   *
   * @throws \Exception
   */
  public function getNodeBundles(): array {
    $bundleIds = [];
    /** @var \Drupal\Core\Entity\EntityTypeManager $etm */
    $etm = $this->serviceFactory->getDrupalService('entity_type.manager');
    $bundles = $etm->getStorage('node_type')->loadMultiple();
    foreach ($bundles as $bundle) {
      $bundleIds[$bundle->id()] = $bundle->label();
    }
    return $bundleIds;
  }

  /**
   * Returns list of fields for each bundle.
   *
   * @param array $bundles
   *   Array of bundle ids.
   *
   * @return array
   *   Array containing field info for each bundle.
   *   ['article' => [], 'page' => []].
   *
   * @throws \Exception
   */
  public function getFieldTypes(array $bundles): array {
    $fieldData = [];
    // @todo Change this to be dynamic in Phase 2.
    $entity_type = 'node';
    /** @var \Drupal\Core\Entity\EntityFieldManager $fieldManager */
    $fieldManager = $this->serviceFactory->getDrupalService('entity_field.manager');
    foreach ($bundles as $bundle) {
      $fieldDefinitions = $fieldManager->getFieldDefinitions($entity_type, $bundle);
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
            $target_entity = $settings['target_type'];
            $target_bundles = array_keys($settings['handler_settings']['target_bundles']);
            $data[$fieldType][$fieldName]['target_entity'] = $target_entity;
            $data[$fieldType][$fieldName]['target_bundles'] = $target_bundles;
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

}
