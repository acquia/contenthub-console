<?php

namespace Acquia\Console\ContentHub\Command\Backups;

use Acquia\Console\Acsf\Platform\ACSFPlatform;
use Acquia\Console\Cloud\Command\AcquiaCloudCommandBase;
use Acquia\Console\Cloud\Command\DatabaseBackup\AcquiaCloudDatabaseBackupCreate;
use Acquia\Console\Cloud\Command\DatabaseBackup\AcquiaCloudDatabaseBackupHelperTrait;
use Acquia\Console\Cloud\Command\DatabaseBackup\AcquiaCloudDatabaseBackupList;
use Acquia\Console\Cloud\Platform\AcquiaCloudMultiSitePlatform;
use Acquia\Console\Cloud\Platform\AcquiaCloudPlatform;
use Acquia\Console\Helpers\PlatformCommandExecutioner;
use Acquia\Console\Helpers\Command\PlatformCmdOutputFormatterTrait;
use Acquia\Console\ContentHub\Command\ServiceSnapshots\ContentHubCreateSnapshot;
use Consolidation\Config\Config;
use EclipseGc\CommonConsole\Config\ConfigStorage;
use EclipseGc\CommonConsole\PlatformInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class AcquiaCloudBackupCreate.
 *
 * Creates a snapshot of Acquia Content Hub Service and database
 * backups for all sites within the platform.
 *
 * @package Acquia\Console\ContentHub\Command\Backups
 */
class AcquiaCloudBackupCreate extends AcquiaCloudCommandBase {

  use PlatformCmdOutputFormatterTrait;
  use AcquiaCloudDatabaseBackupHelperTrait;

  /**
   * The platform command executioner.
   *
   * @var \Acquia\Console\Helpers\PlatformCommandExecutioner
   */
  protected $platformCommandExecutioner;

  /**
   * Parts of the directory path pointing to configuration files.
   *
   * @var array
   */
  protected $configDir = [
    '.acquia',
    'contenthub',
    'backups',
  ];

  /**
   * The config storage.
   *
   * @var \EclipseGc\CommonConsole\Config\ConfigStorage
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ace:backup:create';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setDescription('Creates a snapshot of Acquia Content Hub Service and database backups for all sites within the platform.')
      ->setHidden(TRUE)
      ->setAliases(['ace-bc']);
  }

  /**
   * AcquiaCloudBackupCreate constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher.
   * @param \EclipseGc\CommonConsole\Config\ConfigStorage $config_storage
   *   Config storage.
   * @param \Acquia\Console\Helpers\PlatformCommandExecutioner $platform_command_executioner
   *   The platform command executioner.
   * @param string|null $name
   *   Command name.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, ConfigStorage $config_storage, PlatformCommandExecutioner $platform_command_executioner, string $name = NULL) {
    parent::__construct($event_dispatcher, $name);

    $this->storage = $config_storage;
    $this->platformCommandExecutioner = $platform_command_executioner;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $output->writeln('We are about to create a backup of all databases in this platform and a snapshot of the subscription.');
    $output->writeln('Please name this backup in order to restore it later (alphanumeric characters only)!');

    $config = new Config();
    $this->configDir[] = $this->platform->getAlias();

    $helper = $this->getHelper('question');
    $question = new Question('Please enter a name:');
    $question->setValidator(function ($answer) {
      if (!$answer) {
        throw new \RuntimeException(
          'Name cannot be empty!'
        );
      }
      if (strlen($answer) !== strlen(preg_replace('/\s+/', '', $answer))) {
        throw new \RuntimeException(
          'Name cannot contain white spaces!'
        );
      }
      if ($this->storage->configExists($this->configDir, $answer)) {
        throw new \RuntimeException(
          'Configuration with given name already exists!'
        );
      }

      return $answer;
    });
    $answer = $helper->ask($input, $output, $question);

    try {
      $backups = $this->getBackupId($this->platform, $input, $output);
      if (empty($backups)) {
        $output->writeln('<warning>Cannot find the recently created backup.</warning>');
        return 1;
      }
      $output->writeln('<info>Database backups are successfully created! Starting Content Hub service snapshot creation!</info>');
      // In case there is an exception while creating the snapshot,
      // database backup needs to be deleted.
      $snapshot_failed = TRUE;
      try {
        $group_name = $input->hasOption('group') ? $input->getOption('group') : '';
        $snapshot = $this->runSnapshotCreateCommand($output, $group_name);
        $snapshot_failed = empty($snapshot);
      }
      catch (\Exception $exception) {
        $output->writeln("<error>{$exception->getMessage()}</error>");
      }
      if ($snapshot_failed) {
        $this->deleteDatabaseBackups($backups);
        $output->writeln('<warning>Cannot create Content Hub service snapshot. Please check your Content Hub service credentials and try again.</warning>');
        $output->writeln('<warning>The previously created database backups are being deleted because the service snapshot creation failed.</warning>');
        return 2;
      }
      $output->writeln("<info>Content Hub Service Snapshot is successfully created. Current Content Hub version is {$snapshot['module_version']}.x .</info>");

    }
    catch (\Exception $exception) {
      $output->writeln("<error>{$exception->getMessage()}</error>");
      return 3;
    }

    $platform_info = [
      'name' => $this->platform->getAlias(),
      'type' => $this->platform->getPlatformId(),
      'module_version' => $snapshot['module_version'],
      'backupCreated' => time(),
    ];
    $backup_info = [
      'database' => $backups,
      'ach_snapshot' => $snapshot['snapshot_id'],
    ];

    $config->set('name', $answer);
    $config->set('platform', $platform_info);
    $config->set('backups', $backup_info);
    $this->storage->save($config, $answer, $this->configDir);

    return 0;
  }

  /**
   * Returns an array of newly created backups.
   *
   * @param \EclipseGc\CommonConsole\PlatformInterface $platform
   *   Platform instance.
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   Input instance.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   Output instance.
   *
   * @return array
   *   Info about newly created database backups.
   *
   * @throws \Exception
   */
  protected function getBackupId(PlatformInterface $platform, InputInterface $input, OutputInterface $output): array {
    $output->writeln('<info>Starting the creation of database backups for all sites in the platform...</info>');
    $output->writeln('<info>This may take a while...</info>');
    $list_before = $this->runBackupListCommand($platform, $output);
    $raw = $this->runBackupCreateCommand($platform);

    if ($raw->getReturnCode() !== 0) {
      throw new \Exception('Database backup creation failed.');
    }

    $list_after = $this->runBackupListCommand($platform, $output);

    return array_diff_key($list_after, $list_before);
  }

  /**
   * List currently saved database backups.
   *
   * @param \EclipseGc\CommonConsole\PlatformInterface $platform
   *   Platform instance.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   Output.
   *
   * @return array
   *   Db backup info.
   *
   * @throws \Exception
   */
  protected function runBackupListCommand(PlatformInterface $platform, OutputInterface $output): array {
    $cmd_input = [
      '--all' => TRUE,
      '--silent' => TRUE,
    ];

    $raw = $this->platformCommandExecutioner->runLocallyWithMemoryOutput(AcquiaCloudDatabaseBackupList::getDefaultName(), $platform, $cmd_input);

    $db_backup_list = [];
    $lines = explode(PHP_EOL, trim($raw));
    foreach ($lines as $line) {
      $data = $this->fromJson($line, $output);
      if (!$data) {
        continue;
      }

      foreach ($data as $backup) {
        $db_backup_list[$backup->backup_id] = [
          'environment_id' => $backup->env_id,
          'database_name' => $backup->database,
          'created_at' => $backup->completed_at,
        ];
      }
    }

    return $db_backup_list;
  }

  /**
   * Runs database backup creation command.
   *
   * @param \EclipseGc\CommonConsole\PlatformInterface $platform
   *   Platform instance.
   *
   * @return object
   *   Object containing command run info.
   *
   * @throws \Exception
   */
  protected function runBackupCreateCommand(PlatformInterface $platform): object {
    $cmd_input = [
      '--all' => TRUE,
      '--wait' => TRUE,
    ];

    return $this->platformCommandExecutioner->runLocallyWithMemoryOutput(AcquiaCloudDatabaseBackupCreate::getDefaultName(), $platform, $cmd_input);
  }

  /**
   * Creates ACH service snapshot.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   Output.
   * @param string $group_name
   *   Group name.
   *
   * @return array
   *   Array containing snapshot ID and module version.
   *
   * @throws \Exception
   */
  protected function runSnapshotCreateCommand(OutputInterface $output, string $group_name): array {
    $raw = $this->platformCommandExecutioner->runWithMemoryOutput(ContentHubCreateSnapshot::getDefaultName(), $this->getPlatform('source'), [
      '--uri' => $this->getUri($output, $group_name),
    ]);

    $exit_code = $raw->getReturnCode();
    if ($exit_code !== 0) {
      throw new \Exception("Cannot create Content Hub service snapshot. Exit code: $exit_code");
    }

    $info = [];

    $lines = explode(PHP_EOL, trim($raw));
    foreach ($lines as $line) {
      $data = $this->fromJson($line, $output);
      if (!$data) {
        continue;
      }

      $info = [
        'snapshot_id' => $data->snapshot_id,
        'module_version' => $data->module_version,
      ];
    }

    return $info;
  }

  /**
   * Deletes database backups in Acquia Cloud.
   *
   * @param array $backups
   *   The database backup list.
   */
  protected function deleteDatabaseBackups(array $backups) {
    foreach ($backups as $backup_id => $data) {
      $this->delete($data['environment_id'], $data['database_name'], $backup_id);
    }
  }

  /**
   * Gets one of the site URI from platform.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   Output instance.
   * @param string $group_name
   *   The group name.
   *
   * @return string
   *   Returns URI.
   */
  protected function getUri(OutputInterface $output, string $group_name): string {
    $sites = [];
    $platform_id = $this->platform::getPlatformId();
    switch ($platform_id) {
      case AcquiaCloudMultiSitePlatform::PLATFORM_NAME:
        $sites = $this->platform->getMultiSites();
        break;

      case AcquiaCloudPlatform::PLATFORM_NAME:
      case ACSFPlatform::PLATFORM_NAME:
        $sites = $this->platform->getPlatformSites();
        break;
    }

    if (!empty($group_name)) {
      $alias = $this->platform->getAlias();
      $sites = $this->filterSitesByGroup($group_name, $sites, $output, $alias, $platform_id);
      if (empty($sites)) {
        return 1;
      }
    }

    $site_info = reset($sites);
    return $site_info['uri'] ?? $site_info;
  }

}
