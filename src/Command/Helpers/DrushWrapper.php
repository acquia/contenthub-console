<?php

namespace Acquia\Console\ContentHub\Command\Helpers;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class DrushWrapper extends Command {

  public static $defaultName = 'ach:drush';

  /**
   * {@inheritDoc}
   */
  protected function configure() {
    $this->setDescription("A wrapper for running Drush commands.");
    $this->addArgument('drush_command', InputArgument::OPTIONAL, "The drush command to run", "list");
    $this->addOption('drush_args', 'da', InputOption::VALUE_OPTIONAL, "Any additional drush command arguments.", []);
  }

  /**
   * {@inheritDoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $paths_to_drush = [
      $this->getDrushExecFromPath(),
      './vendor/bin/drush',
      '../vendor/bin/drush',
    ];

    $args = [$input->getArgument('drush_command')];

    if ($input->hasOption('drush_args') && $drush_args = $input->getOption('drush_args')) {
      array_unshift($args, $drush_args);
    }
    if ($input->hasOption('uri') && $uri = $input->getOption('uri')) {
      array_unshift($args, "--uri=$uri");
    }

    $match = '';
    foreach ($paths_to_drush as $path) {
      if (is_executable($path)) {
        $match = $path;
        break;
      }
    }

    if (!$match) {
      throw new \Exception('Drush executable not found!');
    }

    $process = new Process(array_merge([$match], $args));

    $exit_code = $process->run();
    $output->writeln($process->getOutput());
    $output->writeln($process->getErrorOutput());

    return $exit_code;
  }

  /**
   * Attempts to return the path to drush executable from PATH env var.
   *
   * @return string
   *   The absolute path to drush executable.
   */
  public function getDrushExecFromPath(): string {
    $path = explode(':', getenv('PATH'));
    if (empty($path)) {
      return '';
    }

    foreach ($path as $component) {
      if (is_executable($component . '/drush')) {
        return $component . '/drush';
      }
    }

    return '';
  }

}
