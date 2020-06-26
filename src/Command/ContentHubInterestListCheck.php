<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubHealthCheck
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubInterestListCheck extends ContentHubCommandBase implements PlatformBootStrapCommandInterface {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:health-check:interest-list';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Compares client interest list with already imported/exported entities.');
    $this->setAliases(['ach-hc-il']);
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
      $output->writeln('There are no entities in tracking table(s) and interest list.');
      return 0;
    }

    $tracking_diff_count = count($diff['tracking_diff']);
    $interest_diff_count = count($diff['interest_diff']);

    if ($tracking_diff_count === $interest_diff_count
      && $tracking_diff_count === 0) {
      $output->writeln('Interest list and tracking table are good to work with.');
      return 0;
    }

    if ($tracking_diff_count !== 0) {
      $output->writeln("<error>There are $tracking_diff_count entities in the tracking table which missing from the interest list.</error>");
    }

    if ($interest_diff_count !== 0) {
      $output->writeln("<error>There are $interest_diff_count entities on the interest list but missing from the tracking table(s).</error>");
    }

    $output->writeln('Listing the difference use ach:health-check:interest-diff command');

    return 0;
  }

}
