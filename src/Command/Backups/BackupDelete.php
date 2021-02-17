<?php

namespace Acquia\Console\ContentHub\Command\Backups;

use Acquia\Console\Acsf\Platform\ACSFPlatform;
use Acquia\Console\Cloud\Platform\AcquiaCloudMultiSitePlatform;
use Acquia\Console\Cloud\Platform\AcquiaCloudPlatform;
use EclipseGc\CommonConsole\Platform\PlatformCommandTrait;
use EclipseGc\CommonConsole\PlatformCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class BackupDelete.
 *
 * @package Acquia\Console\ContentHub\Command\Backups
 */
class BackupDelete  extends Command implements PlatformCommandInterface {

  use PlatformCommandTrait;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'backup:delete';

  /**
   * {@inheritdoc}
   */
  public static function getExpectedPlatformOptions(): array {
    return ['source' => PlatformCommandInterface::ANY_PLATFORM];
  }

  /**
   * BackupDelete constructor.
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
    $this->setDescription('Deletes backups bundles of Content Hub Service snapshots and database site backups.');
    $this->setAliases(['bd']);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $platform = $this->getPlatform('source');
    $backup_command = NULL;
    switch ($platform->getPlatformId()) {
      case AcquiaCloudPlatform::getPlatformId():
        $backup_command = $this->getApplication()->find(AcquiaCloudBackupDelete::getDefaultName());
        break;

      case AcquiaCloudMultiSitePlatform::getPlatformId():
        $backup_command = $this->getApplication()->find(AcquiaCloudBackupDeleteMultisite::getDefaultName());
        break;

      case ACSFPlatform::getPlatformId():
        $backup_command = $this->getApplication()->find(AcsfBackupDelete::getDefaultName());
        break;
    }

    $backup_command->addPlatform($input->getArgument('alias'), $platform);
    return $backup_command->run(new ArrayInput(['alias' => $input->getArgument('alias')]), $output);

  }

}
