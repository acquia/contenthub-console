<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Command\Helpers\DrushWrapper;
use Acquia\Console\ContentHub\Command\Helpers\PlatformCommandExecutionTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for checking drush version.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class DrushVersionCheck extends Command {

  use PlatformCommandExecutionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'drush:version';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Checks drush version on the server.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $output->writeln('Checking drush version...');
    $version = $this->runWithMemoryOutput(DrushWrapper::$defaultName, ['--drush_command' => 'version', '--drush_args' => ['format' => 'string']], '');
    print_r($version);exit;
    $version = $this->execDrush(['version', '--format=string'])->stdout;
    if (!$version) {
      $output->writeln('<comment>Attempted to run "drush". It might be missing or the executable name does not match the expected.</comment>');
      return 2;
    }

    $output->writeln(sprintf('Current drush version is: <info>%s</info>', $version));
    if (version_compare($version, '9.0.0', '<')) {
      $output->writeln('<error>Drush version must be 9.0.0 or higher!</error>');
      return 1;
    }

    return 0;
  }

}
