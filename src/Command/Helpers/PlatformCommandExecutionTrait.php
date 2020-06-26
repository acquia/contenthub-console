<?php

namespace Acquia\Console\ContentHub\Command\Helpers;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Process\Process;

/**
 * Trait PlatformCommandExecutionTrait.
 *
 * @package Acquia\Console\ContentHub\Command\Helpers
 */
trait PlatformCommandExecutionTrait {

  /**
   * Executes a command on the given platform and returns the output.
   *
   * @param string $cmd_name
   *   The name of the command to execute.
   * @param array $input
   *   The input for the command.
   *
   * @return string
   *   The output of the command execution.
   */
  protected function runWithMemoryOutput(string $cmd_name, array $input = []): string {
    $command = $this->getApplication()->find($cmd_name);
    $input = array_merge([
      'command' => $cmd_name,
    ], $input);
    $remote_output = new StreamOutput(fopen('php://memory', 'r+', false));
    $this->getPlatform('source')->execute($command, new ArrayInput($input), $remote_output);
    rewind($remote_output->getStream());
    return stream_get_contents($remote_output->getStream()) ?: '';
  }

  /**
   * Executes an arbitrary drush command.
   *
   * By default this method tries 3 different paths to drush.
   *  - drush (globally)
   *  - ./vendor/bin/drush
   *  - ../vendor/bin/drush (just in case)
   * In case none of them works, there's still a possibility to specify the
   * executable.
   *
   * @param array $args
   *   The arguments for the drush command.
   * @param string $uri
   *   [Optional] Specify an uri.
   * @param string $path_to_drush
   *   [Optional] Specify a path to drush.
   *
   * @return \Acquia\Console\ContentHub\Command\Helpers\PlatformOutput
   *   The command output, stderr and stdout.
   *
   * @todo revisit this. There is no PlatformOutput object and I don't think anything's calling this method.
   *
   * @throws \Exception
   */
  protected function execDrush(array $args, string $uri = '', string $path_to_drush = ''): PlatformOutput {
    $output = new PlatformOutput();
    $paths_to_drush = [
      $path_to_drush ?: $this->getDrushExecFromPath(),
      './vendor/bin/drush',
      '../vendor/bin/drush',
    ];

    if ($uri) {
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
    $process->run();
    $output->stdout = trim($process->getOutput());
    $output->stderr = trim($process->getErrorOutput());

    return $output;
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
