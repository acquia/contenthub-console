<?php

namespace Acquia\Console\ContentHub\Command\Helpers;

use EclipseGc\CommonConsole\PlatformInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Process\Process;

/**
 * Trait PlatformCommandExecutionTrait.
 *
 * @package Acquia\Console\ContentHub\Command\Helpers
 */
trait PlatformCommandExecutionTrait {

  use CommandOptionsDefinitionTrait;

  /**
   * Executes a command on the given platform and returns the output.
   *
   * @param string $cmd_name
   *   The name of the command to execute.
   * @param array $input
   *   The input for the command.
   * @param string $platform
   *   The name of the key of where the desired platform resides.
   *
   * @return object
   *   The output of the command execution.
   */
  protected function runWithMemoryOutput(string $cmd_name, array $input = [], string $platform = 'source'): object {
    /** @var \Symfony\Component\Console\Command\Command $command */
    $command = $this->getApplication()->find($cmd_name);
    $remote_output = new StreamOutput(fopen('php://memory', 'r+', false));
    // @todo LCH-4538 added this solution for fix the highlighting
    //  It fixes highlighting but PlatformCmdOutputFormatterTrait functions will work incorrectly
    //  $remote_output->setDecorated(TRUE);
    $input['--bare'] = NULL;
    $bind_input = new ArrayInput($input);
    $bind_input->bind($this->getDefinitions($command));
    $return_code = $this->getPlatform($platform)->execute($command, $bind_input, $remote_output);
    rewind($remote_output->getStream());
    return $this->formatReturnObject($return_code, $remote_output);
  }

  /**
   * Executes a command with given platform locally and returns the output.
   *
   * @param string $cmd_name
   *   The name of the command to execute.
   * @param \EclipseGc\CommonConsole\PlatformInterface $platform
   *   The name of the key of where the desired platform resides.
   * @param array $input
   *   The input for the command.
   *
   * @return object
   *   The output of the command execution.
   *
   * @throws \Exception
   */
  protected function runLocallyWithMemoryOutput(string $cmd_name, PlatformInterface $platform, array $input = []) {
    /** @var \Symfony\Component\Console\Command\Command $command */
    $command = $this->getApplication()->find($cmd_name);
    $remote_output = new StreamOutput(fopen('php://memory', 'r+', false));
    // @todo LCH-4538 added this solution for fix the highlighting
    //  It fixes highlighting but PlatformCmdOutputFormatterTrait functions will work incorrectly
    //  $remote_output->setDecorated(TRUE);
    $bind_input = new ArrayInput($input);
    $bind_input->bind($this->getDefinitions($command));
    $command->addPlatform($platform->getAlias(), $platform);
    $return_code = $command->run($bind_input, $remote_output);
    rewind($remote_output->getStream());

    return $this->formatReturnObject($return_code, $remote_output);
  }

  /**
   * Format command execution output.
   *
   * @param int $return_code
   *   Exit code.
   * @param \Symfony\Component\Console\Output\StreamOutput $remote_output
   *   StreamOutput after command run.
   *
   * @return object
   */
  protected function formatReturnObject(int $return_code, StreamOutput $remote_output) {
    return new class($return_code, stream_get_contents($remote_output->getStream()) ?? '') {
      public function __construct($returnCode, string $result) {
        $this->returnCode = $returnCode ?? -1;
        $this->result = $result;
      }

      public function getReturnCode() {
        return $this->returnCode;
      }

      public function __toString() {
        return $this->result;
      }
    };
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
   * @return \stdClass
   *   The command output, stderr and stdout.
   *
   * @throws \Exception
   */
  protected function execDrush(array $args, string $uri = '', string $path_to_drush = ''): \stdClass {
    $output = new \stdClass();
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
    $output->exitcode = $process->run();
    $output->stdout = trim($process->getOutput());
    $output->stderr = trim($process->getErrorOutput());

    return $output;
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
   * @param OutputInterface $output
   *  Output Interface.
   * @param array $args
   *   The arguments for the drush command.
   * @param string $uri
   *   [Optional] Specify an uri.
   * @param string $path_to_drush
   *   [Optional] Specify a path to drush.
   *
   * @return int
   *   The status.
   *
   * @throws \Exception
   */
  protected function execDrushWithOutput(OutputInterface $output, array $args, string $uri = '', string $path_to_drush = ''): int {
    $out = $this->execDrush($args, $uri, $path_to_drush);
    if ($out->exitcode) {
      $output->writeln(sprintf('<error>Error executing drush command "%s" (Exit code = %s):</error>', reset($args), $out->exitcode));
      $output->writeln($out->stderr);
      return 1;
    }
    $output->writeln($out->stdout);
    $output->writeln($out->stderr);
    return 0;
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
