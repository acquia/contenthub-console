<?php

namespace Acquia\Console\ContentHub\Client;

/**
 * Trait ContentHubServiceCommonTrait.
 *
 * Contains common methods for handling requests and responses related to
 * Content Hub.
 *
 * @package Acquia\Console\ContentHub\Client
 */
trait ContentHubServiceCommonTrait {

  /**
   * Checks if the request was successful.
   *
   * @param array $response
   *   The decoded json response body.
   *
   * @return array
   *   The response data if the request was successful.
   *
   * @throws \Exception
   *   Thrown if the response is empty or the the expected data structure is
   *   malformed.
   */
  public function checkResponseSuccess(array $response) {
    if (empty($response) || !isset($response['success'])) {
      throw new \Exception('Unexpected error. The webhook url could not be deleted.');
    }

    if (isset($response['error']['message'])) {
      throw new \Exception("Error during webhook url removal: {$response['error']['message']}");
    }

    return $response;
  }

}
