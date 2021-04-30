<?php

namespace Acquia\Console\ContentHub\Command;

use EclipseGc\CommonConsole\Platform\PlatformCommandTrait;
use EclipseGc\CommonConsole\PlatformCommandInterface;
use EclipseGc\CommonConsole\PlatformInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class PlatformSitesDeletion
 *
 * @package Acquia\Console\ContentHub\Command
 */
class PlatformSitesDeletion extends Command implements PlatformCommandInterface {

  use PlatformCommandTrait;

  public const INCLUDED_SITES = 'acquia.cloud.environment.include';

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'delete:included:sites';

  /**
   * {@inheritdoc}
   */
  public static function getExpectedPlatformOptions(): array {
    return ['source' => PlatformCommandInterface::ANY_PLATFORM];
  }

  /**
   * PlatformSitesDeletion constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event Dispatcher service.
   * @param string|null $name
   *   Command name.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, string $name = NULL) {
    parent::__construct($name);
    $this->dispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Remove sites from the inclusion list.');
    $this->setAliases(['dis']);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $platform = $this->getPlatform('source');
    return $this->deleteSitesFromInclusionList($platform, $input, $output);
  }

  /**
   * Delete sites from inclusion list.
   *
   * @param PlatformInterface $platform
   *   Platform object.
   * @param InputInterface $input
   *   Input stream.
   * @param OutputInterface $output
   *   Output stream.
   *
   * @return int
   *   Exit code.
   */
  private function deleteSitesFromInclusionList(PlatformInterface $platform, InputInterface $input, OutputInterface $output): int {
    $helper = $this->getHelper('question');
    $platform_sites = $platform->get(self::INCLUDED_SITES) ?? [];
    if (empty($platform_sites)) {
      $output->writeln('<warning>No sites available in the inclusion list.</warning>');
      return 0;
    }
    $question = new ChoiceQuestion('Please pick sites to delete from the inclusion list:', $platform_sites);
    $question->setMultiselect(TRUE);
    $answers = $helper->ask($input, $output, $question);

    $platform_sites = array_diff($platform_sites, $answers);
    $platform->set(self::INCLUDED_SITES, $platform_sites)->save();
    return 0;
  }

}
