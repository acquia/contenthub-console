<?php

namespace Acquia\Console\ContentHub\Command;

use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubAuditCheckUuid.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubAuditCheckUuid extends Command implements PlatformBootStrapCommandInterface {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:audit:config-uuid';

  /**
   * {@inheritdoc}
   */
  public function configure(): void {
    $this->setDescription('Audit configuration entities and print the ones with missing uuids.');
    $this->addOption('fix', 'f', InputOption::VALUE_NONE, 'Generating uuids.');
    $this->setAliases(['audit-uuid']);
  }

  /**
   * {@inheritdoc}
   */
  public function getPlatformBootstrapType(): string {
    return 'drupal8';
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $output->writeln('Checking configuration entities...');
    try {
      $configs = $this->getConfigEntitiesWithMissingUuid();
    }
    catch (\Exception $e) {
      $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
      return 1;
    }

    if (!$configs) {
      $output->writeln("<info>All of your configuration entities have valid UUIDs.</info>");
      return 0;
    }

    $output->writeln('<bg=yellow;options=bold>The following configuration entities do not have a uuid:</>');
    $table = new Table($output);
    $table->setHeaders(['Config']);
    foreach (array_keys($configs) as $config_id) {
      $table->addRow([$config_id]);
    }
    $table->render();

    if ($input->getOption('fix')) {
      $this->provideUuid($configs);
      $output->writeln('<info>Uuids have been generated for the entities listed above.</info>');
      return 0;
    }
    // Errors were not fixed.
    $output->writeln("<comment>Uuids have not been generated yet. Re-run this command with the '-fix' flag to automatically generate uuids for these entities.</comment>");
    return 1;
  }

  /**
   * Returns config entities with missing uuids.
   *
   * @return \Drupal\Core\Config\Config[]
   *   An array of config entities with missing uuids.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getConfigEntitiesWithMissingUuid(): array {
    $entity_type_manager = \Drupal::entityTypeManager();
    $entity_types = $entity_type_manager->getDefinitions();
    $config_factory = \Drupal::configFactory();
    $configs = [];
    foreach ($entity_types as $entity_type) {
      if (!$entity_type instanceof ConfigEntityTypeInterface) {
        continue;
      }

      $conf_entities = $entity_type_manager->getStorage($entity_type->id())->loadMultiple();
      foreach ($conf_entities as $entity) {
        if (!$entity->uuid()) {
          $config_id = $entity->getConfigDependencyName();
          $config = $config_factory->getEditable($config_id);
          $configs[$config_id] = $config;
        }
      }
    }
    ksort($configs);

    return $configs;
  }

  /**
   * Provides uuid for config entities missing them.
   *
   * @param \Drupal\Core\Config\Config[] $configs
   *   The list of configs to generate uuid for.
   */
  protected function provideUuid(array $configs): void {
    $uuid = \Drupal::service('uuid');
    foreach ($configs as $config) {
      $config->set('uuid', $uuid->generate())
        ->save();
    }
  }

}
