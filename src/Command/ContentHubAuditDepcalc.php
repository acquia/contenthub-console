<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubAuditDepcalc.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubAuditDepcalc extends ContentHubCommandBase implements PlatformBootStrapCommandInterface {

  /**
   * {@inheritDoc}
   */
  protected static $defaultName = 'ach:audit:check-depcalc';

  /**
   * {@inheritDoc}
   */
  protected function configure() {
    $this->setDescription('Check for depcalc module presence.');
    $this->setAliases(['ach-acd']);
  }

  /**
   * {@inheritDoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    if (!$this->drupalServiceFactory->getDrupalService('extension.list.module')->exists('depcalc')) {
      $output->writeln('<error>Depcalc module is missing from dependencies! Please run: composer require drupal/depcalc and deploy to your environment.</error>');
      return 2;
    }

    if (!$this->drupalServiceFactory->getDrupalService('module_handler')->moduleExists('depcalc')) {
      $output->writeln('<warning>Depcalc module is not enabled.</warning>');
      return 1;
    }

    $output->writeln('Depcalc module is present. You may proceed.');
    return 0;
  }

}
