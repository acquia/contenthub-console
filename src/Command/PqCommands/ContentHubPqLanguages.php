<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

use Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Checks available languages, translation count and risky paragraph fields.
 */
class ContentHubPqLanguages extends ContentHubPqCommandBase {

  /**
   * Paragraph field type.
   */
  public const PARAGRAPH_FIELD = 'paragraph';

  /**
   * Entity reference revision field type.
   */
  public const ENTITY_REF_REV = 'entity_reference_revisions';

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
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this->setDescription('Checks available languages, translation count and risky paragraph fields.');
  }

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
    $kriName = 'List of enabled languages and respective translation count.';
    if (!empty($languageData)) {
      $kriValue = '';
      foreach ($languageData as $langCode => $count) {
        $kriValue .= sprintf('%s: %s', $langCode, $count) . PHP_EOL;
      }
      $result->setIndicator(
        $kriName,
        trim($kriValue),
        'Multiple enabled languages and translations increase dependency count.',
      );
    }

    $incorrectParaConfigFields = $this->checkMultilingualParagraphConfiguredCorrect();
    $kriName = 'List of incorrectly configured referenced paragraph fields.';
    if (!empty($incorrectParaConfigFields)) {
      $kriValue = '';
      foreach ($incorrectParaConfigFields as $incorrectParaFields) {
        $kriValue .= $incorrectParaFields . PHP_EOL;
      }
      $result->setIndicator(
        $kriName,
        trim($kriValue),
        PqCommandResultViolations::$paragraphConfiguration,
        TRUE,
      );
    }
    else {
      $result->setIndicator(
      $kriName,
      '',
      'No incorrectly configured referenced paragraph fields.'
      );
    }

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
    $languages = $languageManager->getLanguages();
    $langcodeList = array_keys($languages);

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
    $entityTypeManager = $this->drupalServiceFactory->getDrupalService('entity_type.manager');
    /** @var \Drupal\Core\Database\Connection $databaseManager */
    $databaseConnection = $this->drupalServiceFactory->getDrupalService('database');

    $entityTypes = $entityTypeManager->getDefinitions();
    foreach ($entityTypes as $type) {
      if ($type->entityClassImplements(ContentEntityInterface::class) && $type->isTranslatable()) {
        foreach ($langcodeList as $langCode) {
          $count = $databaseConnection->select($type->getDataTable())
            ->condition('langcode', $langCode)
            ->countQuery()
            ->execute()
            ->fetchField();
          $languagesData[$languages[$langCode]->getName()] += $count;
        }
      }
    }
    return $languagesData;
  }

  /**
   * Checks if multilingual paragraphs are configured properly.
   *
   * @return array
   *   Array of incorrect configured paragraph fields
   */
  protected function checkMultilingualParagraphConfiguredCorrect(): array {
    /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfo */
    $bundleInfo = $this->drupalServiceFactory->getDrupalService('entity_type.bundle.info');
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $fieldManager */
    $fieldManager = $this->drupalServiceFactory->getDrupalService('entity_field.manager');

    $configuration = [];
    $fieldMap = $fieldManager->getFieldMapByFieldType(self::ENTITY_REF_REV);
    foreach ($fieldMap as $type => $fields) {
      $sourceBundles = $bundleInfo->getBundleInfo($type);
      foreach ($fields as $fieldName => $field) {
        /** @var \Drupal\field\FieldStorageConfigInterface $config */
        $config = FieldStorageConfig::loadByName($type, $fieldName);
        $bundles = array_keys($field['bundles']);
        if (!empty($config) && $config->getSetting('target_type') === self::PARAGRAPH_FIELD && !empty($bundles)) {
          foreach ($bundles as $bundle) {
            if (!$sourceBundles[$bundle]['translatable']) {
              continue;
            }
            /** @var \Drupal\Core\Field\FieldDefinitionInterface $fieldDef */
            $fieldDef = $fieldManager->getFieldDefinitions($type, $bundle)[$fieldName];
            // Referencing field of paragraph should not be translatable.
            if ($fieldDef->isTranslatable()) {
              $configuration[] = $type . ':' . $bundle . ':' . $fieldName;
            }
          }
        }
      }
    }
    return $configuration;
  }

}
