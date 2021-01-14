<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use Acquia\Console\Helpers\Command\PlatformCmdOutputFormatterTrait;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubLiftVersion.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubLiftVersion extends ContentHubCommandBase implements PlatformBootStrapCommandInterface {

  use PlatformCmdOutputFormatterTrait;

  public const ACQUIA_LIFT_USAGE = 'acquia.lift.usage';

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:lift-version';

  /**
   *
   */
  protected function configure() {
    $this->setDescription('Checks for the Acquia Lift module 4.x version.');
    $this->setHidden('TRUE');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $module_list = $this->drupalServiceFactory->getDrupalService('extension.list.module');
    if (!$module_list->exists('acquia_lift')) {
      return 1;
    }

    if (!$this->drupalServiceFactory->getDrupalService('module_handler')->moduleExists('acquia_lift')) {
      return 2;
    }

    if (!$this->isAcquiaLiftConfigured()) {
      return 3;
    }

    $output->writeln($this->toJsonSuccess([
      'module_version' => $module_list->exists('acquia_lift_publisher') ? 4 : 3,
      'configured' => $this->isAcquiaLiftConfigured(),
      'base_url' => $input->hasOption('uri') ? $input->getOption('uri') : '',
    ]));

    return 0;
  }

  /**
   *
   */
  protected function isAcquiaLiftConfigured(): bool {
    $config = $this->drupalServiceFactory->getDrupalService('config.factory')->getEditable('acquia_lift.settings');
    return empty($config->get('credential.account_id')) ? FALSE : TRUE;
  }

}
