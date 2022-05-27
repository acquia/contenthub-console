<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

/**
 * Base class for ach:pq commands.
 */
abstract class ContentHubPqCommandBase extends Command implements PlatformBootStrapCommandInterface {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format <json|table>', 'table');
  }

  /**
   * {@inheritdoc}
   */
  public function getPlatformBootstrapType(): string {
    return 'drupal8';
  }

}
