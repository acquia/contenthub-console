<?php

namespace Acquia\Console\ContentHub\Client;

use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubCommandBase.
 *
 * @package Acquia\Console\ContentHub\Client
 */
abstract class ContentHubCommandBase extends Command implements PlatformBootStrapCommandInterface {

  /**
   * The applicable Content Hub Client.
   *
   * @var \Acquia\Console\ContentHub\Client\ContentHubServiceInterface
   */
  protected $achClientService;

  /**
   * {@inheritdoc}
   */
  protected function initialize(InputInterface $input, OutputInterface $output) {
    $factory = new ContentHubClientFactory();
    $this->achClientService = $factory->getClient();

    if (!$this->achClientService) {
      throw new \Exception('Could not connect to Content Hub, the client could not be instantiated.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPlatformBootstrapType(): string {
    return 'drupal8';
  }

}
