<?php

namespace Acquia\Console\ContentHub\EventSubscriber;

use Acquia\Console\Acsf\Platform\ACSFPlatform;
use Acquia\Console\Cloud\Platform\AcquiaCloudMultiSitePlatform;
use Acquia\Console\Cloud\Platform\AcquiaCloudPlatform;
use Acquia\Console\ContentHub\Command\ContentHubServiceUuid;
use Acquia\Console\ContentHub\ContentHubConsoleEvents;
use Acquia\Console\ContentHub\Event\ServiceClientUuidEvent;
use Acquia\Console\Helpers\Command\PlatformCmdOutputFormatterTrait;
use Acquia\Console\Helpers\PlatformCommandExecutioner;
use EclipseGc\CommonConsole\PlatformInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class SetClientServiceUuid.
 *
 * @package Acquia\Console\ContentHub\EventSubscriber
 */
class SetClientServiceUuid implements EventSubscriberInterface {

  use PlatformCmdOutputFormatterTrait;

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
    $raw = $this->executioner->runWithMemoryOutput(ContentHubServiceUuid::getDefaultName(), $platform, ['--uri' => $this->getUri($platform)]);
    $data = $result = NULL;
    if (!$raw->getReturnCode()) {
      $lines = explode(PHP_EOL, trim($raw));
      foreach ($lines as $line) {
        $data = $this->fromJson($line, $event->getOutput());
        if (!$data) {
          continue;
        }
        $result = $data;
      }
      if ($result) {
        $event->setClientServiceUuid($result->service_client_uuid);
      }
    }
  }

  /**
   * Gets the uri of one of the sites.
   *
   * @param \EclipseGc\CommonConsole\PlatformInterface $platform
   *   Platform object.
   *
   * @return string
   *   URI to return.
   */
  private function getUri(PlatformInterface $platform): string {
    $uri = '';
    switch ($platform->getPlatformId()) {
      case AcquiaCloudMultiSitePlatform::PLATFORM_NAME:
        $sites = $platform->getMultiSites();
        $uri = reset($sites);
        break;

      case AcquiaCloudPlatform::PLATFORM_NAME:
      case ACSFPlatform::PLATFORM_NAME:
        $sites = $platform->getPlatformSites();
        $site = reset($sites);
        $uri = $site['uri'];
        break;
    }
    return $uri;
  }

}
