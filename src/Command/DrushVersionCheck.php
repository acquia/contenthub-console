<?php

namespace Acquia\Console\ContentHub\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Command for checking drush version.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class DrushVersionCheck extends Command {

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
    $version = $this->getDrushVersion();
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

  /**
   * Returns the current drush version.
   *
   * @return string
   *   The drush version.
   */
  protected function getDrushVersion(): string {
    $process = new Process(['drush', 'version', '--format=string']);
    $process->run();
    $output = $process->getOutput();

    return trim($output);
  }

}
