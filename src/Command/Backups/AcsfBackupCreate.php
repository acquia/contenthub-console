<?php

namespace Acquia\Console\ContentHub\Command\Backups;

use Acquia\Console\Acsf\Client\ResponseHandlerTrait;
use Acquia\Console\Acsf\Command\AcsfDatabaseBackupCreate;
use Acquia\Console\Acsf\Command\AcsfDatabaseBackupList;
use Acquia\Console\Acsf\Platform\ACSFPlatform;
use EclipseGc\CommonConsole\PlatformInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AcsfBackupCreate.
 *
 * @package Acquia\Console\Acsf\Command\Backups
 */
class AcsfBackupCreate extends AcquiaCloudBackupCreate {

  use ResponseHandlerTrait;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'acsf:backup:create';

  /**
   * Acsf client.
   *
   * @var \Acquia\Console\Acsf\Client\AcsfClient
   */
  protected $acsfClient;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Creates a snapshot of Acquia Content Hub Service and database backups for all sites within the ACSF platform.');
    $this->setAliases(['acsf-bc']);
  }

  /**
   * {@inheritdoc}
   */
  public static function getExpectedPlatformOptions(): array {
    return ['source' => ACSFPlatform::getPlatformId()];
  }

  /**
   * {@inheritdoc}
   */
  protected function initialize(InputInterface $input, OutputInterface $output): void {
    parent::initialize($input, $output);

    $this->acsfClient = $this->platform->getAcsfClient();
  }

  /**
   * {@inheritdoc}
   */
  protected function getBackupId(PlatformInterface $platform, OutputInterface $output): array {
    $output->writeln('<info>Starting the creation of database backups for all sites in the platform.</info>');
    $list_before = $this->runAcsfBackupListCommand($platform, $output);
    $raw = $this->runAcsfBackupCreateCommand($platform);

    if ($raw->getReturnCode() !== 0) {
      throw new \Exception('Database backup creation failed.');
    }

    $list_after = $this->runAcsfBackupListCommand($platform, $output);

    return $this->getDifference($list_before, $list_after);
  }

  /**
   * Helper function to get the difference of backups list before and after backup creation.
   *
   * @param object $before
   *   List of backups before backup creation.
   * @param object $after
   *   List of backups after backup creation.
   *
   * @return array
   *   Array of sites with latest backup id created.
   */
  protected function getDifference(object $before, object $after) {
    $diff = [];
    $before = json_decode(json_encode($before), TRUE);
    $after = json_decode(json_encode($after), TRUE);
    foreach ($before as $site_id => $backup_ids) {
      $backup_id = current(array_diff($after[$site_id], $backup_ids));
      if ($backup_id) {
        $diff[$site_id] = $backup_id;
      }
    }

    return $diff;
  }

  /**
   * Helper function to get the list of Acsf sites backup list.
   *
   * @param \EclipseGc\CommonConsole\PlatformInterface $platform
   *   Platform instance.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   Output instance.
   *
   * @return object|null
   *   Object of list containing the sites and associated backup ids.
   *
   * @throws \Exception
   */
  protected function runAcsfBackupListCommand(PlatformInterface $platform, OutputInterface $output): ?object {
    $raw = $this->platformCommandExecutioner->runLocallyWithMemoryOutput(AcsfDatabaseBackupList::getDefaultName(),
      $platform, ['--silent' => TRUE]);

    $lines = explode(PHP_EOL, trim($raw));
    foreach ($lines as $line) {
      $data = $this->fromJson($line, $output);
      if (!$data) {
        continue;
      }

      return $data;
    }

    return NULL;
  }

  /**
   * Runs backup creation or ACSF sites.
   *
   * @param \EclipseGc\CommonConsole\PlatformInterface $platform
   *   Platform instance.
   *
   * @return object
   *   Object containing command run info.
   *
   * @throws \Exception
   */
  protected function runAcsfBackupCreateCommand(PlatformInterface $platform): object {
    $cmd_input = [
      '--all' => TRUE,
      '--wait' => 300,
      '--silent' => TRUE,
    ];

    return $this->platformCommandExecutioner->runLocallyWithMemoryOutput(AcsfDatabaseBackupCreate::getDefaultName(),
      $platform, $cmd_input);
  }

  /**
   * Deletes a database backup in an environment.
   *
   * @param array $backups
   *   The database backup list.
   */
  protected function deleteDatabaseBackups(array $backups) {
    foreach ($backups as $site_id => $backup_id) {
      $this->acsfClient->deleteAcsfSiteBackup($site_id, $backup_id);
    }
  }

}
