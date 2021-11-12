<?php

namespace Acquia\Console\ContentHub\Command\Helpers;

use Acquia\Console\ContentHub\Client\AmplitudeClient;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interface defined to initialize Amplitude Client for the commands.
 */
interface AmplitudeClientInterface {

  public const SERVICE_UUID_KEY = 'acquia.cloud.service.uuid';

  /**
   * Initializes Amplitude Client.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   Input stream instance.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   Output stream instance.
   */
  public function initializeAmplitudeClient(InputInterface $input, OutputInterface $output): void;

  /**
   * Sets the amplitude client object.
   *
   * @param \Acquia\Console\ContentHub\Client\AmplitudeClient $amplitude_client
   *   Amplitude Client.
   */
  public function setAmplitudeClient(AmplitudeClient $amplitude_client): void;

  /**
   * Helper method to send logs to amplitude.
   *
   * @param string $event_name
   *   Event to send to amplitude.
   * @param int $step
   *   Upgrade step user currently is on.
   * @param string $message
   *   Message to be shown for this step.
   */
  public function sendLogsToAmplitude(string $event_name, int $step, string $message): void;

}
