<?php

namespace Acquia\Console\ContentHub\Command\Helpers;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Trait CommandExecutionTrait.
 *
 * Usable within classes which inherited from Symfony/Command.
 *
 * @package Acquia\Console\ContentHub\Command\Helpers
 */
trait CommandExecutionTrait {

  /**
   * Runs an arbitrary command with given options.
   *
   * Extract options from input and passes to "child" command if appropriate.
   *
   * @param string $command_name
   *   Command name to run.
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   Input interface.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   Output interface.
   *
   * @return int
   *   The exit code of the command.
   *
   * @throws \Exception
   */
  protected function executeCommand(string $command_name, InputInterface $input, OutputInterface $output): int {
    $args = [];
    /** @var \Symfony\Component\Console\Command\Command $command */
    $command = $this->getApplication()->find($command_name);
    $options = $command->getDefinition()->getOptions();
    foreach ($options as $option) {
      $name = $option->getName();
      if ($input->hasOption($name)) {
        $args["--${name}"] = $input->getOption($name);
      }
    }

    return $command->run(new ArrayInput($args), $output);
  }

}
