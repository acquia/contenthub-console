<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Command\Helpers\PlatformCmdOutputFormatterTrait;
use Acquia\Console\ContentHub\Command\Helpers\PlatformCommandExecutionTrait;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubLiftVersion.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubLiftVersion extends Command  implements PlatformBootStrapCommandInterface {

  use PlatformCmdOutputFormatterTrait;
  use PlatformCommandExecutionTrait;

  public const ACQUIA_LIFT_USAGE = 'acquia.lift.usage';

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:lift-version';

  /**
   * {@inheritdoc}
   */
  public function getPlatformBootstrapType(): string {
    return 'drupal8';
  }

  protected function configure() {
    $this->setDescription('Checks for the Acquia Lift module 4.x version.');
    $this->addOption('clear-cache','cr',InputOption::VALUE_OPTIONAL,'Clear cache.');
    $this->setHidden('TRUE');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $module_list = \Drupal::getContainer()->get('extension.list.module');
    if (!$module_list->exists('acquia_lift')) {
      return 1;
    }

    if (!\Drupal::moduleHandler()->moduleExists('acquia_lift')) {
      return 2;
    }

    if (!$this->isAcquiaLiftConfigured()) {
      return 3;
    }

    if ($input->getOption('clear-cache')) {
      $output->writeln('Clearing database cache...');
      $this->execDrushWithOutput($output, ['cr']);
    }

    $output->writeln($this->toJsonSuccess([
      'module_version' => $module_list->exists('acquia_lift_publisher') ? 4 : 3,
      'configured' => $this->isAcquiaLiftConfigured(),
      'base_url' => $input->getOption('uri'),
    ]));

    return 0;
  }

  protected function isAcquiaLiftConfigured(): bool {
    $config = \Drupal::configFactory()->getEditable('acquia_lift.settings');
    return empty($config->get('credential.account_id')) ? FALSE : TRUE;
  }

}
