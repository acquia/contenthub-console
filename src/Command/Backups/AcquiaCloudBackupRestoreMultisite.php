<?php

namespace Acquia\Console\ContentHub\Command\Backups;

use Acquia\Console\Cloud\Platform\AcquiaCloudMultiSitePlatform;

/**
 * Class AcquiaCloudBackupRestoreMultisite.
 *
 * @package Acquia\Console\ContentHub\Command\Backups
 */
class AcquiaCloudBackupRestoreMultisite extends AcquiaCloudBackupRestore {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ace-multi:backup:restore';

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
    $this->setDescription('Restores a snapshot of Acquia Content Hub Service and database backups for all sites within ACE Multi-site environment.')
      ->setHidden(TRUE)
      ->setAliases(['ace-brm']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getPlatformSitesForRestore(): array {
    return $this->platform->getMultiSites();
  }

  /**
   * {@inheritdoc}
   */
  protected function getUri(array $sites): string {
    return reset($sites);
  }

}
