<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
    $this->setDescription('Check for depcalc module presence.')
      ->addOption('fix', 'f', InputOption::VALUE_NONE, 'Enables depcalc module.')
      ->setHidden(TRUE)
      ->setAliases(['ach-acd']);
  }

  /**
   * {@inheritDoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    if (!$this->drupalServiceFactory->isModulePresentInCodebase('depcalc')) {
      $output->writeln('<error>Depcalc module is missing from dependencies! Please run: composer require drupal/depcalc and deploy to your environment.</error>');
      return 2;
    }

    $module_enabled = $this->drupalServiceFactory->isModuleEnabled('depcalc');
    if (!$module_enabled) {
      $output->writeln('<warning>Depcalc module is not enabled.</warning>');

      if ($input->hasOption('fix') && $input->getOption('fix')) {
        $enabled = $this->drupalServiceFactory->enableModules(['depcalc']);
        if ($enabled) {
          $output->writeln('<info>Depcalc module has been successfully enabled.</info>');
          return 0;
        }
        $output->writeln('<error>Depcalc module installation has failed!</error>');
      }
      $output->writeln('<info>Re-run the "ach:audit:check-depcalc" command with "--fix" option to enable the Depcalc module.</info>');
      return 1;
    }

    $output->writeln('<info>Depcalc module is enabled. You may proceed.</info>');
    return 0;
  }

}
