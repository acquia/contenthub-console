<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubPanelizer.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubPanelizer extends ContentHubCommandBase  implements PlatformBootStrapCommandInterface {

  /**
   * {@inheritdoc}
   */
  protected  static $defaultName = 'ach:panelizer-check';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Checks use of Panelizer module.');
    $this->setAliases(['ach-pan']);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    if (!\Drupal::getContainer()->get('extension.list.module')->exists('panelizer')) {
      $output->writeln('Panelizer module is not present.');
      return 0;
    }

    if (!\Drupal::moduleHandler()->moduleExists('panelizer')) {
      $output->writeln('Panelizer module present but not installed');
      return 0;
    }

    $output->writeln('<comment>Panelizer module installed. Please consider using Layout builder instead!</comment>');
    return 0;
  }
}
