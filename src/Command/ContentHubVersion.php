<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Command\Helpers\PlatformCmdOutputFormatterTrait;
use Acquia\Console\ContentHub\Command\Helpers\PlatformCommandExecutionTrait;
use EclipseGc\CommonConsole\Platform\PlatformCommandTrait;
use EclipseGc\CommonConsole\PlatformCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class ContentHubVersion.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubVersion extends Command implements PlatformCommandInterface {

  use PlatformCommandTrait;
  use PlatformCommandExecutionTrait;
  use PlatformCmdOutputFormatterTrait;

  /**
   * {@inheritdoc}
   */
  public static $defaultName = 'ach:module:version';

  /**
   * {@inheritdoc}
   */
  public function configure() {
    $this->setDescription('Checks if platform sites have ACH module 2.x version.');
    $this->setAliases(['ach-mv']);
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
   * @param string|null $name
   *   The name of the command.
   */
  public function __construct(EventDispatcherInterface $dispatcher, string $name = NULL) {
    parent::__construct($name);

    $this->dispatcher = $dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $output->writeln('Looking for 2.x version of Content Hub module');

    $ready = FALSE;
    $helper = $this->getHelper('question');

    while (!$ready) {
      $sites_not_ready = $this->getNotUpToDateSites($output);

      if (!empty($sites_not_ready)) {
        $output->writeln('<error>The following sites do not have 2.x version of ContentHub</error>');
        $table = new Table($output);
        $table->setHeaders(['Url']);
        $table->addRows($sites_not_ready);
        $table->render();

        $question = new Question('Please deploy and hit enter once the code is up-to-date!');
        $helper->ask($input, $output, $question);
        continue;
      }

      $ready = TRUE;
    }

    $output->writeln('All sites are up-to-date. You may proceed.');
  }

  /**
   * Gathers sites which do not have ACH 2.x.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output.
   *
   * @return array
   *   Array contains sites, which do not have ACH 2.x.
   */
  protected function getNotUpToDateSites(OutputInterface $output): array {
    $sites_not_ready = [];

    $raw = $this->runWithMemoryOutput(ContentHubModuleVersion::getDefaultName());

    $lines = explode(PHP_EOL, trim($raw));
    foreach ($lines as $line) {
      $data = $this->fromJson($line, $output);
      if (!$data) {
        continue;
      }

      if ($data->module_version !== 2) {
        $sites_not_ready[] = [
          $data->base_url,
        ];
      }
    }

    return $sites_not_ready;
  }

}
