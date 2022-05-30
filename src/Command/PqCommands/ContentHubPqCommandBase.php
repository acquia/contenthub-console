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

  /**
   * {@inheritdoc}
   */
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
    $this->toTable($this->convertKrisToArray($result->getResult()), $output);
    return $exitCode;
  }

  /**
   * Runs the internal logic of the implementing command.
   *
   * The method receives a PqCommandResult object, which encapsulated the
   * resulting key risk indicators. The outputting and formatting is handled in
   * the parent class.
   *
   * Any command related - which is not check violation - exception should be
   * thrown here using the ContentHubPqCommandErrors class.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input object containing the provided input for the command.
   * @param \Acquia\Console\ContentHub\Command\PqCommands\PqCommandResult $result
   *   The result object to set key risk indicators.
   *
   * @return int
   *   The command exit code.
   */
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

  /**
   * Outputs error in a table format to keep the output flow consistent.
   *
   * @param array $rows
   *   The data to include into the table.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output object to write to.
   */
  protected function toErrorTable(array $rows, OutputInterface $output): void {
    $headers = ['Command Execution Error', 'Error Code'];
    $table = $this->createTable($output, $headers, $rows);
    $table->setFooterTitle(
      sprintf('%s - %s', static::getDefaultName(), $this->getDescription())
    );
    $table->render();
  }

  /**
   * Converts KeyRiskIndicators to array and returns them.
   *
   * @param \Acquia\Console\ContentHub\Command\PqCommands\KeyRiskIndicator[] $kris
   *   The key risk indicators.
   *
   * @return array
   *   The converted KeyRiskIndicators.
   */
  protected function convertKrisToArray(array $kris): array {
    $res = [];
    foreach ($kris as $kri) {
      $res[] = $kri->toArray();
    }
    return $res;
  }

}
