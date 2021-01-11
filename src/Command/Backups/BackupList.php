<?php

namespace Acquia\Console\ContentHub\Command\Backups;

use EclipseGc\CommonConsole\Config\ConfigStorage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BackupList.
 *
 * @package Acquia\Console\Cloud\Command\Backups
 */
class BackupList extends Command {

  /**
   * Parts of the directory path pointing to configuration files.
   *
   * @var array
   */
  protected $config_dir = [
    '.acquia',
    'contenthub',
    'backups'
  ];

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'backup:list';

  /**
   * The config storage.
   *
   * @var \EclipseGc\CommonConsole\Config\ConfigStorage
   */
  protected $storage;

  /**
   * BackupList constructor.
   *
   * @param \EclipseGc\CommonConsole\Config\ConfigStorage $configStorage
   *   Config storage.
   * @param string|NULL $name
   *   Command name.
   */
  public function __construct(ConfigStorage $configStorage, string $name = NULL) {
    parent::__construct();

    $this->storage = $configStorage;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('List available backup bundles of Content Hub Service snapshots and database site backups.');
    $this->setAliases(['bl']);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    /** @var \Consolidation\Config\Config[] $backup_configs */
    $backup_configs = $this->storage->loadAll($this->config_dir);
    if (!$backup_configs) {
      $output->writeln('<warning>No configuration found.</warning>');
      return 0;
    }

    $table = new Table($output);
    $table->setHeaders(['Backup name', 'Platform alias', 'Platform type', 'Module version', 'Site count', 'Created']);

    $rows = [];
    foreach ($backup_configs as $config) {
      $created = new \DateTime();
      $created->setTimestamp($config->get('platform.backupCreated'));

      $rows[] = [
        $config->get('name'),
        $config->get('platform.name'),
        $config->get('platform.type'),
        $config->get('platform.module_version') == 1 ? '8.x-1.x' : '8.x-2.x' ,
        count($config->get('backups.database')),
        $created->format('Y-m-d H:i:s'),
      ];
    }

    $table->addRows($rows);
    $table->render();

    return 0;
  }

}
