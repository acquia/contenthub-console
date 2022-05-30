<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

/**
 * Represents the result of an ach:pq command run.
 */
class PqCommandResult {

  /**
   * List of key risk indicators.
   *
   * @var \Acquia\Console\ContentHub\Command\PqCommands\KeyRiskIndicator[]
   */
  private $data;

  /**
   * Constructs a new PqCommandResult object.
   *
   * @param \Acquia\Console\ContentHub\Command\PqCommands\KeyRiskIndicator[] $data
   *   The registered key risk indicators.
   */
  public function __construct(array $data = []) {
    $this->data = $data;
  }

  /**
   * Adds a new key risk indicator name and a value to the underlying data.
   *
   * @param string $kriName
   *   Key risk indicator name.
   * @param mixed $kriValue
   *   Indicator value.
   * @param string $message
   *   The indicator message.
   * @param bool $failed
   *   Indicator whether the check failed, i.e. the observed indicator is risky.
   */
  public function setIndicator(string $kriName, $kriValue, string $message, bool $failed = FALSE): void {
    $this->data[] = new KeyRiskIndicator(
      $kriName, $kriValue, $failed, $message,
    );
  }

  /**
   * Returns the array of key risk indicators.
   *
   * @return array|\Acquia\Console\ContentHub\Command\PqCommands\KeyRiskIndicator[]
   *   The list of indicators.
   */
  public function getResult(): array {
    return $this->data;
  }

}
