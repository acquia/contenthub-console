<?php

namespace Acquia\Console\ContentHub\Command\Backups;

use Acquia\Console\Acsf\Platform\ACSFPlatform;
use Acquia\Console\Cloud\Command\Helpers\AcquiaCloudDbBackupDeleteHelper;
use Acquia\Console\Cloud\Platform\AcquiaCloudMultiSitePlatform;
use Acquia\Console\Cloud\Platform\AcquiaCloudPlatform;
use Acquia\Console\Helpers\Client\PlatformCommandExecutioner;
use EclipseGc\CommonConsole\Platform\PlatformCommandTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class BackupList.
 *
 * @package Acquia\Console\Cloud\Command\Backups
 */
class BackupCreate extends Command {

  use PlatformCommandTrait;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'backup:create';

  /**
   * Command executioner service.
   *
   * @var \Acquia\Console\Helpers\Client\PlatformCommandExecutioner
   */
  protected $executioner;

  /**
   * AcquiaCloudBackupDelete constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher.
   * @param \Acquia\Console\Helpers\Client\PlatformCommandExecutioner $executioner
   *   Command executioner service instance.
   * @param string|NULL $name
   *   Command name.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher, PlatformCommandExecutioner $executioner, string $name = NULL) {
    parent::__construct($event_dispatcher, $name);

    $this->executioner = $executioner;
  }


  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('List available backup bundles of Content Hub Service snapshots and database site backups.');
    $this->setAliases(['bc']);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $platform = $this->getPlatform('source');
    switch ($platform) {
      case AcquiaCloudPlatform::getPlatformId():
        return $this->executioner->runLocallyWithMemoryOutput(AcquiaCloudBackupCreate::getDefaultName(), $platform, $input);
        break;

      case AcquiaCloudMultiSitePlatform::getPlatformId():
        return $this->executioner->runLocallyWithMemoryOutput(AcquiaCloudBackupCreateMultiSite::getDefaultName(), $platform, $input);
        break;

      case ACSFPlatform::getPlatformId():
        return $this->executioner->runLocallyWithMemoryOutput(AcquiaCloudBackupCreate::getDefaultName(), $platform, $input);
        break;

      default:
        break;
    }

    return 0;
  }

}
