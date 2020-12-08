<?php

namespace Acquia\Console\ContentHub\Command\Helpers;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class DrushWrapper extends Command {

  use PlatformCmdOutputFormatterTrait;

  public static $defaultName = 'ach:drush';

  /**
   * {@inheritDoc}
   */
  protected function configure() {
    $this->setDescription("A wrapper for running Drush commands.");
    $this->addOption('drush_command', 'cmd', InputOption::VALUE_OPTIONAL, "The drush command to run", "list");
    $this->addOption('drush_args', 'da', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, "Any additional drush command arguments.", []);
  }

  /**
   * {@inheritDoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    // Only ./vendor/bin/drush needs to be checked because commoncli is executed from vendor path
    // which is configured in the platform configuration.
    // So we are always executing it from vendor path configured in the platform config
    // so we don't need to check ../vendor/bin/drush.
    $path_to_drush = './vendor/bin/drush';

    if (!is_executable($path_to_drush)) {
      throw new \Exception('Drush executable not found!');
    }

    // This can be removed if we leverage DrushVersionCheck command in migration start command.
    $check_drush_version_process = new Process([$path_to_drush, 'version', '--format=string']);
    $check_drush_version_process->run();
    $version = $check_drush_version_process->getOutput();
    if (version_compare($version, '9.0.0', '<')) {
      throw new \Exception('Drush version must be 9.0.0 or higher!');
    }

    $match = $path_to_drush;

    $args = [$input->getOption('drush_command')];

    if ($input->hasOption('drush_args') && $drush_args = $input->getOption('drush_args')) {
      $args = array_merge($args, $drush_args);
    }
    if ($input->hasOption('uri') && $uri = $input->getOption('uri')) {
      array_unshift($args, "--uri=$uri");
    }

    $process = new Process(array_merge([$match], $args));

    $exit_code = $process->run();
    $exit_code === 0 ?
      $output->writeln(
        $this->toJsonSuccess(
          [
            // Actual drush command output.
            'drush_output' => $process->getOutput(),
            // This needs to be done because drush cr prints the output in getErrorOutput().
            'drush_error' => $process->getErrorOutput()
          ]
        ))
        : $output->writeln(
        $this->toJsonError(
          '<error>' . $process->getErrorOutput() . '</error>'
        ));

    return $exit_code;
  }

}
