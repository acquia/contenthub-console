<?php

namespace Acquia\Console\ContentHub\EventSubscriber;

use Acquia\Console\ContentHub\Command\ContentHubServiceUuid;
use Acquia\Console\ContentHub\ContentHubConsoleEvents;
use Acquia\Console\ContentHub\Event\ServiceClientUuidEvent;
use Acquia\Console\Helpers\Command\PlatformCmdOutputFormatterTrait;
use Acquia\Console\Helpers\Command\PlatformGroupTrait;
use Acquia\Console\Helpers\PlatformCommandExecutioner;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class SetClientServiceUuid.
 *
 * @package Acquia\Console\ContentHub\EventSubscriber
 */
class SetClientServiceUuid implements EventSubscriberInterface {

  use PlatformCmdOutputFormatterTrait;
  use PlatformGroupTrait;

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
    $raw = $this->executioner->runWithMemoryOutput(ContentHubServiceUuid::getDefaultName(), $platform, [
      '--uri' => $this->getUri($platform, $input, $output),
    ]);
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

}
