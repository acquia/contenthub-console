<?php

namespace Acquia\Console\ContentHub\Command\Migrate;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubMigrateEnableUnsubscribe
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubMigrateEnableUnsubscribe extends ContentHubCommandBase implements PlatformBootStrapCommandInterface {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:migrate:unsubscribe';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Enables Unsubscribe module if there are "disconnected" imported entities in the site.');
    $this->setAliases(['ach-mi-un']);
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
    $output->writeln('Searching for "Disconnected" imported entities.');
    if (\Drupal::moduleHandler()->moduleExists('acquia_contenthub_subscriber')) {
      return $this->enableUnsubscribeForAutoUpdateDisabledEntities($output);
    }

    $output->writeln('<info>This site is not a subscriber.</info>');
    return 0;

  }

  /**
   * Enables Unsubscribe module for Auto-Update Disabled/Local Changes Entities.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The Output interface.
   *
   * @return int
   *   0: Success, Any other integer if it fails.
   */
  protected function enableUnsubscribeForAutoUpdateDisabledEntities(OutputInterface $output): int {
    $disconnected_entities = \Drupal::state()->get('acquia_contenthub_update_82001_disconnected_entities', []);
    if (empty($disconnected_entities)) {
      $output->writeln('<info>There are no entities with local changes or Auto-Update disabled imported in this site.</info>');
      return 0;
    }
    $output->writeln('<info>We have detected imported entities with local changes or Auto-Update disabled previously syndicated with Content Hub in this site. Enabling "acquia_contenthub_unsubscribe" module...</info>');
    try {
      \Drupal::service('module_installer')->install(['acquia_contenthub_unsubscribe']);
    }
    catch (\Exception $e) {
      $output->writeln("<error>Module 'acquia_contenthub_unsubscribe' could not be installed. {$e->getMessage()}</error>");
      return 1;
    }
    $output->writeln('<warning>We have turned on the "acquia_contenthub_unsubscribe" module. Note that this module is experimental in Content Hub 2.x and provides no UI for disconnecting entities from syndication.</warning>');
    return 0;
  }

}
