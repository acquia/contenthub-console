<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use Acquia\Console\Helpers\Command\PlatformCmdOutputFormatterTrait;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubServiceUuid.
 */
class ContentHubServiceUuid extends ContentHubCommandBase implements PlatformBootStrapCommandInterface {

  use PlatformCmdOutputFormatterTrait;

  /**
   * {@inheritDoc}
   */
  protected static $defaultName = 'ach:get-ch-service-uuid';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Checks for Content Hub module 2.x version.');
    $this->setAliases(['ach-chuuid']);
    $this->setHidden('TRUE');
  }

  /**
   * {@inheritDoc}
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    $module_version = $this->drupalServiceFactory->getModuleVersion();
    if ($module_version === 2) {
      $remote_settings = $this->achClientService->getRemoteSettings();
      $uuid = $remote_settings['uuid'];
    }
    else {
      $settings_service = $this->drupalServiceFactory->getDrupalService('acquia_contenthub.acquia_contenthub_subscription');
      $uuid = $settings_service->getUuid();
    }
    if ($uuid) {
      $output->writeln($this->toJsonSuccess([
        'service_client_uuid' => $uuid,
      ]));
      return 0;
    }
    $output->writeln($this->toJsonError(
      '<error>Client Service Uuid doesn\'t exist</error>'
    ));
    return 1;
  }

}
