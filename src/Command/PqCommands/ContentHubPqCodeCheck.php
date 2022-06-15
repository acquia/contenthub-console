<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

use Acquia\Console\ContentHub\Command\ContentHubAuditTrait;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Checks hook implementations available for the module.
 */
class ContentHubPqCodeCheck extends ContentHubPqCommandBase {

  use ContentHubAuditTrait;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:pq:code-check';

  /**
   * {@inheritdoc}
   */
  protected function runCommand(InputInterface $input, PqCommandResult $result): int {
    $hooks_implemented = $this->getHookImplementation();
    $kriName = 'Hooks Implemented (hook_name : module_names)';
    if (!empty($hooks_implemented)) {
      foreach ($hooks_implemented as $hook => $module_names) {
        $result->setIndicator(
          $kriName,
          $hook . ' : ' . implode(', ', $module_names),
          PqCommandResultViolations::$hookImplemented,
          TRUE,
        );
      }
      return 0;
    }

    $result->setIndicator(
      $kriName,
      '',
      'No unsupported hook implementations detected!'
    );
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this->setDescription('Checks hook implementation available.');
  }

  /**
   * Returns number of hook implementation.
   *
   * @return array
   *   The list of hook implementation.
   */
  public function getHookImplementation(): array {
    $hookImplementation = [];
    $kernel = \Drupal::service('kernel');
    $directories = [
      $kernel->getAppRoot(),
      "{$kernel->getAppRoot()}/{$kernel->getSitePath()}",
    ];
    foreach ($directories as $directory) {
      if (!file_exists("$directory/modules")) {
        continue;
      }
      $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator("$directory/modules"));
      $regex = new \RegexIterator($iterator, '/^.+\.module$/i', \RecursiveRegexIterator::GET_MATCH);

      foreach ($regex as $module_file) {
        $functions = $this->getModuleFunctions($module_file[0]);
        $file_info = pathinfo($module_file[0]);
        foreach ($this->hooks as $hook) {
          if (array_search("{$file_info['filename']}_$hook", $functions) !== FALSE) {
            $hookImplementation[$hook][] = $file_info['filename'];
          }
        }
      }
    }

    return $hookImplementation;
  }

}
