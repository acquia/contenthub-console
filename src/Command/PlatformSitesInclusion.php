<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\Acsf\Platform\ACSFPlatform;
use Acquia\Console\Cloud\Platform\AcquiaCloudMultiSitePlatform;
use Acquia\Console\Cloud\Platform\AcquiaCloudPlatform;
use EclipseGc\CommonConsole\Platform\PlatformCommandTrait;
use EclipseGc\CommonConsole\PlatformCommandInterface;
use EclipseGc\CommonConsole\PlatformInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class PlatformSitesInclusion
 *
 * @package Acquia\Console\ContentHub\Command
 */
class PlatformSitesInclusion extends Command implements PlatformCommandInterface {

  use PlatformCommandTrait;

  public const INCLUDED_SITES = 'acquia.cloud.environment.include';

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'include:sites';

  /**
   * {@inheritdoc}
   */
  public static function getExpectedPlatformOptions(): array {
    return ['source' => PlatformCommandInterface::ANY_PLATFORM];
  }

  /**
   * PlatformSitesInclusion constructor.
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
    $this->setDescription('Add sites to the inclusion list.');
    $this->setAliases(['is']);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $platform = $this->getPlatform('source');
    $sites = $this->getSites($platform);

    return $this->addSitesToInclusionList($platform, $sites, $input, $output);
  }

  /**
   * Add sites to the inclusion list.
   *
   * @param \EclipseGc\CommonConsole\PlatformInterface $platform
   *   Platform object.
   *
   * @return array
   *   List of sites.
   */
  private function getSites(PlatformInterface $platform): array {
    $sites = [];
    switch ($platform->getPlatformId()) {
      case AcquiaCloudMultiSitePlatform::PLATFORM_NAME:
        $sites = $platform->getMultiSites();
        break;

      case AcquiaCloudPlatform::PLATFORM_NAME:
      case ACSFPlatform::PLATFORM_NAME:
        $sites = $platform->getPlatformSites();
        $sites = array_column($sites, 'uri');
        break;
    }

    return $sites;
  }

  /**
   * Add sites to the inclusion list.
   *
   * @param PlatformInterface $platform
   *   Platform object.
   * @param array $sites
   *   Array containing list of sites.
   * @param InputInterface $input
   *   Input stream.
   * @param OutputInterface $output
   *   Output stream.
   *
   * @return int
   *   Exit code.
   */
  private function addSitesToInclusionList(PlatformInterface $platform, array $sites, InputInterface $input, OutputInterface $output): int {
    $helper = $this->getHelper('question');
    $platform_sites = $platform->get(self::INCLUDED_SITES) ?? [];
    if (!empty($platform_sites)) {
      $sites = array_diff($sites, $platform_sites);
      if (empty($sites)) {
        $output->writeln('<warning>All sites are already there in the inclusion list.</warning>');
        return 0;
      }
    }
    $question = new ChoiceQuestion('Please pick sites to add to the inclusion list:', array_values($sites));
    $question->setMultiselect(TRUE);
    $answers = $helper->ask($input, $output, $question);

    $platform_sites = array_merge($platform_sites, $answers);
    $platform->set(self::INCLUDED_SITES, $platform_sites)->save();

    $output->writeln('<info>Sites successfully added to the inclusion list.</info>');

    return 0;
  }

}
