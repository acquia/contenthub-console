<?php

namespace Acquia\Console\ContentHub\Command\Migrate;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use Acquia\Console\Helpers\PlatformCommandExecutioner;
use Acquia\Console\ContentHub\Command\Helpers\DrushWrapper;
use Acquia\Console\Helpers\Command\PlatformCmdOutputFormatterTrait;
use Acquia\Console\ContentHub\Exception\ContentHubVersionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Extension\MissingDependencyException;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use EclipseGc\CommonConsole\PlatformCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubMigrationPrepareUpgrade.
 *
 * @package Acquia\Console\ContentHub\Command\Migration
 */
class ContentHubMigrationPrepareUpgrade extends ContentHubCommandBase implements PlatformBootStrapCommandInterface {

  use PlatformCmdOutputFormatterTrait;

  /**
   * The platform command executioner.
   *
   * @var \Acquia\Console\Helpers\PlatformCommandExecutioner
   */
  protected $platformCommandExecutioner;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:migrate:prepare-upgrade';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Prepares sites running Content Hub 1.x for upgrade to version 2.x.')
      ->setHidden(TRUE)
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
   * {@inheritdoc}
   */
  public static function getExpectedPlatformOptions(): array {
    return ['source' => PlatformCommandInterface::ANY_PLATFORM];
  }

  /**
   * ContentHubMigrationPrepareUpgrade constructor.
   *
   * @param \Acquia\Console\Helpers\PlatformCommandExecutioner $platform_command_executioner
   *   The platform command executioner.
   * @param string|null $name
   *   The name of the command.
   */
  public function __construct(PlatformCommandExecutioner $platform_command_executioner, string $name = NULL) {
    parent::__construct($name);
    $this->platformCommandExecutioner = $platform_command_executioner;
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

    $drush_options = ['--drush_command' => 'cr'];
    if ($uri = $input->getOption('uri')) {
      $drush_options['--uri'] = $uri;
    }
    $raw = $this->platformCommandExecutioner->runWithMemoryOutput(DrushWrapper::$defaultName, NULL, $drush_options);
    $exit_code = $raw->getReturnCode();
    $this->getDrushOutput($raw, $output, $exit_code, reset($drush_options));

    $output->writeln('<info>Upgrade preparation has been completed. Please build a branch with Content Hub 2.x and push it to origin.</info>');
    return 0;
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
}
