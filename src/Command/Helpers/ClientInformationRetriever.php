<?php

namespace Acquia\Console\ContentHub\Command\Helpers;

use Acquia\Console\ContentHub\Command\ContentHubModuleTrait;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ClientInformationRetriever.
 *
 * Returns information about the client from the site it ran on.
 *
 * @package Acquia\Console\ContentHub\Command\Helpers
 */
class ClientInformationRetriever extends Command implements PlatformBootStrapCommandInterface {

  use PlatformCmdOutputFormatterTrait;
  use ContentHubModuleTrait;

  /**
   * {@inheritDoc}
   */
  protected static $defaultName = 'ach:retrieve-client';

  /**
   * {@inheritDoc}
   */
  public function getPlatformBootstrapType(): string {
    return 'drupal8';
  }

  /**
   * {@inheritDoc}
   */
  protected function configure() {
    $this->setHidden(TRUE);
  }

  /**
   * {@inheritDoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $settings = \Drupal::config('acquia_contenthub.admin_settings');
    if ($settings->isNew()) {
      $output->writeln($this->toJsonError(
        'Content Hub config does not exist.'
      ));
      return 1;
    }

    $output->writeln($this->toJsonSuccess([
      'origin' => $settings->get('origin'),
      'client_name' => $settings->get('client_name'),
      'publisher' => $this->isPublisher(),
      'site' => $input->getOption('uri') ?? '',
    ]));
    return 0;
  }

}
