<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

/**
 * Represents a key risk indicator.
 */
class KeyRiskIndicator {

  /**
   * The name of the indicator.
   *
   * @var string
   */
  protected $name;

  /**
   * The vale of the indicator.
   *
   * @var string
   */
  protected $value;

  /**
   * Whether the indicator should be considered as a risk.
   *
   * @var bool
   */
  protected $risky;

  /**
   * Any note related to the indicator.
   *
   * @var string
   */
  protected $message;

  /**
   * Constructs a new KeyRiskIndicatorObject.
   *
   * @param string $name
   *   The name of the indicator.
   * @param string $value
   *   The value of the indicator.
   * @param bool $risky
   *   Whether it should be considered as a risk.
   * @param string $message
   *   A note for the indicator.
   */
  public function __construct(string $name, string $value, bool $risky, string $message) {
    $this->name = $name;
    $this->value = $value;
    $this->risky = $risky;
    $this->message = $message;
  }

  /**
   * Converts the object to array.
   *
   * @return array
   *   The converted object as an array.
   */
  public function toArray(): array {
    return [
      'name' => $this->name,
      'value' => $this->value,
      'message' => $this->message,
      'risky' => $this->risky,
    ];
  }

}
