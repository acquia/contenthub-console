<?php

namespace Acquia\Console\ContentHub\Command;

use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubAuditDepcalc.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubAuditDepcalc extends Command implements PlatformBootStrapCommandInterface {

  /**
   * {@inheritDoc}
   */
  protected static $defaultName = 'ach:audit:check-depcalc';

  /**
   * @inheritDoc
   */
  public function getPlatformBootstrapType(): string {
    return 'drupal8';
  }

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
    if (!\Drupal::getContainer()->get('extension.list.module')->exists('depcalc')) {
      $output->writeln('<error>Depcalc module is missing from dependencies! Please run: composer require drupal/depcalc and deploy to your environment.</error>');
      return 2;
    }

    if (!\Drupal::moduleHandler()->moduleExists('depcalc')) {
      $output->writeln('<warning>Depcalc module is not enabled.</warning>');
      return 1;
    }

    $output->writeln('Depcalc module is present. You may proceed.');
    return 0;
  }

}
