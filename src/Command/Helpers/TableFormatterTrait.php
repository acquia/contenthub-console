<?php

namespace Acquia\Console\ContentHub\Command\Helpers;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Contains table formatting helper methods.
 */
trait TableFormatterTrait {

  /**
   * Returns a new table object.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output object to write to.
   * @param array $headers
   *   Table headers.
   * @param array $rows
   *   The table rows, an array of arrays.
   *
   * @return \Symfony\Component\Console\Helper\Table
   *   The constructed Table object.
   */
  public function createTable(OutputInterface $output, array $headers, array $rows): Table {
    $table = new Table($output);
    $table->setHeaders($headers);
    $table->setRows($rows);
    return $table;
  }

}
