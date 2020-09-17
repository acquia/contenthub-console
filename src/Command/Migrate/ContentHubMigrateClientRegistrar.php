<?php

namespace Acquia\Console\ContentHub\Command\Migrate;

use Acquia\Console\ContentHub\Command\Helpers\ClientInformationRetriever;
use Acquia\Console\ContentHub\Command\Helpers\ContentHubRegistrar;
use Acquia\Console\ContentHub\Command\Helpers\PlatformCommandExecutionTrait;
use Acquia\Console\ContentHub\Command\Helpers\PlatformCmdOutputFormatterTrait;
use Acquia\Console\ContentHub\Command\ContentHubSubscriptionSet;
use EclipseGc\CommonConsole\CommonConsoleEvents;
use EclipseGc\CommonConsole\Event\PlatformArgumentInjectionEvent;
use EclipseGc\CommonConsole\Platform\PlatformCommandTrait;
use EclipseGc\CommonConsole\PlatformCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ContentHubMigrateClientRegistrar.
 *
 * Registers a client in the new subscription and maps to its source
 * counterpart. It is only necessary if you are working with non-production
 * environments and you are migrating the sites to a clean subscription.
 *
 * @package Acquia\Console\ContentHub\Command\Migrate
 */
class ContentHubMigrateClientRegistrar extends Command implements PlatformCommandInterface, EventSubscriberInterface {

  use PlatformCommandExecutionTrait;
  use PlatformCmdOutputFormatterTrait;
  use PlatformCommandTrait;

  /**
   * Configuration key.
   */
  public const CONFIG_ACH_MIGRATION = 'acquia.content_hub.migration';

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:migrate:client-register';

  /**
   * The list of source clients.
   *
   * @var array
   */
  private $sourceClients;

  /**
   * ContentHubMigrateClientRegistrar constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param string|null $name
   *   The name of the command.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, string $name = NULL) {
    parent::__construct($name);

    $this->dispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function getExpectedPlatformOptions(): array {
    return [
      // The source platform, used to map the new clients to their production
      // counterpart.
      'source' => PlatformCommandInterface::ANY_PLATFORM,
      // The target platform, where the new clients are going to be created.
      'target' => PlatformCommandInterface::ANY_PLATFORM,
    ];
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    return [
      CommonConsoleEvents::PLATFORM_ARGS_INJ => 'onPlatformArgumentInjection',
    ];
  }

  /**
   * Injects values into the input list based on the current site.
   *
   * The client name must be the same as on source platform, therefore we need
   * to set them accordingly.
   *
   * @param \EclipseGc\CommonConsole\Event\PlatformArgumentInjectionEvent $event
   *   The platform argument injection event.
   *
   * @throws \Exception
   */
  public function onPlatformArgumentInjection(PlatformArgumentInjectionEvent $event) {
    if ($event->getCommandName() !== ContentHubRegistrar::getDefaultName()) {
      return;
    }
    $sites = $event->getSites();
    $arguments = [];
    foreach ($sites as $site) {
      $arguments[$site] = [
        '--name' => current($this->sourceClients)['client_name'],
      ];
      next($this->sourceClients);
    }

    $event->setDecoratedInput($arguments);
    $event->stopPropagation();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Create clients in the new subscription.')
      ->setHelp('This command will create new clients and map to the production counterparts.')
      ->setAliases(['ach-mcr'])
      ->addArgument('target', InputArgument::OPTIONAL, 'Provide the alias of the migration source source platform.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $application = $this->getApplication();
    $output->writeln('<info>Creating clients for the new subscription</info>');
    $output->writeln('<info>This migration type requires the source subscription credentials.</info>');

    // The subject platform.
    $platform = $this->getPlatform('target');
    // Store the credentials used as client registration source.
    $subscription_setter = $application->find(ContentHubSubscriptionSet::getDefaultName());
    $subscription_setter->addPlatform($input->getArgument('alias'), $platform);
    $subscription_setter->run(new ArrayInput(['alias' => $input->getArgument('alias'), '--migration' => TRUE]), $output);

    $this->sourceClients = $this->getProdClients($output);
    $raw = (string) $this->runWithMemoryOutput(ContentHubRegistrar::getDefaultName(), [
      '--hostname' => $platform->get(ContentHubSubscriptionSet::CONFIG_HOSTNAME),
      '--api-key' => $platform->get(ContentHubSubscriptionSet::CONFIG_API_KEY),
      '--secret-key' => $platform->get(ContentHubSubscriptionSet::CONFIG_SECRET_KEY),
    ], 'target');

    try {
      $this->storeClients($raw, $output);
    }
    catch (\Exception $e) {
      $output->writeln(sprintf('Error during configuration save: %s', $e->getMessage()));
      return 1;
    }

    $output->writeln('<info>Client registration finished!</info>');
    return 0;
  }

  /**
   * Parses raw output and stores newly registered clients.
   *
   * Storage scheme:
   *  acquia:
   *    content_hub:
   *      migration:
   *        client_list:
   *          {site_origin_uuid_1}:
   *            original_client_uuid: {client_uuid_1}
   *            new_client_name: {client_name_1}
   *            publisher: {bool}
   *          ...
   *
   * @param string $raw
   *   The raw output of ContentHubRegisterer command.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output to write to.
   *
   * @throws \Exception
   */
  protected function storeClients(string $raw, OutputInterface $output): void {
    $platform = $this->getPlatform('target');
    $lines = explode(PHP_EOL, trim($raw));
    foreach ($lines as $line) {
      $client_data = $this->fromJson($line, $output);
      if (!$client_data) {
        continue;
      }
      $name = $client_data->client_name;
      $platform->set(
        self::CONFIG_ACH_MIGRATION . '.client_list.' . $client_data->origin,
        [
          'original_client_uuid' => $this->sourceClients[$name]['origin'],
          'client_name' => $client_data->client_name,
          'publisher' => $this->sourceClients[$name]['publisher'],
        ]
      );

      $output->writeln("Client '<info>{$client_data->client_name}</info>' queued for storage.");
    }

    $platform->save();
  }

  /**
   * Runs ach:retrieve-client command on source platform and returns the result.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output stream to write to.
   *
   * @return array
   *   The list of publisher clients.
   */
  protected function getProdClients(OutputInterface $output): array {
    // The source platform aka prod env.
    $raw = $this->runWithMemoryOutput(ClientInformationRetriever::getDefaultName());
    $lines = explode(PHP_EOL, trim($raw));
    $subs = [];
    foreach ($lines as $line) {
      $client_data = $this->fromJson($line, $output);
      if (!$client_data) {
        continue;
      }

      $subs[$client_data->client_name] = [
        'origin' => $client_data->origin,
        'client_name' => $client_data->client_name,
        'publisher' => $client_data->publisher,
      ];
    }

    return $subs;
  }

}
