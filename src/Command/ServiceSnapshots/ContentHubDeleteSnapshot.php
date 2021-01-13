<?php

namespace Acquia\Console\ContentHub\Command\ServiceSnapshots;

use Acquia\Console\Helpers\Client\PlatformCommandExecutioner;
use Acquia\Console\ContentHub\Command\Helpers\ContentHubDeleteSnapshotHelper;
use Acquia\Console\Helpers\Command\PlatformCmdOutputFormatterTrait;
use EclipseGc\CommonConsole\Platform\PlatformCommandTrait;
use EclipseGc\CommonConsole\PlatformCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class ContentHubDeleteSnapshot.
 *
 * @package Acquia\Console\ContentHub\Command\ServiceSnapshots
 */
class ContentHubDeleteSnapshot extends Command implements PlatformCommandInterface {

  use PlatformCommandTrait;
  use PlatformCmdOutputFormatterTrait;

  /**
   * The platform command executioner.
   *
   * @var \Acquia\Console\Helpers\Client\PlatformCommandExecutioner
   */
  protected $platformCommandExecutioner;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:delete-snapshot';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Delete Acquia Content Hub snapshots.')
      ->setHidden(TRUE)
      ->setAliases(['ach-ds']);
  }

  /**
   * {@inheritdoc}
   */
  public static function getExpectedPlatformOptions(): array {
    return ['source' => PlatformCommandInterface::ANY_PLATFORM];
  }

  /**
   * ContentHubSubscriptionSet constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The dispatcher service.
   * @param \Acquia\Console\Helpers\Client\PlatformCommandExecutioner $platform_command_executioner
   *   The platform command executioner.
   * @param string|null $name
   *   The name of the command.
   */
  public function __construct(EventDispatcherInterface $dispatcher, PlatformCommandExecutioner $platform_command_executioner, string $name = NULL) {
    parent::__construct($name);
    $this->dispatcher = $dispatcher;
    $this->platformCommandExecutioner = $platform_command_executioner;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $snapshots = $this->listSnapshots($output);

    $helper = $this->getHelper('question');
    $question = new ChoiceQuestion('Select snapshot you want to delete.', $snapshots);
    $question->setMultiselect(TRUE);
    $select_snapshot = $helper->ask($input, $output, $question);

    foreach ($select_snapshot as $snapshot) {
      $delete_snapshot = $this->platformCommandExecutioner->runWithMemoryOutput(ContentHubDeleteSnapshotHelper::getDefaultName(),
        $this->getPlatform('source'),
        ['--name' => $snapshot]);
      if ($delete_snapshot->getReturnCode()) {
        $output->writeln(sprintf('<error>Could not delete snapshot: %s</error>', $snapshot));
        return 1;
      }
      $output->writeln(sprintf('<info>Snapshot deleted successfully: %s</info>', $snapshot));
    }

    return 0;
  }

  /**
   * List snapshots.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output.
   *
   * @return array
   *   Snapshots list.
   */
  protected function listSnapshots($output) {
    $raw = $this->platformCommandExecutioner->runWithMemoryOutput(ContentHubGetSnapshots::getDefaultName(),
      $this->getPlatform('source'), [
      '--list' => TRUE
    ]);
    if ($raw->getReturnCode()) {
      return 1;
    }
    $lines = explode(PHP_EOL, trim($raw));
    $snapshot = [];
    foreach ($lines as $line) {
      $data = $this->fromJson($line, $output);
      if (isset($data->snapshots)) {
        $snapshot = $data->snapshots;
      }
      continue;
    }
    return $snapshot;
  }

}
