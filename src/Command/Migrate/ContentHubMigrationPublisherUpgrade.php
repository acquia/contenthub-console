<?php

namespace Acquia\Console\ContentHub\Command\Migrate;

use Acquia\Console\ContentHub\Command\Helpers\PlatformCommandExecutionTrait;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubMigrationPublisherUpgrade.
 *
 * @package Acquia\ContentHubCli\Command\Migration
 */
class ContentHubMigrationPublisherUpgrade extends Command implements PlatformBootStrapCommandInterface {

  use PlatformCommandExecutionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:migrate:upgrade';

  /**
   * @inheritDoc
   */
  public function getPlatformBootstrapType(): string {
    return 'drupal8';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Upgrade sites to 2.x version.')
      ->setAliases(['ach-mu'])
      ->addOption(
        'lift-support',
        'ls',
        InputOption::VALUE_NONE,
        'Enable acquia_lift_support module.'
      );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $output->writeln('Initiating module upgrade process...');
    $this->setSchema($output);
    $this->updateDatabases($input, $output);
    if ($input->getOption('lift-support')) {
      $this->enableAcquiaLiftSupportModule($output);
    }
    $this->upgradePublishers($output, $input->getOption('uri'));
  }

  /**
   * Sets the schema version to version 2.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output stream to write to.
   */
  protected function setSchema(OutputInterface $output): void {
    $output->writeln('Schema version setup...');
    drupal_set_installed_schema_version('acquia_contenthub', '8200');
    $output->writeln('<info>Done</info>');
  }

  /**
   * Runs database schema updates.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output stream to write to.
   *
   * @throws \Exception
   */
  protected function updateDatabases(InputInterface $input, OutputInterface $output): void {
    $output->writeln('Running database updates...');
    $this->execDrush(['update-db', '-y', $input->getOption('uri')]);
  }

  /**
   * Enables acquia_lift_support module.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output stream to write to.
   */
  protected function enableAcquiaLiftSupportModule(OutputInterface $output): void {
    $output->writeln('Installing acquia_lift_support module...');
    try {
      \Drupal::service('module_installer')->install(['acquia_lift_support']);
    }
    catch (\Exception $e) {
      $output->writeln("<error>Module could not be installed. {$e->getMessage()}</error>");
      return;
    }
  }

  /**
   * Identifies if a site is a publisher and runs the publisher upgrade command.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output stream to write to.
   */
  protected function upgradePublishers(OutputInterface $output, $uri = ''): void {
    $result = \Drupal::database()
      ->select('acquia_contenthub_entities_tracking', 'exp')
      ->fields('exp', ['status_export'])
      ->condition('exp.status_export', '', '<>')
      ->execute()
      ->fetchField();
    $publisher = $result ?? 0;
    if ($publisher) {
      $output->writeln('The site is a publisher, enabling acquia_contenthub_publisher...');
      \Drupal::service('module_installer')->install(['acquia_contenthub_publisher']);
    }

    // It is possible that it was already enabled, therefore we need to make
    // sure if that is the case.
    if (\Drupal::moduleHandler()->moduleExists('acquia_contenthub_publisher')) {
      $output->writeln('Running publisher upgrades...');
      $this->execDrush(['ach-publisher-upgrade'], $uri);
      $output->writeln('Done');
    }
  }

}
