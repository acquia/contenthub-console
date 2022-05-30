<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

use Acquia\Console\ContentHub\Command\Helpers\ColorizedOutputTrait;
use Acquia\Console\ContentHub\Command\Helpers\TableFormatterTrait;
use Acquia\Console\Helpers\Command\PlatformCmdOutputFormatterTrait;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base class for ach:pq commands.
 */
abstract class ContentHubPqCommandBase extends Command implements PlatformBootStrapCommandInterface {

  use ColorizedOutputTrait;
  use PlatformCmdOutputFormatterTrait;
  use TableFormatterTrait;

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

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $result = new PqCommandResult();
    $format = $input->getOption('format');
    try {
      $exitCode = $this->runCommand($input, $result);
    }
    catch (\Exception $e) {
      if ($format === 'json') {
        $output->write($this->toJsonError($e->getMessage(), ['error_code' => $e->getCode()]));
      }
      else {
        $this->toErrorTable([[$e->getMessage(), $e->getCode()]], $output);
      }
      return $e->getCode();
    }

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
    $table = $this->createTable($output, $headers, $data);
    $table->setFooterTitle(
      sprintf('%s - %s', static::getDefaultName(), $this->getDescription())
    );
    $table->render();
  }

  protected function toErrorTable(array $data, OutputInterface $output): void {
    $headers = ['Command Execution Error', 'Error Code'];
    $table = $this->createTable($output, $headers, $data);
    $table->setFooterTitle(
      sprintf('%s - %s', static::getDefaultName(), $this->getDescription())
    );
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
