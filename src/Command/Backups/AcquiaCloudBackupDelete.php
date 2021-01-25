<?php

namespace Acquia\Console\ContentHub\Command\Backups;

use Acquia\Console\Cloud\Command\AcquiaCloudCommandBase;
use Acquia\Console\Cloud\Command\Helpers\AcquiaCloudDbBackupDeleteHelper;
use Acquia\Console\Helpers\PlatformCommandExecutioner;
use Acquia\Console\ContentHub\Command\Helpers\ContentHubDeleteSnapshotHelper;
use Consolidation\Config\Config;
use EclipseGc\CommonConsole\Config\ConfigStorage;
use EclipseGc\CommonConsole\PlatformInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class AcquiaCloudBackupDelete.
 *
 * @package Acquia\Console\ContentHub\Command\Backups
 */
class AcquiaCloudBackupDelete extends AcquiaCloudCommandBase {

  /**
   * Parts of the directory path pointing to configuration files.
   *
   * @var array
   */
  protected $configDir = [
    '.acquia',
    'contenthub',
    'backups'
  ];

  /**
   * The config storage.
   *
   * @var \EclipseGc\CommonConsole\Config\ConfigStorage
   */
  protected $storage;

  /**
   * Command executioner service.
   *
   * @var \Acquia\Console\Helpers\PlatformCommandExecutioner
   */
  protected $executioner;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ace:backup:delete';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Delete snapshot of ACH service and database backups for all site within the platform.');
    $this->setAliases(['ace-bd']);
  }

  /**
   * AcquiaCloudBackupDelete constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher.
   * @param \EclipseGc\CommonConsole\Config\ConfigStorage $configStorage
   *   Config storage.
   * @param \Acquia\Console\Helpers\PlatformCommandExecutioner $executioner
   *   Command executioner service instance.
   * @param string|null $name
   *   Command name.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, ConfigStorage $configStorage, PlatformCommandExecutioner $executioner, string $name = NULL) {
    parent::__construct($event_dispatcher, $name);

    $this->storage = $configStorage;
    $this->executioner = $executioner;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $output->writeln('<warning>We are about to delete backups of all databases in this platform and a snapshot of the subscription.</warning>');

    $sites = $this->getPlatformSitesForDelete();
    if (empty($sites)) {
      $output->writeln('<Error>There are no sites in this platform.</Error>');
      return 1;
    }

    $alias = $this->platform->getAlias();
    $backups = $this->getPlatformBackupConfigs($alias);
    $configs = array_map(function (Config $config) {
      return $config->get('name');
    }, $backups);

    /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
    $helper = $this->getHelper('question');
    $question = new ChoiceQuestion('Please pick a configuration to delete:', $configs);
    $answer = $helper->ask($input, $output, $question);

    try {
      $config_to_delete = $this->storage->load($answer, $this->configDir);
      $uri = $this->getUri($sites);
      $output->writeln('<info>Starting Acquia Content Hub service snapshot deletion.</info>');
      $exit_code = $this->deleteSnapshot($config_to_delete->get('backups.ach_snapshot'), $this->platform, $uri);
      if ($exit_code !== 0) {
        $output->writeln(sprintf('<error>Acquia Content Hub service snapshot deletion failed with exit code: %s.</error>', $exit_code));
        return $exit_code;
      }
      $output->writeln('<info>Acquia Content Hub service snapshot deletion is completed successfully.</info>');
      $output->writeln('<info>Database backup deletion started. It can take several minutes to complete.</info>');
      $exit = $this->deleteDatabaseBackups($this->platform, $config_to_delete->get('backups.database'));
    }
    catch (\Exception $e) {
      $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
      return 2;
    }

    if ($exit !== 0) {
      $output->writeln(sprintf('<error>Backup deletion command failed with exit code: %s.</error>', $exit));
      return $exit;
    }

    try {
      $this->storage->delete($answer, $this->configDir);
    }
    catch (\Exception $e) {
      $output->writeln('<warning>Backup configuration file deletion failed.</warning>');
      return 3;
    }

    $output->writeln('<info>Acquia Content Hub service snapshot and site\'s database backups have been deleted successfully!</info>');
    $output->writeln('<info>Backup configuration file has been deleted successfully!</info>');
    return 0;

  }

  /**
   * Delete database backups on ACE platform.
   *
   * @param \EclipseGc\CommonConsole\PlatformInterface $platform
   *   Platform instance.
   * @param array $backups
   *   Database backup information from configuration.
   *
   * @return int
   *   Exit code.
   *
   * @throws \Exception
   */
  protected function deleteDatabaseBackups(PlatformInterface $platform, array $backups): int {
    $raw = $this
      ->executioner
      ->runLocallyWithMemoryOutput(AcquiaCloudDbBackupDeleteHelper::getDefaultName(), $platform, ['--backups' => $backups]);
    return $raw->getReturnCode();
  }

  /**
   * Return backup configs of given platform.
   *
   * @param string $alias
   *   Platform alias.
   *
   * @return \Consolidation\Config\Config[]
   *   Array containing all backup for given platform.
   */
  protected function getPlatformBackupConfigs(string $alias): array {
    array_push($this->configDir, $alias);
    return $this->storage->loadAll($this->configDir);
  }

  /**
   * Delete ACH service snapshot.
   *
   * @param string $snapshot_name
   *   Snapshot name.
   * @param \EclipseGc\CommonConsole\PlatformInterface $platform
   *   Platform object.
   * @param string $uri
   *   Site uri from platform.
   *
   * @return int
   *   Exit code.
   */
  protected function deleteSnapshot(string $snapshot_name, PlatformInterface $platform, string $uri): int {
    $raw = $this
      ->executioner
      ->runWithMemoryOutput(ContentHubDeleteSnapshotHelper::getDefaultName(), $platform, [
        '--name' => $snapshot_name,
        '--uri' => $uri
      ]);
    return $raw->getReturnCode();
  }

  /**
   * Helper function to get sites on the platform.
   *
   * @return array
   *   Array of sites.
   */
  protected function getPlatformSitesForDelete(): array {
    return $this->getPlatformSites('source');
  }

  /**
   * Gets one of the site URI from platform.
   *
   * @param array $sites
   *   Array of sites in the platform.
   *
   * @return string
   *   Uri of site.
   */
  protected function getUri(array $sites): string {
    $site_info = reset($sites);
    return $site_info['uri'];
  }

}
