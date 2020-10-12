<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Command\Helpers\PlatformCmdOutputFormatterTrait;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubDiff.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubDiff extends Command implements PlatformBootStrapCommandInterface {

  use PlatformCmdOutputFormatterTrait;

  /**
   * {@inheritDoc}
   */
  protected static $defaultName = 'ach:diff';

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
    $this->setDescription('Check whether the diff module codebase exists or not.');
    $this->setHidden('TRUE');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $module_list = \Drupal::getContainer()->get('extension.list.module');
    if (!$module_list->exists('diff')) {
      $output->writeln($this->toJsonSuccess([
        'base_url' => $input->getOption('uri'),
      ]));
      return 1;
    }

    return 0;
  }

}
