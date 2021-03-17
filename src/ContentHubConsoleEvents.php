<?php

namespace Acquia\Console\ContentHub;

/**
 * Class to declared the events used in CHUC.
 */
final class ContentHubConsoleEvents {

  /**
   * Dispatched to get the CH service UUID and set in the platform config.
   *
   * @see \ServiceClientUuidEvent
   */
  const GET_SERVICE_CLIENT_UUID = 'chuc.service.uuid';

}
