<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use Drupal\Core\Config\Entity\ConfigEntityType;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubLayoutBuilderDefaults.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubLayoutBuilderDefaults extends ContentHubCommandBase implements  PlatformBootStrapCommandInterface {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:layout-builder-defaults';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Checks Layout Builder defaults usage.');
    $this->setAliases(['ach-lbd']);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    if (!\Drupal::moduleHandler()->moduleExists('layout_builder')) {
      $output->writeln('Layout Builder not installed.');
      return 0;
    }

    $output->writeln('Looking for Layout Builder defaults usage...');
    $problematic_types = [];

    $entity_types = \Drupal::entityTypeManager()->getDefinitions();
    $bundle_info_manager = \Drupal::service('entity_type.bundle.info');
    $view_mode_repository = \Drupal::service('entity_display.repository');

    foreach($entity_types as $entity_type) {
      if ($entity_type instanceof ConfigEntityType) {
        continue;
      }

      $entity_type_id = $entity_type->id();
      foreach (array_keys($bundle_info_manager->getBundleInfo($entity_type_id)) as $bundle) {
        $view_modes = $view_mode_repository->getViewModes($entity_type_id);
        $modes = array_keys($view_modes);

        // Default view mode not get listed, must add manually
        $modes[] = 'default';
        foreach ($modes as $mode) {
          $view_mode_config = \Drupal::configFactory()
            ->getEditable("core.entity_view_display.$entity_type_id.$bundle.$mode");

          if ($view_mode_config->get('third_party_settings.layout_builder.enabled')) {
            $problematic_types[] = [
              $entity_type_id,
              $bundle,
              $mode,
            ];
          }
        }
      }
    }

    if (empty($problematic_types)) {
      $output->writeln('No Layout Builder defaults used in your instance. You may proceed!');
      return 0;
    }

    $output->writeln('<comment>The following table contains information about Layout Builder default usage!</comment>');
    $output->writeln('<comment>Layout builder defaults currently not supported by ACH 2.x version.</comment>');

    $table = new Table($output);
    $table->setHeaders(['Entity type', 'Bundle', 'View mode']);
    $table->addRows($problematic_types);
    $table->render();

    return 0;
  }

}
