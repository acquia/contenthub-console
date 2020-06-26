<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubInterestListDiff
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubInterestListDiff extends ContentHubCommandBase implements PlatformBootStrapCommandInterface {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:health-check:interest-diff';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('List the differences between interest list and tracking tables.');
    $this->setAliases(['ach-hc-id']);
  }

  /**
   * {@inheritdoc}
   */
  public function getPlatformBootstrapType(): string {
    return 'drupal8';
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    if (!$this->achClientService->checkClient()) {
      $output->writeln('Client connection to service is not healthy.');
      return 1;
    }

    $diff = $this->achClientService->getTrackingAndInterestDiff();
    if (empty($diff)) {
      $output->writeln("No differences found.");
      return 0;
    }

    $this->renderDiffTable($output, 'interest list', $diff['tracking_diff']);
    $this->renderDiffTable($output, 'tracking table', $diff['interest_diff']);

    return 0;
  }

  /**
   * Render table with entity differences.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output.
   * @param string $from
   *   Defines from where the entity is missing.
   * @param array $diff
   *   Differences to list.
   */
  protected function renderDiffTable(OutputInterface $output, string $from, array $diff): void {
    if (empty($diff)) {
      return;
    }
    array_walk($diff, function (&$row) {
      $row = [$row];
    });

    $table = new Table($output);
    $table->setHeaders(["Missing from $from"]);
    $table->addRows($diff);
    $table->render();
  }

}
