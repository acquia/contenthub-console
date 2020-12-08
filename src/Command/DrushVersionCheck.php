<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Client\PlatformCommandExecutioner;
use Acquia\Console\ContentHub\Command\Helpers\DrushWrapper;
use Acquia\Console\ContentHub\Command\Helpers\PlatformCmdOutputFormatterTrait;
use EclipseGc\CommonConsole\Platform\PlatformCommandTrait;
use EclipseGc\CommonConsole\PlatformCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Command for checking drush version.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class DrushVersionCheck extends Command implements PlatformCommandInterface {

  use PlatformCommandTrait;
  use PlatformCmdOutputFormatterTrait;

  /**
   * The platform command executioner.
   *
   * @var \Acquia\Console\ContentHub\Client\PlatformCommandExecutioner
   */
  protected $platformCommandExecutioner;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'drush:version';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Checks drush version on the server.');
  }

  /**
   * {@inheritdoc}
   */
  public static function getExpectedPlatformOptions(): array {
    return ['source' => PlatformCommandInterface::ANY_PLATFORM];
  }

  /**
   * DrushVersionCheck constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The dispatcher service.
   * @param \Acquia\Console\ContentHub\Client\PlatformCommandExecutioner $platform_command_executioner
   *   The platform command executioner.
   * @param string|null $name
   *   The name of the command.
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
    $output->writeln('Checking drush version...');
    $drush_options = ['--drush_command' => 'version', '--drush_args' => ['--format=string']];
    $raw = $this->platformCommandExecutioner->runWithMemoryOutput(DrushWrapper::$defaultName, $this->getPlatform('source'), $drush_options);
    $exit_code = $raw->getReturnCode();
    $result = $this->getDrushOutput($raw, $output, $exit_code, reset($drush_options), FALSE);

    $version = $result->drush_output ?? NULL;

    if (!$version) {
      $output->writeln('<comment>Attempted to run "drush". It might be missing or the executable name does not match the expected.</comment>');
      return 2;
    }

    $output->writeln(sprintf('Current drush version is: <info>%s</info>', $version));
    if (version_compare($version, '9.0.0', '<')) {
      $output->writeln('<error>Drush version must be 9.0.0 or higher!</error>');
      return 1;
    }

    return 0;
  }

}
