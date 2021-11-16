<?php

namespace Acquia\Console\ContentHub\Command\Helpers;

use Acquia\Console\Acsf\Platform\ACSFPlatform;
use Acquia\Console\Cloud\Platform\AcquiaCloudMultiSitePlatform;
use Acquia\Console\Cloud\Platform\AcquiaCloudPlatform;
use EclipseGc\CommonConsole\PlatformInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Trait PlatformSitesTrait.
 *
 * Used for fetching the platform sites.
 *
 * @package Acquia\Console\ContentHub\Command\Helpers
 */
trait PlatformSitesTrait {

  /**
   * Gets the uri of one of the sites.
   *
   * @param \EclipseGc\CommonConsole\PlatformInterface $platform
   *   Platform object.
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   Input stream.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   Output stream.
   *
   * @return string
   *   URI to return.
   */
  protected function getUri(PlatformInterface $platform, InputInterface $input, OutputInterface $output): string {
    $sites = [];
    $platform_id = $platform->getPlatformId();
    switch ($platform_id) {
      case AcquiaCloudMultiSitePlatform::PLATFORM_NAME:
        $sites = $platform->getMultiSites();
        break;

      case AcquiaCloudPlatform::PLATFORM_NAME:
      case ACSFPlatform::PLATFORM_NAME:
        $sites = $platform->getPlatformSites();
        break;
    }
    $group_name = $input->hasOption('group') ? $input->getOption('group') : '';
    if (!empty($group_name)) {
      $alias = $platform->getAlias();
      $sites = $this->filterSitesByGroup($group_name, $sites, $output, $alias, $platform_id);
    }

    $site_info = reset($sites);
    return $site_info['uri'] ?? $site_info;
  }

}
