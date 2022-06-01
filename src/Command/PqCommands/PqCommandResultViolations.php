<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

/**
 * Container of pre-qualification violations.
 */
final class PqCommandResultViolations {

  /**
   * Drupal version compatibility violation.
   *
   * @var string
   */
  public static $drupalCompatibility = 'Your version of drupal is not supported by the module team! Full compatibility cannot be guaranteed.';

  /**
   * Outdated acquia_contenthub module version violation.
   *
   * @var string
   */
  public static $moduleVersionOutdated = 'Your version of acquia_contenthub module is outdated. Update is advised!';

  /**
   * Unsupported entity types violation.
   *
   * This doens't necessarily mean that it imposes a blocker to the
   * implementation.
   *
   * @var string
   */
  public static $unsupportedEntityTypes = 'You have entity types that are not explicitly supported by Content Hub Team. This increases risk factor, should not be considered as a blocker.';

}
