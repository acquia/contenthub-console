<?php

namespace Acquia\Console\ContentHub\Exception;

use Throwable;

/**
 * Class ContentHubVersionException.
 *
 * Thrown when inappropriate Content Hub version is used.
 *
 * @package Acquia\Console\ContentHub\Exception
 */
class ContentHubVersionException extends \Exception {

  protected $message = "Invalid version. Version '%s' should be used.";

  /**
   * ContentHubVersionException constructor.
   *
   * @param int $version
   *   The content hub version.
   * @param int $code
   *   [Optional] The Exception code.
   * @param Throwable $previous
   *   [Optional] The previous throwable used for the exception chaining.
   */
  public function __construct(int $version, int $code = 0, Throwable $previous = NULL) {
    parent::__construct(sprintf($this->message, $version), $code, $previous);
  }

}
