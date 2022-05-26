<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Command\Command;

abstract class ContentHubPqCommandBase extends Command implements PlatformBootStrapCommandInterface {

  /**
   * {@inheritdoc}
   */
  public function getPlatformBootstrapType(): string {
    return 'drupal8';
  }

}
