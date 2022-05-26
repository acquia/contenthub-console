<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

use Acquia\Console\ContentHub\Command\Helpers\ColorizedOutputTrait;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function Spatie\SslCertificate\length;

class ContentHubPqBundle extends Command {

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
    $this
      ->setDescription('Runs all the pre-qualification commands, or the ones specified by options.')
      ->addOption('exclude', 'e', InputOption::VALUE_OPTIONAL|InputOption::VALUE_IS_ARRAY, 'Exclude the provided checks')
      ->addOption('checks', 'c', InputOption::VALUE_OPTIONAL|InputOption::VALUE_IS_ARRAY, 'Run the provided checks');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $exclude = $input->getOption('exclude');
    $checks = $input->getOption('checks');
    if ($exclude && $checks) {
      $output->writeln($this->error('The options "exclude" and "checks" cannot be used together'));
      return 1;
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

    $pqCommands = $this->getPqCommands($commandList, $filterMode);
    $resultExitCode = 0;
    foreach ($pqCommands as $command) {
      $exit = $command->run($input, $output);
      if ($exit > 0) {
        $resultExitCode = $exit;
      }
    }

    return $resultExitCode;
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

  protected function cutNamespace(string $cmdName) {
    return substr($cmdName, length('ach:pq:'));
  }

}
