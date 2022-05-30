<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

/**
 * Thrown in case of pq command errors.
 *
 * Should be used in conjunction with ContentHubPqCommandErrors. Violations
 * do not count as command errors.
 *
 * @see \Acquia\Console\ContentHub\Command\PqCommands\ContentHubPqCommandErrors::newException()
 */
class PqCommandException extends \Exception {}
