<?php

namespace Acquia\Console\ContentHub\Command\Helpers;

use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Checks for content hub module presence on the server.
 *
 * @package Acquia\Console\ContentHub\Command\Helpers
 */
class ContentHubModuleChecker extends Command implements PlatformBootStrapCommandInterface {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:module-exists';

  /**
   * {@inheritdoc}
   */
  public function getPlatformBootstrapType(): string {
    return 'drupal8';
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Checks if Content Hub Module is enabled');
    $this->addOption('module', 'm', InputOption::VALUE_OPTIONAL,
      'Check for a specific module: publisher, subscriber, curation, moderation, preview, s3, unsubscribe.', '');
    $this->setHidden(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $sub_module = $input->getOption('module');
    $module_name = 'acquia_contenthub';
    if (!$sub_module) {
      $enabled = $this->enabled($module_name);
    }
    else {
      $enabled = $this->enabled("{$module_name}_{$sub_module}");
    }

    $enabled ? $output->writeln('enabled') : $output->writeln('disabled');
  }

  /**
   * Checks whether given module is enabled on the server.
   *
   * @param string $name
   *   The name of the module to check after.
   *
   * @return bool
   *   TRUE if enabled.
   */
  protected function enabled(string $name): bool {
    return \Drupal::moduleHandler()->moduleExists($name);
  }

}
