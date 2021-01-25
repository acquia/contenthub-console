<?php

namespace Acquia\Console\ContentHub\Command\Backups;

use Acquia\Console\Acsf\Command\Helpers\AcsfDbBackupRestoreHelper;
use Acquia\Console\Acsf\Platform\ACSFPlatform;
use EclipseGc\CommonConsole\PlatformInterface;

/**
 * Class AcsfBackupRestore.
 *
 * @package Acquia\Console\ContentHub\Command\Backups
 */
class AcsfBackupRestore extends AcquiaCloudBackupRestore {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'acsf:backup:restore';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Restore snapshot of Acquia Content Hub service service and database backups for all sites within the ACSF platform.');
    $this->setAliases(['acsf-br']);
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
  protected function restoreDatabaseBackups(PlatformInterface $platform, array $backups): int {
    $raw = $this
      ->executioner
      ->runLocallyWithMemoryOutput(AcsfDbBackupRestoreHelper::getDefaultName(), $platform, ['--backups' => $backups]);
    return $raw->getReturnCode();
  }

}
