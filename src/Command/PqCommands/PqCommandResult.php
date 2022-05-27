<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

use Symfony\Component\Console\Output\OutputInterface;

class PqCommandResult {

  /**
   * @var array
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
   */
  public function setIndicator(string $kriName, $kriValue, $message, $failed = FALSE): void {
    $this->data[] = new KeyRiskIndicator(
      $kriName, $kriValue, $failed, $message,
    );
  }

  public function getResult(): array {
    return $this->data;
  }

}
