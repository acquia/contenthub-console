<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

use Acquia\Console\ContentHub\Command\ContentHubAudit;
use Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Checks hook implementations available for the module.
 */
class ContentHubPqCodeCheck extends ContentHubPqCommandBase {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:pq:code-check';

  /**
   * The Drupal service factory service.
   *
   * @var \Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory
   */
  protected $drupalServiceFactory;

  /**
   * Constructs a new ContentHubPqCodeCheck object.
   *
   * @param \Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory $drupalServiceFactory
   *   The Drupal service factory service.
   * @param string|null $name
   *   The name of the command.
   */
  public function __construct(DrupalServiceFactory $drupalServiceFactory, string $name = NULL) {
    parent::__construct($name);

    $this->drupalServiceFactory = $drupalServiceFactory;
  }

  /**
   * {@inheritdoc}
   */
  protected function runCommand(InputInterface $input, PqCommandResult $result): int {
    $hooksImplemented = $this->getHookImplementation();
    $kriName = 'Hooks Implemented';
    if (!empty($hooksImplemented)) {
      foreach ($hooksImplemented as $hook => $module_names) {
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
    $this->setDescription('Checks 1.x hooks custom implementations available.');
  }

  /**
   * Returns number of hook implementation.
   *
   * @return array
   *   The list of hook implementation.
   */
  public function getHookImplementation(): array {
    $hookImplementation = [];
    $moduleHandler = $this->drupalServiceFactory->getDrupalService('module_handler');
    foreach (ContentHubAudit::V1_MODULE_HOOKS as $hook) {
      if (!empty($moduleList = $moduleHandler->getImplementations($hook))) {
        $hookImplementation[$hook] = $moduleList;
      }
    }
    return $hookImplementation;
  }

}
