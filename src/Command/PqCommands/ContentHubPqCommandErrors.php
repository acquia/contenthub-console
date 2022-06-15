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
   * Used when the version cannot be fetched.
   *
   * @var array
   */
  public static $versionRetrievalErrorWithContext = [
    'code' => 3001,
    'message' => 'Could not return latest %s version',
  ];

  /**
   * Thrown when depcalc is not enabled on the site.
   *
   * @var array
   */
  public static $depcalcIsNotEnabledError = [
    'code' => 3002,
    'message' => 'Depcalc is not enabled on the site',
  ];

  /**
   * Thrown when bundle was not found for the provided entity type.
   *
   * @var array
   */
  public static $bundleDoesNotExistErrorWithContext = [
    'code' => 3003,
    'message' => 'The provided bundle(s) "%s" for "%s" does not exist',
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
