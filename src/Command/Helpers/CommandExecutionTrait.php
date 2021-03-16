<?php

namespace Acquia\Console\ContentHub\Command\Helpers;

use Acquia\Console\Acsf\Platform\ACSFPlatform;
use Acquia\Console\Cloud\Platform\AcquiaCloudPlatform;
use Acquia\Console\ContentHub\Client\AmplitudeClient;
use Acquia\Console\ContentHub\ContentHubConsoleEvents;
use Acquia\Console\ContentHub\Event\ServiceClientUuidEvent;
use Acquia\Console\Helpers\Command\CommandOptionsDefinitionTrait;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Trait CommandExecutionTrait.
 *
 * Usable within classes which inherited from Symfony/Command.
 *
 * @package Acquia\Console\ContentHub\Command\Helpers
 */
trait CommandExecutionTrait {

  use CommandOptionsDefinitionTrait;

  /**
   * Runs an arbitrary command with given options.
   *
   * Extract options from input and passes to "child" command if appropriate.
   *
   * @param string $command_name
   *   Command name to run.
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   Input interface.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   Output interface.
   *
   * @return int
   *   The exit code of the command.
   *
   * @throws \Exception
   * @throws \Symfony\Component\Console\Exception\ExceptionInterface
   */
  protected function executeCommand(string $command_name, InputInterface $input, OutputInterface $output): int {
    $args = [];
    /** @var \Symfony\Component\Console\Command\Command $command */
    $command = $this->getApplication()->find($command_name);
    $cmd_input = $this->getDefinitions($command);
    $options = $cmd_input->getOptions();
    foreach ($options as $option) {
      $name = $option->getName();
      if ($input->hasOption($name)) {
        $args["--${name}"] = $input->getOption($name);
      }
    }

    return $command->run(new ArrayInput($args), $output);
  }

  /**
   * Initializes Amplitude Client.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   Output stream.
   */
  protected function initializeAmplitudeClient(OutputInterface $output) {
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

}
