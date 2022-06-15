<?php

namespace Acquia\Console\ContentHub\Command\Backups;

use Acquia\Console\Cloud\Command\AcquiaCloudCommandBase;
use Acquia\Console\Cloud\Command\Helpers\AcquiaCloudDbBackupRestoreHelper;
use Acquia\Console\Helpers\PlatformCommandExecutioner;
use Acquia\Console\ContentHub\Command\Helpers\ContentHubRestoreSnapshotHelper;
use Consolidation\Config\Config;
use EclipseGc\CommonConsole\Config\ConfigStorage;
use EclipseGc\CommonConsole\PlatformInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class AcquiaCloudBackupRestore.
 *
 * @package Acquia\Console\ContentHub\Command\Backups
 */
class AcquiaCloudBackupRestore extends AcquiaCloudCommandBase {

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
  protected static $defaultName = 'ace:backup:restore';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Restores a snapshot of Acquia Content Hub service and database backups for all site within the platform.')
      ->setHidden(TRUE)
      ->setAliases(['ace-br']);
  }

  /**
   * AcquiaCloudBackupCreate constructor.
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
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $output->writeln('<warning>We are about to restore backups of all databases in this platform and a snapshot of the subscription.</warning>');

    $sites = $this->getPlatformSitesForRestore();
    if (empty($sites)) {
      $output->writeln('<Error>There are no sites in this platform.</Error>');
      return 1;
    }

    $alias = $this->platform->getAlias();
    $backups = $this->getPlatformBackupConfigs($alias);
    $configs = array_map(function (Config $config) {
       return $config->get('name');
    }, $backups);

    if (!$configs) {
      $output->writeln('<error>The requested backup does not exist. Please retry.</error>');
      return 1;
    }

    /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
    $helper = $this->getHelper('question');
    $question = new ChoiceQuestion('Please pick a configuration to restore:', $configs);
    $answer = $helper->ask($input, $output, $question);

    try {
      $platform = $this->platform;
      $config_to_restore = $this->storage->load($answer, $this->configDir);
      $uri = $this->getUri($platform, $input, $output);
      $output->writeln('<info>Starting Acquia Content Hub service restoration.</info>');
      $exit_code = $this->restoreSnapshot($config_to_restore->get('backups.ach_snapshot'), $platform, $uri);
      if ($exit_code !== 0) {
        $output->writeln(sprintf('<error>Acquia Content Hub service restoration failed with exit code: %s.</error>', $exit_code));
        return $exit_code;
      }
      $output->writeln('<info>Acquia Content Hub service restoration is completed successfully.</info>');
      $output->writeln('<info>Database backup restoration started. It can take several minutes to complete.</info>');
      $exit = $this->restoreDatabaseBackups($this->platform, $config_to_restore->get('backups.database'));
    }
    catch (\Exception $exception) {
      $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));
      return 2;
    }

    if ($exit !== 0) {
      $output->writeln(sprintf('<error>Backup restoration command failed with exit code: %s.</error>', $exit));
      return $exit;
    }

    $output->writeln('<info>Acquia Content Hub service and site\'s database backups have been restored successfully!</info>');
    return 0;
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
   * Restore database backups on ACE platform.
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
  protected function restoreDatabaseBackups(PlatformInterface $platform, array $backups): int {
    $raw = $this
      ->executioner
      ->runLocallyWithMemoryOutput(AcquiaCloudDbBackupRestoreHelper::getDefaultName(), $platform, ['--backups' => $backups]);
    return $raw->getReturnCode();
  }

  /**
   * Restore ACH service snapshot.
   *
   * @param string $snapshot_name
   *   Snapshot name.
   * @param \EclipseGc\CommonConsole\PlatformInterface $platform
   *   Platform instance.
   * @param string $uri
   *   Site uri from platform.
   *
   * @return int
   *   Exit code.
   */
  protected function restoreSnapshot(string $snapshot_name, PlatformInterface $platform, string $uri): int {
    $raw = $this
      ->executioner
      ->runWithMemoryOutput(ContentHubRestoreSnapshotHelper::getDefaultName(), $platform, [
        '--name' => $snapshot_name,
        '--uri' => $uri
      ]);
    return $raw->getReturnCode();
  }

  /**
   * Helper function to get sites on the platform.
   *
   * @return array
   *   Array of sites on the platform.
   */
  protected function getPlatformSitesForRestore(): array {
    return $this->getPlatformSites('source');
  }

}
