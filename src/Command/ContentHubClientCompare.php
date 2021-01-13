<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\Helpers\Client\PlatformCommandExecutioner;
use Acquia\Console\Helpers\Command\PlatformCmdOutputFormatterTrait;
use EclipseGc\CommonConsole\Platform\PlatformCommandTrait;
use EclipseGc\CommonConsole\PlatformCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class ContentHubClientCompare.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubClientCompare extends Command implements PlatformCommandInterface {

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
  protected static $defaultName = 'ach:audit:client-compare';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setDescription('Compares the number of platform sites and Content Hub Subscription clients.')
      ->setHidden(TRUE)
      ->setAliases(['ach-cl-diff']);
  }

  /**
   * {@inheritdoc}
   */
  public static function getExpectedPlatformOptions(): array {
    return [
      'source' => PlatformCommandInterface::ANY_PLATFORM,
    ];
  }

  /**
   * ContentHubClientCompare constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   * @param \Acquia\Console\Helpers\Client\PlatformCommandExecutioner $platform_command_executioner
   *   The platform command executioner.
   * @param string|NULL $name
   *   The name of this command.
   */
  public function __construct(EventDispatcherInterface $dispatcher, PlatformCommandExecutioner $platform_command_executioner, string $name = NULL) {
    parent::__construct($name);
    $this->platformCommandExecutioner = $platform_command_executioner;
    $this->dispatcher = $dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $output->writeln('Execution in progress...');
    /** @var \Acquia\Console\Cloud\Platform\AcquiaCloudPlatform $platform */
    $platform = $this->getPlatform('source');
    $sites_count = count($platform->getPlatformSites());

    $raw = $this->platformCommandExecutioner->runWithMemoryOutput(ContentHubAuditClients::getDefaultName(), $platform, [
        '--count' => TRUE,
      ]);

    $lines = explode(PHP_EOL, trim($raw));
    foreach ($lines as $line) {
      $data = $this->fromJson($line, $output);
      if (!$data) {
        continue;
      }

      if ($sites_count !== $data->count) {
        $output->writeln("<error>You have $sites_count sites in your platform configuration and {$data->count} clients in your subscription.</error>");
        $output->writeln('Please review your configuration!');
      } else {
        $output->writeln('Sites count and clients count are equal');
      }
    }

    return 0;
  }

}
