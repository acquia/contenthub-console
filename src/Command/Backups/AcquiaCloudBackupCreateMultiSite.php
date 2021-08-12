<?php

namespace Acquia\Console\ContentHub\Command\Backups;

use Acquia\Console\Cloud\Command\DatabaseBackup\AcquiaCloudMultisiteDatabaseBackupCreate;
use Acquia\Console\Cloud\Command\DatabaseBackup\AcquiaCloudMultisiteDatabaseBackupList;
use Acquia\Console\Cloud\Platform\AcquiaCloudMultiSitePlatform;
use EclipseGc\CommonConsole\PlatformInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AcquiaCloudBackupCreateMultiSite.
 *
 * Creates a snapshot of Acquia Content Hub Service and database
 * backups for all sites within ACE Multi-site environment.
 *
 * @package Acquia\Console\ContentHub\Command\Backups
 */
class AcquiaCloudBackupCreateMultiSite extends AcquiaCloudBackupCreate {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ace-multi:backup:create';

  /**
   * {@inheritdoc}
   */
  public static function getExpectedPlatformOptions(): array {
    return ['source' => AcquiaCloudMultiSitePlatform::getPlatformId()];
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setDescription('Creates a snapshot of Acquia Content Hub Service and database backups for all sites within ACE Multi-site environment.')
      ->setHidden(TRUE)
      ->setAliases(['ace-bcm']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getBackupId(PlatformInterface $platform, InputInterface $input, OutputInterface $output): array {
    $output->writeln('<info>Starting database backup creation.</info>');
    $list_before = $this->runBackupListCommand($platform, $output);
    $raw = $this->runBackupCreateCommand($platform);

    if ($raw->getReturnCode() !== 0) {
      throw new \Exception('Database backup creation failed.');
    }

    $list_after = $this->runBackupListCommand($platform, $output);
    return array_diff_key($list_after, $list_before);
  }

  /**
   * {@inheritdoc}
   */
  protected function runBackupListCommand(PlatformInterface $platform, OutputInterface $output): array {
    $cmd_input = [
      '--all' => TRUE,
      '--silent' => TRUE,
    ];

    $raw = $this->platformCommandExecutioner->runLocallyWithMemoryOutput(AcquiaCloudMultisiteDatabaseBackupList::getDefaultName(), $platform, $cmd_input);
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
   * {@inheritdoc}
   */
  protected function runBackupCreateCommand(PlatformInterface $platform): object {
    $cmd_input = [
      '--all' => TRUE,
      '--wait' => TRUE,
    ];
    return $this->platformCommandExecutioner->runLocallyWithMemoryOutput(AcquiaCloudMultisiteDatabaseBackupCreate::getDefaultName(), $platform, $cmd_input);
  }

  /**
   * {@inheritdoc}
   */
  protected function getUri(OutputInterface $output, string $group_name): string {
    $sites = $this->platform->getMultiSites();
    return reset($sites);
  }

}
