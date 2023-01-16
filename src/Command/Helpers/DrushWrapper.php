<?php

namespace Acquia\Console\ContentHub\Command\Helpers;

use Acquia\Console\Helpers\Command\PlatformCmdOutputFormatterTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Responsible for wrapping executable drush commands.
 */
class DrushWrapper extends Command {

  use PlatformCmdOutputFormatterTrait;

  /**
   * {@inheritDoc}
   */
  public static $defaultName = 'ach:drush';

  /**
   * {@inheritDoc}
   */
  protected function configure() {
    $this
      ->setDescription("A wrapper for running Drush commands.")
      ->addOption('drush_command', 'cmd', InputOption::VALUE_OPTIONAL, "The drush command to run", "list")
      ->addOption('drush_args', 'da', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, "Any additional drush command arguments.", [])
      ->addOption('json', '', InputOption::VALUE_NONE, 'Format the drush command output into json')
      ->setAliases(['drush']);
  }

  /**
   * {@inheritDoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $eligible_drush_paths = [
      './vendor/bin/drush',
      '../vendor/bin/drush',
    ];

    $path_to_drush = '';
    foreach ($eligible_drush_paths as $path) {
      if (is_executable($path)) {
        $path_to_drush = $path;
        break;
      }
    }

    if (empty($path_to_drush)) {
      throw new \Exception('Drush executable not found!');
    }

    // This can be removed if we leverage DrushVersionCheck command in
    // migration start command.
    $check_drush_version_process = new Process([
      $path_to_drush,
      'version',
      '--format=string',
    ]);
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
    $process->setTimeout(600);

    $exit_code = $process->run();
    $exit_code === 0 ?
      $output->writeln(
        $this->toJsonSuccess(
          [
            // Actual drush command output.
            'drush_output' => $this->formatOutput(
              $process->getOutput(),
              $input->getOption('json')
            ),
            // This needs to be done because drush cr prints the output in
            // getErrorOutput().
            'drush_error' => $process->getErrorOutput(),
          ]
        ))
        : $output->writeln(
        $this->toJsonError(
          '<error>' . $process->getErrorOutput() . '</error>'
        ));

    return $exit_code;
  }

  /**
   * Returns the output in the specified format.
   *
   * @param string $output
   *   The output to format.
   * @param bool $json
   *   If true, formats the output into a json string.
   *
   * @return string
   *   The formatted output.
   */
  protected function formatOutput(string $output, bool $json): string {
    return $json ?
      json_decode($output, TRUE) :
      $output;
  }

}
