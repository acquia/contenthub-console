<?php

namespace Acquia\Console\ContentHub\Command\Helpers;

use Acquia\Console\Acsf\Platform\ACSFPlatform;
use Acquia\Console\Cloud\Platform\AcquiaCloudPlatform;
use Acquia\Console\ContentHub\Client\AmplitudeClient;
use Acquia\Console\ContentHub\ContentHubConsoleEvents;
use Acquia\Console\ContentHub\Event\ServiceClientUuidEvent;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Trait AmplitudeClientTrait.
 *
 * Used for initializing Amplitude Client and common methods.
 *
 * @package Acquia\Console\ContentHub\Command\Helpers
 */
trait AmplitudeClientTrait {

  /**
   * User details for amplitude tracking.
   *
   * @var array
   */
  protected $userDetails = [];

  /**
   * Amplitude Client to log events.
   *
   * @var \Acquia\Console\ContentHub\Client\AmplitudeClient
   */
  protected $amplitudeClient;

  /**
   * {@inheritDoc}
   */
  public function initializeAmplitudeClient(OutputInterface $output): void {
    if (empty($this->amplitudeClient)) {
      $platform = $this->getPlatform('source');
      $client_origin_uuid = $platform->get(self::SERVICE_UUID_KEY);
      if (!$client_origin_uuid) {
        $event = new ServiceClientUuidEvent($platform, $output);
        $this->dispatcher->dispatch(ContentHubConsoleEvents::GET_SERVICE_CLIENT_UUID, $event);
        $client_origin_uuid = $event->getClientServiceUuid();
        if (empty($client_origin_uuid)) {
          throw new \Exception(sprintf('Service Subscription UUID missing.'));
        }
        $platform->set(self::SERVICE_UUID_KEY, $client_origin_uuid);
        $platform->save();
      }
      $user_details = [];
      if ($platform->getPlatformId() === ACSFPlatform::PLATFORM_NAME) {
        $application_id = $platform->get(AcquiaCloudPlatform::ACE_APPLICATION_ID);
        $environment_id = $platform->get(AcquiaCloudPlatform::ACE_ENVIRONMENT_NAME);
        $user_details[$application_id] = $environment_id;
      }
      else {
        $user_details = $platform->get(AcquiaCloudPlatform::ACE_ENVIRONMENT_DETAILS);
      }
      $this->userDetails = $user_details;
      $this->amplitudeClient = new AmplitudeClient($client_origin_uuid);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function setAmplitudeClient(AmplitudeClient $amplitude_client): void {
    $this->amplitudeClient = $amplitude_client;
  }

  /**
   * {@inheritDoc}
   */
  public function sendLogsToAmplitude(string $event_name, int $step, string $message): void {
    $this
      ->amplitudeClient
      ->logEvent($event_name, array_merge($this->userDetails, [
        'step' => $step,
        'message' => $message,
      ]));
  }

}
