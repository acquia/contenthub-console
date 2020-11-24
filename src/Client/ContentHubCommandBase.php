<?php

namespace Acquia\Console\ContentHub\Client;

use Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory;
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
   * The drupal service factory.
   *
   * @var \Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory
   */
  protected $drupalServiceFactory;

  /**
   * {@inheritdoc}
   */
  protected function initialize(InputInterface $input, OutputInterface $output) {
    if (empty($this->drupalServiceFactory)) {
      $this->drupalServiceFactory = new DrupalServiceFactory();
    }
    if (empty($this->achClientService)) {
      $factory = new ContentHubClientFactory();
      $this->achClientService = $factory->getClient($this->drupalServiceFactory);
    }
  }

  /**
   * Sets achClientService instance.
   *
   * @param \Acquia\Console\ContentHub\Client\ContentHubServiceInterface $content_hub_service
   */
  public function setAchClientService(ContentHubServiceInterface $content_hub_service) {
    $this->achClientService = $content_hub_service;
  }

  /**
   * Sets drupalServiceFactory instance.
   *
   * @param \Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory $drupalServiceFactory
   */
  public function setDrupalServiceFactory(DrupalServiceFactory $drupalServiceFactory): void {
    $this->drupalServiceFactory = $drupalServiceFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlatformBootstrapType(): string {
    return 'drupal8';
  }

}
