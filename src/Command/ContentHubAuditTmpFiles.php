<?php

namespace Acquia\Console\ContentHub\Command;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubAuditTmpFiles.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubAuditTmpFiles extends Command implements PlatformBootStrapCommandInterface {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:audit:tmp-files';

  /**
   * {@inheritdoc}
   */
  public function getPlatformBootstrapType(): string {
    return 'drupal8';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Audit an existing site to determine ContentHub concerning temporary files.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $files = $this->getTempFiles(\Drupal::entityTypeManager());
    if (!$files) {
      $output->writeln('Files are safe to export.');
      return;
    }

    $output->writeln('<bg=yellow;options=bold>The following files are registered as temporary:</>');
    $table = new Table($output);
    $table->setHeaders(['ID', 'Name', 'Uri']);
    $table->addRows($files);
    $table->render();
  }
  
  /**
   * Returns temporary files with status of 0.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *
   * @return array
   *   The array of temporary files.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getTempFiles(EntityTypeManagerInterface $entity_type_manager): array {
    $storage = $entity_type_manager->getStorage('file');
    $query = $storage->getQuery();
    $query->condition('status', 0, '=');
    $result = $query->execute();
    if (empty($result)) {
      return [];
    }

    $files = [];
    foreach ($result as $id) {
      /** @var \Drupal\file\FileInterface $file */
      $file = $storage->load($id);
      $files[] = [$file->id(), $file->getFilename(), $file->getFileUri()];
    }

    return $files;
  }

}
