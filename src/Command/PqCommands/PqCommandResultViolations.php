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
  public static $hookImplemented = 'You have module that contains CH 1.x hook implementations.';

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
  public static $riskyBundles = 'Bundle and the number entity reference and entity reference revisions fields.' . PHP_EOL
  . 'Content Entity Type complexity is heavily influenced by these fields and can increase dependency calculation time.' . PHP_EOL
  . 'Bundles marked with red are paragraph references and should be handled with caution.';

  /**
   * Risky asmmetric paragraphs voilation.
   *
   * This imposes blocker to selective language imports for subscribers.
   *
   * @var string
   */
  public static $asymmetricParagraphs = 'There are asymmetric paragraphs which don\'t work well when working with acquia_contenthub_translations module when using selective language import.' . PHP_EOL
  . 'Please create translations for such paragraphs so that these paragraphs are symmetric.';

  /**
   * Incorrect paragraph configuration violations.
   *
   * @var string
   */
  public static $paragraphConfiguration = 'Multilingual paragraphs are not configured properly.' . PHP_EOL
  . 'Please enable translations for fields inside paragraph bundle except nested paragraph fields. ' . PHP_EOL
  . 'Follow https://www.drupal.org/docs/contributed-modules/paragraphs/multilingual-paragraphs-configuration for more information.';

  /**
   * Risky non-translatable entities and bundles.
   *
   * @var string
   */
  public static $nonTranslatables = 'There are non-translatable entities and bundles, only path_alias, file and redirect entities will be handled automatically, ' . PHP_EOL
  . 'any non-translatable entites other then these needs custom entity handler. ' . PHP_EOL
  . 'Follow https://docs.acquia.com/contenthub/enhanced-language-capabilities/#non-translatable-entities for more information.';

}
