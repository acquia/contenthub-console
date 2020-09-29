<?php

namespace Acquia\Console\ContentHub\Command\Helpers;

use Symfony\Component\Console\Command\Command;

/**
 * Trait CommandOptionsDefinitionTrait.
 *
 * @package Acquia\Console\ContentHub\Command\Helpers
 */
trait CommandOptionsDefinitionTrait {

  /**
   * Merges the InputDefinition Options from the Application into the Command and returns the full Input.
   *
   * @param \Symfony\Component\Console\Command\Command $command
   *   The command executed.
   *
   * @return \Symfony\Component\Console\Input\InputDefinition
   *   The complete input definition.
   */
  protected function getDefinitions(Command $command) {
    /** @var \Symfony\Component\Console\Input\InputDefinition $definition */
    $definition = $command->getDefinition();
    /** @var \Symfony\Component\Console\Input\InputDefinition $application_definition */
    $application_definition = $command->getApplication()->getDefinition();
    foreach ($application_definition->getOptions() as $option) {
      $definition->addOption($option);
    }
    return $definition;
  }
}
