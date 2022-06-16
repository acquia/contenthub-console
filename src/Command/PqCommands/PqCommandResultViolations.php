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
   * This doesn't necessarily mean that it imposes a blocker to the
   * implementation.
   *
   * @var string
   */
  public static $unsupportedEntityTypes = 'You have entity types that are not explicitly supported by Content Hub Team. This increases risk factor, should not be considered as a blocker.';

  /**
   * Hook implementation violation.
   *
   * @var string
   */
  public static $hookImplemented = 'You have module that contains hook implementations';

  /**
   * Dependency count is higher than the specified safety threshold.
   *
   * @var string
   */
  public static $dependencyCount = 'The number of eligible dependencies is high. Consider using a gradual export approach or decrease dependencies.';

  /**
   * The configured depcalc cache bin is lower than the actual number of deps.
   *
   * @var string
   */
  public static $depcalcCacheMaxRows = 'The number of rows depcalc is allowed to cache is too low! Please increase the configured amount!';

  /**
   * A module is unsupported because of the reason included.
   *
   * @var string
   */
  public static $unsupportedModule = '%s module is unsupported. Reason: %s';

  /**
   * Risky bundles violation.
   *
   * This doesn't necessarily mean that it imposes a blocker to the
   * implementation.
   *
   * @var string
   */
  public static $riskyBundles = 'These content types have complex fields like entity reference and entity reference revisions' . PHP_EOL . 'and can increase dependency calculation time.';

  /**
   * Bundles with paragraph violation.
   *
   * This doesn't necessarily mean that it imposes a blocker to the
   * implementation.
   *
   * @var string
   */
  public static $paragraphBundles = 'You have content types which have paragraph fields which can make dependency calculation complex and circular.'
  . PHP_EOL
  . 'This increases risk factor, should not be considered as a blocker.';

}
