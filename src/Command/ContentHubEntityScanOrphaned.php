<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Command\Helpers\DrushWrapper;
use Acquia\Console\Helpers\Command\PlatformCmdOutputFormatterTrait;
use Acquia\Console\Helpers\PlatformCommandExecutioner;
use EclipseGc\CommonConsole\Platform\PlatformCommandTrait;
use EclipseGc\CommonConsole\PlatformCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides command to scan for orphaned entities in Content Hub.
 */
class ContentHubEntityScanOrphaned extends Command implements PlatformCommandInterface {

  use PlatformCommandTrait;
  use PlatformCmdOutputFormatterTrait;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:entity-scan:orphaned';

  /**
   * The platform command executioner.
   *
   * @var \Acquia\Console\Helpers\PlatformCommandExecutioner
   */
  protected $platformCommandExecutioner;

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
    $this->setDescription('Scan for orphaned entities in the subscription.');
    $this->addOption('delete', 'd', InputOption::VALUE_NONE, 'Delete found entities');
    $this->addOption('json', '', InputOption::VALUE_NONE, 'Return output as json');
    $this->addOption('with-db', '', InputOption::VALUE_NONE, 'Check if the orphaned entities are present in DB. If yes exclude them from the result.');
    $this->addOption('with-interest-list', '', InputOption::VALUE_NONE, 'Check if the orphaned entities are present on the interest list. If yes exclude them from the result.');
    $this->addOption('only-uuids', '', InputOption::VALUE_NONE, 'Returns only uuids in a non-formatted way, 1 by line. (Omits json flag)');
    $this->setAliases(['ach-eso']);
  }

  /**
   * Constructs a new object.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The dispatcher service.
   * @param \Acquia\Console\Helpers\PlatformCommandExecutioner $platformCommandExecutioner
   *   The platform command executioner.
   * @param string|null $name
   *   The name of the command.
   */
  public function __construct(EventDispatcherInterface $dispatcher, PlatformCommandExecutioner $platformCommandExecutioner, $name = NULL) {
    parent::__construct($name);
    $this->platformCommandExecutioner = $platformCommandExecutioner;
    $this->dispatcher = $dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $drushOptions = [
      '--drush_command' => 'acquia:contenthub:entity-scan:orphaned',
      '--drush_args' => [
        '--json',
        '--with-db',
        '--with-interest-list',
      ],
      '--json' => '',
    ];

    $raw = $this->platformCommandExecutioner->runWithMemoryOutput(DrushWrapper::$defaultName, $this->getPlatform('source'), $drushOptions);
    $data = $this->extractAndAggregateJson($raw);
    $orphanedEntities = [];
    foreach ($data as $datum) {
      $entities = $datum['drush_output']['orphaned_entities'];
      foreach ($entities as $entity) {
        $uuid = $entity[1];
        if (isset($orphanedEntities[$uuid]['entity'])) {
          $orphanedEntities[$uuid]['occurrence']++;
        }
        else {
          $orphanedEntities[$uuid] = [
            // Uuid, origin, CH type, Drupal entity type.
            'entity' => [$uuid, $entity[2], $entity[3], $entity[4]],
            'occurrence' => 1,
          ];
        }
      }
    }
    $numberOfSites = count($data);
    // Ensure the entity is listed. If the occurrence does not equal the number
    // of sites it means the entity was filtered from one or more of the sites.
    $orphanedEntities = array_filter($orphanedEntities, function ($item) use ($numberOfSites) {
      return $numberOfSites === $item['occurrence'];
    });

    $entities = array_column($orphanedEntities, 'entity');
    $this->displayOrphanedEntities($entities, $output, $input);
    if ($input->getOption('delete')) {
      $this->deleteEntities($entities);
    }

    return 0;
  }

  /**
   * Displays the provided entities in the desired format.
   *
   * @param array $entities
   *   The list of entities to display.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output object.
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   Retrieves different options from input e.g. output formats.
   */
  protected function displayOrphanedEntities(array $entities, OutputInterface $output, InputInterface $input): void {
    if ($input->getOption('only-uuids')) {
      $output->writeln(array_column($entities, 0));
      return;
    }

    $json = $input->getOption('json');
    $headers = [
      'Uuid', 'Origin', 'Content Hub Entity Type', 'Drupal Entity Type',
    ];
    if ($json) {
      $json = [];
      foreach ($entities as $entity) {
        $json[] = array_combine($headers, $entity);
      }
      $output->write(json_encode($json));
      return;
    }

    $table = new Table($output);
    $table->setHeaders($headers);
    foreach ($entities as $entity) {
      $table->addRow($entity);
    }
    $table->render();
  }

  /**
   * Deletes the entities provided.
   *
   * @param array $entities
   *   The list of entities to delete.
   */
  public function deleteEntities(array $entities): void {
    // @todo implement deletion - req: local content hub service.
  }

}
