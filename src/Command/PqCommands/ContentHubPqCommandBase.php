<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

use Acquia\Console\ContentHub\Command\Helpers\ColorizedOutputTrait;
use Acquia\Console\Helpers\Command\PlatformCmdOutputFormatterTrait;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base class for ach:pq commands.
 */
abstract class ContentHubPqCommandBase extends Command implements PlatformBootStrapCommandInterface {

  use PlatformCmdOutputFormatterTrait;
  use ColorizedOutputTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format <json|table>', 'table');
  }

  /**
   * {@inheritdoc}
   */
  public function getPlatformBootstrapType(): string {
    return 'drupal8';
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $result = new PqCommandResult();
    try {
      $exitCode = $this->runCommand($input, $result);
    }
    catch (PqCommandException $e) {
      $output->writeln($this->error($e->getMessage()));
      return $e->getCode();
    }

    $format = $input->getOption('format');
    if ($format === 'json') {
      $output->write(json_encode($result->getResult()));
      return $exitCode;
    }
    $this->toTable($this->getData($result->getResult()), $output);
    return $exitCode;
  }

  abstract protected function runCommand(InputInterface $input, PqCommandResult $result): int;

  /**
   * Outputs data in a table format.
   *
   * @param array $data
   *   The rows of the table.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output stream provided for the table.
   */
  protected function toTable(array $data, OutputInterface $output): void {
    $headers = [
      'Key Risk Indicator',
      'Result',
      'Note',
      'Risky',
    ];
    $data = array_map(function ($val) {
      $val['risky'] = $val['risky'] ? $this->error('YES') : $this->info('NO');
      return $val;
    }, $data);
    $table = new Table($output);
    $table->setFooterTitle(static::getDefaultName());
    $table->setHeaders($headers);
    $table->setRows($data);
    $table->render();
  }

  /**
   * @param \Acquia\Console\ContentHub\Command\PqCommands\KeyRiskIndicator[] $kris
   */
  protected function getData(array $kris) {
    $res = [];
    foreach ($kris as $kri) {
      $res[] = $kri->toArray();
    }
    return $res;
  }

}
