<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

final class ContentHubPqCommandErrors {

  public static $invalidOptionErrorWithContext = [
    'code' => 3000,
    'message' => 'Invalid options: %s',
  ];

  public static $drupalCompatibilityError = [
    'code' => 3001,
    'message' => 'Your version of drupal is not supported by the module team! Full compatibility cannot be guaranteed.',
  ];

  public static function newException(array $error, array $context = []) {
    return new PqCommandException(
      empty($context) ? $error['message'] : sprintf($error['message'], ...$context),
      $error['code']
    );
  }

}
