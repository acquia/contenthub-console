<?php

namespace Acquia\Console\ContentHub\Command\Migrate;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use Acquia\Console\ContentHub\Command\Helpers\PlatformCommandExecutionTrait;
use Acquia\Console\ContentHub\Exception\ContentHubVersionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Extension\MissingDependencyException;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubMigrationPrepareUpgrade.
 *
 * @package Acquia\Console\ContentHub\Command\Migration
 */
class ContentHubMigrationPrepareUpgrade extends ContentHubCommandBase implements PlatformBootStrapCommandInterface {

  use PlatformCommandExecutionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:migrate:prepare-upgrade';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Prepare sites for the version 2 upgrade.')
      ->setAliases(['ach-mpu'])
      ->addOption(
        'uninstall-modules',
        'um',
        InputOption::VALUE_OPTIONAL,
        'List of modules to uninstall as part of the preparation process.'
      );
  }

  /**
   * {@inheritDoc}
   */
  protected function initialize(InputInterface $input, OutputInterface $output) {
    parent::initialize($input, $output);

    if ($this->achClientService->getVersion() !== 1) {
      throw new ContentHubVersionException(1);
    }
  }

  /**
   * {@inheritDoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Extension\MissingDependencyException
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $output->writeln('Initiating Upgrade Process...');
    $this->handleModules($output, $input->getOption('uninstall-modules') ?? []);
    $this->removeRestResource($output);
    $this->purgeSubscription($output);
    $ret = $this->deleteWebhooks($output);
    $this->execDrushWithOutput($output, ['cr'], $input->getOption('uri') ?? '');
    $output->writeln('<info>Upgrade preparation has been completed. Please build a branch with Content Hub 2.x and push it to origin.</info>');
    return $ret;
  }

  /**
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output to write status to.
   * @param array $modules_to_uninstall
   *   [Optional] Additional modules to uninstall. These are modules
   *   implementing or relying on acquia_contenthub 8.x-1.x.
   *
   * @throws \Drupal\Core\Extension\MissingDependencyException
   *   Thrown when depcalc module could not be installed.
   */
  protected function handleModules(OutputInterface $output, array $modules_to_uninstall = []): void {
    $output->writeln('Initiating module installation process...');
    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
    $module_installer = \Drupal::service('module_installer');
    // Attempt to install depcalc first. If it doesn't exist stop execution.
    if (!$module_installer->install(['depcalc'])) {
      throw new MissingDependencyException('Depcalc module installation was failed!', 1);
    }
    else {
      $output->writeln('<info>Depcalc module has been successfully installed.</info>');
    }

    $modules = [
      'acquia_contenthub_audit',
      'acquia_contenthub_status',
      'acquia_contenthub_diagnostic',
    ];
    $modules = !empty($modules_to_uninstall) ? array_merge($modules, $modules_to_uninstall) : $modules;
    if (!$module_installer->uninstall($modules)) {
      $output->writeln('<warning>Some module could not be uninstalled!</warning>');
      return;
    }
    $output->writeln('<info>All module installation operation has been successfully carried out.</info>');
  }

  /**
   * Removes Content Hub Filter rest resources related configs from database.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output to write status to.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function removeRestResource(OutputInterface $output): void {
    $output->writeln('Removing Content Hub Filter rest resource configurations...');
    $entity_type_manager = \Drupal::entityTypeManager();
    try {
      $rest_storage = $entity_type_manager->getStorage('rest_resource_config');
    }
    catch (PluginNotFoundException $e) {
      $output->writeln("<error>Error during cleanup: {$e->getMessage()}");
    }

    $filter_resource = $rest_storage->load('contenthub_filter');
    if ($filter_resource) {
      $filter_resource->delete();
      $output->writeln('<info>Content Hub Filter rest resource has been deleted.');
    }
    $output->writeln('Content Hub Filter rest resource removal has been finished.');
  }

  /**
   * Purges entities from Content Hub subscription.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output stream to write status to.
   */
  protected function purgeSubscription(OutputInterface $output): void {
    $output->writeln('Purging subscription...');
    try {
      $this->achClientService->purge();
    }
    catch (\Exception $e) {
      $output->writeln("<error>{$e->getMessage()}</error>");
    }
    $output->writeln('<info>Subscription has been successfully purged.</info>');
  }

  /**
   * Deletes every webhook.
   */
  protected function deleteWebhooks(OutputInterface $output): int {
    $output->writeln('Deleting webhooks...');
    $webhooks = $this->achClientService->getWebhooks();
    if (empty($webhooks)) {
      $output->writeln('<warning>No webhooks to delete.</warning>');
    }

    $ret = 0;
    foreach ($webhooks as $webhook) {
      try {
        $this->achClientService->deleteWebhook($webhook['uuid']);
      }
      catch (\Exception $e) {
        $output->writeln("<error>Could not delete webhook with id {$webhook['uuid']}. Reason: {$e->getMessage()}</error>");
        $ret = 1;
      }
    }
    $output->writeln('<info>Webhook deletion process has been finished.</info>');
    return $ret;
  }

}
