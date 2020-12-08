<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Command\Helpers\PlatformCmdOutputFormatterTrait;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubDiff.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubDiff extends ContentHubCommandBase implements PlatformBootStrapCommandInterface {

  use PlatformCmdOutputFormatterTrait;

  /**
   * {@inheritDoc}
   */
  protected static $defaultName = 'ach:diff';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Check whether the diff module exists in the application codebase or not.');
    $this->setHidden('TRUE');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $module_list = $this->drupalServiceFactory->getDrupalService('extension.list.module');
    if (!$module_list->exists('diff')) {
      $output->writeln($this->toJsonSuccess([
        'base_url' => $input->getOption('uri'),
      ]));
      return 1;
    }

    return 0;
  }

}
