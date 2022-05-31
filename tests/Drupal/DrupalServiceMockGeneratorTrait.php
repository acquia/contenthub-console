<?php

namespace Acquia\Console\ContentHub\Tests\Drupal;

/**
 * Generates mock classes to substitute Drupal services.
 */
trait DrupalServiceMockGeneratorTrait {

  /**
   * Generates an anonymous with the provided methods.
   *
   * @param array $methods
   *   The array of methods in the following format.
   *
   * @code
   *   [
   *     'methodName' => 'stringReturnValue',
   *     'methodName' => 5,
   *   ];
   * @endcode
   *
   * @return object
   *   The anonymous object containing the methods provided as a parameter.
   */
  public static function generateDrupalServiceMock(array $methods): object {
    // @codingStandardsIgnoreStart
    return new class($methods) {
      private $methods;
      public function __construct($methods) {
        $this->methods = $methods;
      }
      public function __call($name, $arguments) {
        return $this->methods[$name];
      }
    };
    // @codingStandardsIgnoreEnd
  }

}
