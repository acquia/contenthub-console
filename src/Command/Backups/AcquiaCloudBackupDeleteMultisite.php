<?php

namespace Acquia\Console\ContentHub\Command\Backups;

use Acquia\Console\Cloud\Platform\AcquiaCloudMultiSitePlatform;

/**
 * Class AcquiaCloudBackupDeleteMultisite.
 *
 * @package Acquia\Console\ContentHub\Command\Backups
 */
class AcquiaCloudBackupDeleteMultisite extends AcquiaCloudBackupDelete {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ace-multi:backup:delete';

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
    $this->setDescription('Delete snapshot of ACH service and database backups for all sites within ACE Multi-site environment.');
    $this->setAliases(['ace-bdm']);
  }

  /**
   * {@inheritDoc}
   */
  protected function getPlatformSitesForDelete(): array {
    return $this->platform->getMultiSites();
  }

  /**
   * {@inheritdoc}
   */
  protected function getUri(array $sites): string {
    return reset($sites);
  }

}
