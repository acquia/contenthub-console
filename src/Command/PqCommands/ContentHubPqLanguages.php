<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

use Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Checks avalable languages and translation count.
 */
class ContentHubPqLanguages extends ContentHubPqCommandBase {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:pq:languages';

  /**
   * The Drupal service factory service.
   *
   * @var \Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory
   */
  protected $drupalServiceFactory;

  /**
   * Constructs a new ContentHubPqLanguages object.
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
  protected function runCommand(InputInterface $input, PqCommandResult $result): int {
    $languageData = $this->getEnabledLanguagesData();
    $kriName = 'List of enabled laguages and respective translation count.';
    if (!empty($languageData)) {
      $kriValue = '';
      foreach ($languageData as $langCode => $count) {
        $kriValue .= sprintf('%s: %s', $langCode, $count) . PHP_EOL;
      }
      $result->setIndicator(
        $kriName,
        trim($kriValue),
        PqCommandResultViolations::$enabledLanguages,
        count($languageData) > 1 ? TRUE : FALSE,
      );
      return 0;
    }

    $result->setIndicator(
      $kriName,
      '',
      'No enabled languages and and trnaslations detected!'
    );
    return 0;
  }

  /**
   * Check enabled languages and translations count.
   *
   * @return array
   *   Array of enabled languages and count of translations.
   */
  protected function getEnabledLanguagesData(): array {
    $languagesData = [];
    /** @var \Drupal\Core\Language\LanguageManagerInterface $languageManager */
    $languageManager = $this->drupalServiceFactory->getDrupalService('language_manager');
    $langcodeList = array_keys($languageManager->getLanguages());

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
    $entityTypeManager = $this->drupalServiceFactory->getDrupalService('entity_type.manager');
    /** @var \Drupal\Core\Database\Connection $databaseManager */
    $databaseConnection = $this->drupalServiceFactory->getDrupalService('database');

    $entityTypes = $entityTypeManager->getDefinitions();
    foreach ($entityTypes as $type) {
      if ($type->entityClassImplements(ContentEntityInterface::class) && $type->isTranslatable() && $type->id() == 'node') {
        $this->isConfiguredCorrect($type);
        foreach ($langcodeList as $langCode) {
          $count = $databaseConnection->select($type->getDataTable())
            ->condition('langcode', $langCode)
            ->countQuery()
            ->execute()
            ->fetchField();
          $languagesData[$langCode] += $count;
        }
      }
    }
    return $languagesData;
  }

  /**
   * @param EntityTypeInterface $entityType
   * @return bool
   */
  protected function isConfiguredCorrect(EntityTypeInterface $entityType): bool {
    /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfo */
    $bundleInfo = $this->drupalServiceFactory->getDrupalService('entity_type.bundle.info');
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $fieldManager */
    $fieldManager = $this->drupalServiceFactory->getDrupalService('entity_field.manager');


    $fieldMap = $fieldManager->getFieldMapByFieldType('entity_reference_revisions');
    foreach ($fieldMap as $type => $fields) {
      foreach ($fields as $fieldName => $field) {
        /** @var \Drupal\field\FieldStorageConfigInterface $config */
        $config = FieldStorageConfig::loadByName($type, $fieldName);
        if (!empty($config) && $config->getSetting('target_type') == 'paragraph') {
          echo 'hi';
        }

      }
    }


//    $entityTypeId = $entityType->id();
//    $bundles = $bundleInfo->getBundleInfo($entityTypeId);
//    foreach ($bundles as $bundleId => $bundle) {
//      if (!$bundle['tarnslatable']) {
//        continue;
//      }
//      /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $fields */
//      $fields = $fieldManager->getFieldDefinitions($entityTypeId, $bundle);
//      $fields['nid']->getLabel();
//    }
//    $entityType;

    return TRUE;
  }
}
