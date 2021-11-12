<?php

namespace Acquia\Console\ContentHub\EventSubscriber;

use Acquia\Console\Acsf\Platform\ACSFPlatform;
use Acquia\Console\Cloud\Platform\AcquiaCloudMultiSitePlatform;
use Acquia\Console\Cloud\Platform\AcquiaCloudPlatform;
use Acquia\Console\ContentHub\Command\ContentHubServiceUuid;
use Acquia\Console\ContentHub\ContentHubConsoleEvents;
use Acquia\Console\ContentHub\Event\ServiceClientUuidEvent;
use Acquia\Console\Helpers\Command\PlatformCmdOutputFormatterTrait;
use Acquia\Console\Helpers\Command\PlatformGroupTrait;
use Acquia\Console\Helpers\PlatformCommandExecutioner;
use EclipseGc\CommonConsole\PlatformInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class SetClientServiceUuid.
 *
 * @package Acquia\Console\ContentHub\EventSubscriber
 */
class SetClientServiceUuid implements EventSubscriberInterface {

  use PlatformCmdOutputFormatterTrait;
  use PlatformGroupTrait;

  public const GROUP_CONFIG_LOCATION = [
    '.commonconsole',
    'groups',
  ];

  /**
   * Platform Command executioner service.
   *
   * @var \Acquia\Console\Helpers\PlatformCommandExecutioner
   */
  protected $executioner;

  /**
   * SetClientServiceUuid constructor.
   *
   * @param \Acquia\Console\Helpers\PlatformCommandExecutioner $executioner
   *   Executioner service.
   */
  public function __construct(PlatformCommandExecutioner $executioner) {
    $this->executioner = $executioner;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ContentHubConsoleEvents::GET_SERVICE_CLIENT_UUID] = 'getClientServiceUuid';
    return $events;
  }

  /**
   * Set the service client uuid.
   *
   * @param \Acquia\Console\ContentHub\Event\ServiceClientUuidEvent $event
   *   Event to set the service client uuid.
   *
   * @throws \Symfony\Component\Console\Exception\ExceptionInterface
   */
  public function getClientServiceUuid(ServiceClientUuidEvent $event) {
    $platform = $event->getPlatform();
    $input = $event->getInput();
    $output = $event->getOutput();
    $raw = $this->executioner->runWithMemoryOutput(ContentHubServiceUuid::getDefaultName(), $platform, ['--uri' => $this->getUri($platform, $input, $output)]);
    $data = NULL;
    if (!$raw->getReturnCode()) {
      $lines = explode(PHP_EOL, trim($raw));
      foreach ($lines as $line) {
        $data = $this->fromJson($line, $event->getOutput());
        if (!$data) {
          continue;
        }
      }
      if ($data) {
        $event->setClientServiceUuid($data->service_client_uuid);
      }
    }
  }

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
  private function getUri(PlatformInterface $platform, InputInterface $input, OutputInterface $output): string {
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
