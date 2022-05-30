<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

/**
 * Contains errors for pq commands.
 */
final class ContentHubPqCommandErrors {

  /**
   * Used during command option errors.
   *
   * Should be used with context.
   *
   * @var array
   */
  public static $invalidOptionErrorWithContext = [
    'code' => 3000,
    'message' => 'Invalid options: %s',
  ];

  /**
   * Drupal version compatibility error.
   *
   * @var array
   */
  public static $drupalCompatibilityError = [
    'code' => 3001,
    'message' => 'Your version of drupal is not supported by the module team! Full compatibility cannot be guaranteed.',
  ];

  /**
   * Used when the drupal version cannot be fetched.
   *
   * @var array
   */
  public static $drupalVersionRetrievalError = [
    'code' => 3002,
    'message' => 'Could not return latest drupal version',
  ];

  /**
   * Returns a new PqCommandException.
   *
   * @param array $error
   *   An associative array with 2 keys: code, message.
   * @param array $context
   *   The context for the message. Used for formatting the message string.
   *
   * @return \Acquia\Console\ContentHub\Command\PqCommands\PqCommandException
   *   The initialised PqCommandException instance.
   */
  public static function newException(array $error, array $context = []): PqCommandException {
    return new PqCommandException(
      empty($context) ? $error['message'] : sprintf($error['message'], ...$context),
      $error['code']
    );
  }

}
