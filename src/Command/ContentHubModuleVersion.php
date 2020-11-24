<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use Acquia\Console\ContentHub\Command\Helpers\PlatformCmdOutputFormatterTrait;
use Acquia\Console\ContentHub\Command\Helpers\PlatformCommandExecutionTrait;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubModuleVersion.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubModuleVersion extends ContentHubCommandBase  implements PlatformBootStrapCommandInterface {

  use PlatformCmdOutputFormatterTrait;
  use PlatformCommandExecutionTrait;

  /**
   * {@inheritDoc}
   */
  protected static $defaultName = 'ach:module-version';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Check for the ContentHub module 2.x version.');
    $this->addOption('clear-cache','cr',InputOption::VALUE_OPTIONAL,'Clear cache.');
    $this->setHidden('TRUE');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $module_list = $this->drupalServiceFactory->getDrupalService('extension.list.module');
    if (!$module_list->exists('acquia_contenthub')) {
      return 1;
    }

    if ($input->getOption('clear-cache')) {
      $output->writeln('Clearing database cache...');
      $this->execDrushWithOutput($output, ['cr']);
    }

    $module_version = $this->drupalServiceFactory->getModuleVersion();
    $output->writeln($this->toJsonSuccess([
      'module_version' => $module_version,
      'base_url' => $input->hasOption('uri') ? $input->getOption('uri') : '',
    ]));

    return 0;
  }

}
