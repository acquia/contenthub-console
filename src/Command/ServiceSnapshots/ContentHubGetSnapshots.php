<?php

namespace Acquia\Console\ContentHub\Command\ServiceSnapshots;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use Acquia\Console\Helpers\Command\PlatformCmdOutputFormatterTrait;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubGetSnapshots.
 *
 * @package Acquia\Console\ContentHub\Command\ServiceSnapshots
 */
class ContentHubGetSnapshots extends ContentHubCommandBase implements PlatformBootStrapCommandInterface {

  use PlatformCmdOutputFormatterTrait;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:get-snapshot';

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
    $this->setDescription('Fetch Acquia Content Hub snapshots.')
      ->setHidden(TRUE)
      ->setAliases(['ach-gs'])
      ->addOption('list', 'l', InputOption::VALUE_NONE, 'List Acquia Content Hub snapshots.');

  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $snapshots = $this->achClientService->getSnapshots();
    if (!$snapshots['success']) {
      $output->writeln(sprintf('<warning>Not able to fetch snapshots.</warning>'));
      return 1;
    }
    if ($input->getOption('list')) {
      $output->writeln($this->toJsonSuccess([
        'snapshots' => $snapshots['data'],
      ]));
      return 0;
    }

    $this->renderTable($output, $snapshots['data']);
    return 0;
  }

  /**
   * Renders output with not up-to-date sites.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output.
   * @param array $rows
   *   Rows of the table.
   */
  protected function renderTable(OutputInterface $output, array $rows) {
    $data = [];
    foreach ($rows as $key => $row) {
      $data[] = [$row];
    }
    $table = new Table($output);
    $table->setHeaders(['Snapshots']);
    $table->addRows($data);
    $table->render();
  }

}
