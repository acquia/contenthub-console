<?php

namespace Acquia\Console\ContentHub\Command\Helpers;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

trait TableFormatterTrait {

  public function createTable(OutputInterface $output, array $headers, array $rows): Table {
    $table = new Table($output);
    $table->setHeaders($headers);
    $table->setRows($rows);
    return $table;
  }
  
}
