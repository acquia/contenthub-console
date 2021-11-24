<?php

namespace Acquia\Console\ContentHub\Event;

use EclipseGc\CommonConsole\PlatformInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ServiceClientUuidEvent.
 */
class ServiceClientUuidEvent extends Event {

  /**
   * Platform to run the command on.
   *
   * @var \EclipseGc\CommonConsole\PlatformInterface
   */
  protected $platform;

  /**
   * Input stream.
   *
   * @var \Symfony\Component\Console\Input\InputInterface
   */
  protected $input;

  /**
   * Output stream.
   *
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  protected $output;

  /**
   * Content Hub Service Client Uuid specific to subscription.
   *
   * @var string
   */
  protected $clientServiceUuid = '';

  /**
   * ServiceClientUuidEvent constructor.
   *
   * @param \EclipseGc\CommonConsole\PlatformInterface $platform
   *   Platform to run the command on.
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   Input stream.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   Output stream.
   */
  public function __construct(PlatformInterface $platform, InputInterface $input, OutputInterface $output) {
    $this->platform = $platform;
    $this->input = $input;
    $this->output = $output;
  }

  /**
   * Returns the platform event holds.
   *
   * @return \EclipseGc\CommonConsole\PlatformInterface
   *   Platform object.
   */
  public function getPlatform() {
    return $this->platform;
  }

  /**
   * Returns the input stream event holds.
   *
   * @return \Symfony\Component\Console\Input\InputInterface
   *   Input stream object.
   */
  public function getInput() {
    return $this->input;
  }

  /**
   * Returns the output stream event holds.
   *
   * @return \Symfony\Component\Console\Output\OutputInterface
   *   Output stream object.
   */
  public function getOutput() {
    return $this->output;
  }

  /**
   * Sets the CH Service UUID.
   *
   * @param string $client_service_uuid
   *   Client Service UUID to set.
   */
  public function setClientServiceUuid(string $client_service_uuid) {
    $this->clientServiceUuid = $client_service_uuid;
  }

  /**
   * Returns the Client Service UUID.
   *
   * @return string
   *   Client Service UUID to return.
   */
  public function getClientServiceUuid(): string {
    return $this->clientServiceUuid;
  }

}
