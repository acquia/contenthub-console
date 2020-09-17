<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Client\ContentHubClientFactory;
use Acquia\Console\ContentHub\Command\Helpers\PlatformCmdOutputFormatterTrait;
use Acquia\Console\ContentHub\Command\Helpers\PlatformCommandExecutionTrait;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubModuleVersion.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubModuleVersion extends Command  implements PlatformBootStrapCommandInterface {

  use PlatformCmdOutputFormatterTrait;
  use PlatformCommandExecutionTrait;

  /**
   * {@inheritDoc}
   */
  protected static $defaultName = 'ach:module-version';

  /**
   * @inheritDoc
   */
  public function getPlatformBootstrapType(): string {
    return 'drupal8';
  }

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
    $module_list = \Drupal::getContainer()->get('extension.list.module');
    if (!$module_list->exists('acquia_contenthub')) {
      return 1;
    }

    if ($input->getOption('clear-cache')) {
      $output->writeln('Clearing database cache...');
      $this->execDrushWithOutput($output, ['cr']);
    }

    $module_version = ContentHubClientFactory::getModuleVersion();
    $output->writeln($this->toJsonSuccess([
      'module_version' => $module_version,
      'base_url' => $input->getOption('uri'),
    ]));

    return 0;
  }

}
