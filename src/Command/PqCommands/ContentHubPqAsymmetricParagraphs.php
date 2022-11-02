<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

use Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Runs check against paragraphs which could be asymmetric and lists them down.
 */
class ContentHubPqAsymmetricParagraphs extends ContentHubPqCommandBase {

  /**
   * Paragraph storage.
   */
  const PARAGRAPH_STORAGE = 'paragraph';

  /**
   * The Drupal service factory service.
   *
   * @var \Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory
   */
  protected $serviceFactory;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:pq:asymmetric-paragraphs';

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
    $this->setDescription('Analyses paragraphs and checks whether they are asymmetric based on site\'s default language.');
  }

  /**
   * {@inheritDoc}
   */
  protected function runCommand(InputInterface $input, PqCommandResult $result): int {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $etm */
    $etm = $this->serviceFactory->getDrupalService('entity_type.manager');
    $bundles = $this->getBundles(self::PARAGRAPH_STORAGE, $etm);

    /** @var \Drupal\Core\Language\LanguageManagerInterface $languageManager */
    $languageManager = $this->serviceFactory->getDrupalService('language_manager');
    $defaultLanguage = $languageManager->getDefaultLanguage()->getId();
    $paragraphs = $this->getParagraphs($etm);
    $asymmetricParagraphs = [];
    foreach ($paragraphs as $paragraph) {
      $languages = array_keys($paragraph->getTranslationLanguages());
      if (in_array($defaultLanguage, $languages)) {
        continue;
      }
      $asymmetricParagraphs[$paragraph->bundle()]['count']++;
    }
    $kriName = 'Asymmetric Paragraphs';
    $formatted = [];
    foreach ($asymmetricParagraphs as $bundleId => $bundle) {
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
    if (!$bundleStorage) {
      return $bundleIds;
    }

    $bundles = $etm->getStorage($bundleStorage)->loadMultiple();
    foreach ($bundles as $bundle) {
      $bundleIds[$bundle->id()] = $bundle->label();
    }
    return $bundleIds;
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
  protected function getParagraphs(EntityTypeManagerInterface $etm) {
    $paragraph_storage = $etm->getStorage(self::PARAGRAPH_STORAGE);
    // If paragraph storage is null then paragraph module is not installed.
    if (is_null($paragraph_storage)) {
      return [];
    }
    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $paragraphs */
    return $paragraph_storage->loadByProperties();
  }

}
