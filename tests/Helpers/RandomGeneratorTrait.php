<?php

namespace Helpers;

/**
 * Contains random generators.
 */
trait RandomGeneratorTrait {

  /**
   * Returns a random string.
   *
   * Character types: a-z, A-Z, num, spec.
   *
   * @param int $length
   *   The length of the string.
   * @param array $types
   *   The character types it should include.
   *
   * @return string
   *   The random generated string.
   *
   * @throws \Exception
   */
  public function generateString(int $length, array $types = ['a-z']): string {
    $charList = [
      'a-z' => 'abcdefghijklmnopqrstuvwxyz_',
      'A-Z' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
      'num' => '1234567890',
      'spec' => '!@#$%^&*()?|\/',
    ];

    $charList = array_intersect($charList, array_flip($types));
    $charList = implode('', $charList);

    $chars = [];
    for ($i = 0; $i < $length; $i++) {
      $chars[] = $charList[random_int(0, strlen($charList) - 1)];
    }
    return implode($chars);
  }

}
