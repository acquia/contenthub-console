<?php

namespace Acquia\Console\ContentHub\Command;

use EclipseGc\CommonConsole\Platform\PlatformCommandTrait;
use EclipseGc\CommonConsole\PlatformCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a command under subscription.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubSubscriptionSet extends Command implements PlatformCommandInterface {

  use PlatformCommandTrait;

  /**
   * Api key config key name.
   */
  public const CONFIG_API_KEY = 'acquia.content_hub.api_key';

  /**
   * Api key config key name.
   */
  public const CONFIG_HOSTNAME= 'acquia.content_hub.hostname';

  /**
   * Api key config key name.
   */
  public const CONFIG_SECRET_KEY = 'acquia.content_hub.secret_key';

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:subscription';

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
  public static function getExpectedPlatformOptions(): array {
    return ['source' => PlatformCommandInterface::ANY_PLATFORM];
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Sets up the credentials for a Content Hub Subscription.')
      ->setAliases(['ach-sub']);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    do {
      $output->writeln('Please provide credentials for your Content Hub Subscription.');
      $helper = $this->getHelper('question');
      $question = new Question('Content Hub Hostname: ', '');
      $hostname = $helper->ask($input, $output, $question);
      if (filter_var($hostname, FILTER_VALIDATE_URL) === FALSE) {
        $output->writeln('Please provide a valid Hostname.');
        return 1;
      }
      $question = new Question('API Key: ', '');
      $api_key = $helper->ask($input, $output, $question);

      $question = new Question('Secret Key: ', '');
      $secret_key = $helper->ask($input, $output, $question);

      $table = new Table($output);
      $table->setHeaders(['Property', 'Value']);
      $table->addRow(['Content Hub Hostname', $hostname]);
      $table->addRow(['API Key', $api_key]);
      $table->addRow(['Secret Key', $secret_key]);
      $table->render();

      $quest = new ConfirmationQuestion('Are these values correct? ');
      $answer = $helper->ask($input, $output, $quest);
    } while ($answer !== TRUE);

    $platform = $this->getPlatform('source');
    $platform
      ->set(self::CONFIG_HOSTNAME, $hostname)
      ->set(self::CONFIG_API_KEY, $api_key)
      ->set(self::CONFIG_SECRET_KEY, $secret_key)
      ->save();

    $output->writeln(sprintf('<info>The following values have been saved in the current platform "%s":</info>', $platform->getAlias()));
    $table->render();

    return 0;
  }
}

