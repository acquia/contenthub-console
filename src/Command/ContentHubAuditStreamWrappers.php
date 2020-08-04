<?php

namespace Acquia\Console\ContentHub\Command;

use Drupal\Core\Site\Settings;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubAuditStreamWrappers.
 *
 * Prints registered stream wrappers to stdout, as well as if private file path
 * is set.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubAuditStreamWrappers extends Command implements PlatformBootStrapCommandInterface {

  /**
   * {@inheritDoc}
   */
  protected static $defaultName = 'ach:audit:stream-wrappers';

  /**
   * @inheritDoc
   */
  public function getPlatformBootstrapType(): string {
    return 'drupal8';
  }

  /**
   * {@inheritDoc}
   */
  protected function configure() {
    $this->setDescription('Audit stream wrappers and private file path.')
      ->setAliases(['ach-asw']);
  }

  /**
   * {@inheritDoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $output->writeln('Auditing stream wrappers...');
    $stream_wrapper_mngr = \Drupal::service('stream_wrapper_manager');
    $wrappers = $stream_wrapper_mngr->getNames();
    $table = new Table($output);
    $table->setHeaders(['Scheme', 'Stream Wrapper Name']);
    foreach ($wrappers as $scheme => $name) {
      $table->addRow([$scheme, $name]);
    }
    $table->render();

    $private_file_path = Settings::get('file_private_path');
    if ($private_file_path) {
      $output->writeln("<warning>Private file path is set to '$private_file_path'. Private file syndication is not supported currently.</warning>");
    }

    return 0;
  }

}
