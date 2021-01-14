<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use Acquia\Console\Helpers\Command\PlatformCmdOutputFormatterTrait;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubModuleVersion.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubModuleVersion extends ContentHubCommandBase implements PlatformBootStrapCommandInterface {

  use PlatformCmdOutputFormatterTrait;

  /**
   * {@inheritDoc}
   */
  protected static $defaultName = 'ach:module-version';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Checks for Content Hub module 2.x version.');
    $this->setHidden('TRUE');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    if (!$this->drupalServiceFactory->isModuleEnabled('acquia_contenthub')) {
      return 1;
    }

    $module_version = $this->drupalServiceFactory->getModuleVersion();
    $output->writeln($this->toJsonSuccess([
      'module_version' => $module_version,
      'base_url' => $input->hasOption('uri') ? $input->getOption('uri') : '',
    ]));

    return 0;
  }

}
