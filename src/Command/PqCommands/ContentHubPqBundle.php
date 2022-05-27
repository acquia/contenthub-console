<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

use Acquia\Console\ContentHub\Command\Helpers\ColorizedOutputTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use function Spatie\SslCertificate\length;

/**
 * Runs all ach:pq commands.
 */
class ContentHubPqBundle extends ContentHubPqCommandBase {

  use ColorizedOutputTrait;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:pq:all';

  /**
   * {@inheritdoc}
   */
  public function getPlatformBootstrapType(): string {
    return 'drupal8';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this
      ->setDescription('Runs all the pre-qualification commands, or the ones specified by options.')
      ->addOption('exclude', 'e', InputOption::VALUE_OPTIONAL|InputOption::VALUE_IS_ARRAY, 'Exclude the provided checks')
      ->addOption('checks', 'c', InputOption::VALUE_OPTIONAL|InputOption::VALUE_IS_ARRAY, 'Run the provided checks');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $format = $input->getOption('format');

    try {
      [$commandList, $filterMode] = $this->getCommandListAndFilterMode($input);
    }
    catch (InvalidOptionException $e) {
      $output->writeln($this->error($e->getMessage()));
      return 1;
    }

    $pqCommands = $this->getPqCommands($commandList, $filterMode);
    $resultExitCode = 0;
    $result = [];
    foreach ($pqCommands as $command) {
      if ($format === 'json') {
        $tempOutput = new StreamOutput(fopen('php://memory', 'r+', FALSE));
        $exit = $command->run($input, $tempOutput);
        $result[$command::getDefaultName()] = $this->getJsonStreamContentsInArray($tempOutput);
      }
      else {
        $exit = $command->run($input, $output);
      }

      if ($exit > 0) {
        $resultExitCode = $exit;
      }
    }

    if ($format === 'json') {
      $output->writeln(json_encode($result));
    }

    return $resultExitCode;
  }

  /**
   * Extracts list of commands to run from the input.
   *
   * @param $input
   *   The input containing the options.
   *
   * @return array
   *   A tuple with the list of commands and the filter mode.
   */
  protected function getCommandListAndFilterMode(InputInterface $input): array {
    $exclude = $input->getOption('exclude');
    $checks = $input->getOption('checks');

    if ($exclude && $checks) {
      throw new InvalidOptionException('The options "exclude" and "checks" cannot be used together');
    }

    $filterMode = '';
    $commandList = [];
    if ($checks) {
      $filterMode = 'checks';
      $commandList = $checks;
    }
    if ($exclude) {
      $filterMode = 'exclude';
      $commandList = $exclude;
    }

    return [$commandList, $filterMode];
  }

  /**
   * @param array $commandList
   *   The command name list to filter by.
   * @param string $filterMode
   *   The filter option.
   *
   * @return Command[]
   */
  public function getPqCommands(array $commandList, string $filterMode): array {
    $commands = $this->getApplication()->all('ach:pq');
    $filteredCommands = [];
    foreach ($commands as $command) {
      if ($command->getName() === static::$defaultName) {
        continue;
      }

      $cmdName = $this->cutNamespace($command->getName());
      if (in_array($cmdName, $commandList, TRUE) && $filterMode === 'exclude') {
        continue;
      }

      if (!in_array($cmdName, $commandList, TRUE) && $filterMode === 'checks') {
        continue;
      }

      $filteredCommands[] = $command;
    }

    return $filteredCommands;
  }

  /**
   * Removes pq namespace from string.
   *
   * @param string $cmdName
   *   The command name containing the namespace.
   *
   * @return string
   *   The resulting name.
   */
  protected function cutNamespace(string $cmdName): string {
    return substr($cmdName, length('ach:pq:'));
  }

  /**
   * Extracts output from stream and decodes it from json.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output stream to extract.
   *
   * @return array
   *   The resulting array.
   */
  protected function getJsonStreamContentsInArray(OutputInterface $output): array {
    rewind($output->getStream());
    return json_decode(stream_get_contents($output->getStream()), TRUE) ?? [];
  }

}
