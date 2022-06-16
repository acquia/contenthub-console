<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

use Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory;
use Acquia\Console\ContentHub\Command\Helpers\ModuleDiscoverer;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Provides general checks for the drupal sites.
 *
 * To add more module related specific checks, implement a method that starts
 * with moduleChecker{ModuleName} and uses the annotation tag @module which
 * follows a singular whitespace and the module's machine name. E.g.:
 * - @module node - see the example in the class. The method is automatically
 * picked up by the builtin registrar.
 */
class ContentHubPqModules extends ContentHubPqCommandBase {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:pq:modules';

  /**
   * The module discoverer service.
   *
   * @var \Acquia\Console\ContentHub\Command\Helpers\ModuleDiscoverer
   */
  protected $moduleDiscoverer;

  /**
   * The Drupal service factory service.
   *
   * @var \Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory
   */
  protected $serviceFactory;

  /**
   * Constructs a new ContentHubPqModules object.
   *
   * @param \Acquia\Console\ContentHub\Command\Helpers\ModuleDiscoverer $moduleDiscoverer
   *   The module discoverer service.
   * @param \Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory $drupalServiceFactory
   *   The Drupal service factory service.
   * @param string|null $name
   *   The name of this command.
   */
  public function __construct(ModuleDiscoverer $moduleDiscoverer, DrupalServiceFactory $drupalServiceFactory, string $name = NULL) {
    parent::__construct($name);

    $this->moduleDiscoverer = $moduleDiscoverer;
    $this->serviceFactory = $drupalServiceFactory;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this->setDescription('Runs module checks for the site');
  }

  /**
   * {@inheritdoc}
   */
  protected function runCommand(InputInterface $input, PqCommandResult $result): int {
    $modules = $this->moduleDiscoverer->getAvailableModules();
    $result->setIndicator(
      'Supported Modules',
      implode(', ', ModuleDiscoverer::getSupportedModules()),
      'The following module are supported by Content Hub Team.',
    );

    $this->executeModuleCheckers($modules, $result);

    return 0;
  }

  /**
   * Handles layout builder special case.
   *
   * @param \Acquia\Console\ContentHub\Command\PqCommands\PqCommandResult $result
   *   The pq command result object used to set KRI.
   *
   * @module layout_builder
   */
  public function moduleCheckerLayoutBuilder(PqCommandResult $result): void {
    $result->setIndicator(
      'Module: Layout Builder',
      'supported',
      "Translation specific layouts (provided by layout_builder_at) are not supported until it is enabled by core layout_builder. \nFor workaround contact the Content Hub Team.",
    );
  }

  /**
   * Handles panelizer special case.
   *
   * @param \Acquia\Console\ContentHub\Command\PqCommands\PqCommandResult $result
   *   The pq command result object used to set KRI.
   *
   * @module panelizer
   */
  public function moduleCheckerPanelizer(PqCommandResult $result): void {
    $result->setIndicator(
      'Module: Panelizer',
      'not supported',
      sprintf(
        PqCommandResultViolations::$unsupportedModule,
        'panelizer', 'incompatiblity'
      ),
      TRUE
    );
  }

  /**
   * Executes registered module handlers.
   *
   * @param array $modules
   *   The array of modules to check if there are handlers assigned to them.
   * @param \Acquia\Console\ContentHub\Command\PqCommands\PqCommandResult $result
   *   The pq command result object used to set KRI.
   *
   * @throws \ReflectionException
   */
  protected function executeModuleCheckers(array $modules, PqCommandResult $result): void {
    $handlers = $this->getModuleHandlers();
    $unifiedList = array_flip(array_merge($modules['core'], $modules['non-core']));
    foreach ($handlers as $module => $handler) {
      if (isset($unifiedList[$module])) {
        $this->{$handler}($result);
      }
    }
  }

  /**
   * Collects all the module handlers implemented in this class.
   *
   * @return array
   *   The handlers written in this class.
   *
   * @throws \ReflectionException
   */
  protected function getModuleHandlers(): array {
    $methods = get_class_methods($this);
    $handlers = [];
    foreach ($methods as $method) {
      if (strpos($method, 'moduleChecker') === FALSE) {
        continue;
      }

      $methodRef = new \ReflectionMethod(sprintf('%s::%s', ContentHubPqModules::class, $method));
      $module = $this->parseModuleTag($methodRef->getDocComment());
      if (!$module) {
        continue;
      }

      $handlers[$module] = $method;
    }

    return $handlers;
  }

  /**
   * Parses the module name.
   *
   * @param string $doc
   *   The doc comment to parse.
   *
   * @return string
   *   The module name.
   */
  protected function parseModuleTag(string $doc): string {
    preg_match('/@module\s[a-z_].*/', $doc, $matches);
    return str_replace('@module ', '', $matches[0]);
  }

}
