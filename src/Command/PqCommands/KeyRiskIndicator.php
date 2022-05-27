<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

class KeyRiskIndicator {

  public $name;
  public $value;
  public $risky;
  public $message;

  public function __construct(string $name, string $value, bool $risky, string $message) {
    $this->name = $name;
    $this->value = $value;
    $this->risky = $risky;
    $this->message = $message;
  }

  public function toArray() {
    return [
      'name' => $this->name,
      'value' => $this->value,
      'message' => $this->message,
      'risky' => $this->risky,
    ];
  }

}
