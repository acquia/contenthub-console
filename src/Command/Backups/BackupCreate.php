<?php

namespace Acquia\Console\ContentHub\Command\Backups;

use Acquia\Console\Acsf\Platform\ACSFPlatform;
use Acquia\Console\Cloud\Platform\AcquiaCloudMultiSitePlatform;
use Acquia\Console\Cloud\Platform\AcquiaCloudPlatform;
use EclipseGc\CommonConsole\Platform\PlatformCommandTrait;
use EclipseGc\CommonConsole\PlatformCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class BackupCreate.
 *
 * Creates a backup bundle of Acquia Content Hub Service snapshot and
 * database site backups.
 *
 * @package Acquia\Console\ContentHub\Command\Backups
 */
class BackupCreate extends Command implements PlatformCommandInterface {

  use PlatformCommandTrait;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'backup:create';

  /**
   * {@inheritdoc}
   */
  public static function getExpectedPlatformOptions(): array {
    return ['source' => PlatformCommandInterface::ANY_PLATFORM];
  }

  /**
   * BackupCreate constructor.
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
    $this->setDescription('Creates a backup bundle of Acquia Content Hub Service snapshot and database site backups.');
    $this->setAliases(['bc']);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $platform = $this->getPlatform('source');
    $backup_command = NULL;
    switch ($platform->getPlatformId()) {
      case AcquiaCloudPlatform::getPlatformId():
        $backup_command = $this->getApplication()->find(AcquiaCloudBackupCreate::getDefaultName());
        break;

      case AcquiaCloudMultiSitePlatform::getPlatformId():
        $backup_command = $this->getApplication()->find(AcquiaCloudBackupCreateMultiSite::getDefaultName());
        break;

      case ACSFPlatform::getPlatformId():
        $backup_command = $this->getApplication()->find(AcsfBackupCreate::getDefaultName());
        break;
    }

    return $this->runBackupCommand($backup_command, $input, $output);
  }

}
