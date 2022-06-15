<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

use Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Runs a check against possibly unsupported entity types.
 */
class ContentHubPqEntityStructure extends ContentHubPqCommandBase {

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
    $fieldData = $this->getFieldTypes($bundles);
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
      $bundleIds[] = $bundle->id();
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
   */
  public function getFieldTypes(array $bundles): array {
    $fieldData = [];
    // @todo Change this to be dynamic in Phase 2.
    $entity_type = 'node';
    /** @var \Drupal\Core\Entity\EntityFieldManager $fieldManager */
    $fieldManager = \Drupal::service('entity_field.manager');
    foreach ($bundles as $bundle) {
      $fieldDefinitions = $fieldManager->getFieldDefinitions($entity_type, $bundle);
      $data = [];
      foreach ($fieldDefinitions as $fieldName => $fieldDefinition) {
        $fieldType = $fieldDefinition->getType();
        if ($fieldType === 'entity_reference' || $fieldType === 'entity_reference_revisions') {
          $data[$fieldType]['count']++;
          $settings = $fieldDefinition->getSettings();
          $handler = $settings['handler'];
          // Default handler means it's a standard field with
          // no target configuration for fields like uid, revision uid etc
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

}
