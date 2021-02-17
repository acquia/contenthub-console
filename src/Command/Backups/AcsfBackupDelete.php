<?php

namespace Acquia\Console\ContentHub\Command\Backups;

use Acquia\Console\Acsf\Command\Helpers\AcsfDbBackupDeleteHelper;
use Acquia\Console\Acsf\Platform\ACSFPlatform;
use EclipseGc\CommonConsole\PlatformInterface;

/**
 * Class AcsfBackupDelete.
 *
 * @package Acquia\Console\ContentHub\Command\Backups
 */
class AcsfBackupDelete extends AcquiaCloudBackupDelete {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'acsf:backup:delete';

  /**
   * {@inheritdoc}
   */
  public static function getExpectedPlatformOptions(): array {
    return ['source' => ACSFPlatform::getPlatformId()];
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Deletes a snapshot of Acquia Content Hub service and database backups for all sites within the ACSF platform.')
      ->setHidden(TRUE)
      ->setAliases(['acsf-bd']);
  }

  /**
   * {@inheritdoc}
   */
  protected function deleteDatabaseBackups(PlatformInterface $platform, array $backups): int {
    $raw = $this
      ->executioner
      ->runLocallyWithMemoryOutput(AcsfDbBackupDeleteHelper::getDefaultName(), $platform, ['--backups' => $backups]);
    return $raw->getReturnCode();
  }

}
