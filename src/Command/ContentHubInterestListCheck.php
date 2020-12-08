<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubInterestListCheck
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
  protected function execute(InputInterface $input, OutputInterface $output) {
    if (!$this->achClientService->checkClient()) {
      $output->writeln('<error>Client connection to service is not healthy.</error>');
      return 1;
    }

    $diff = $this->achClientService->getTrackingAndInterestDiff();
    if (empty($diff)) {
      $output->writeln('<info>There are no entities in tracking table(s) and interest list.</info>');
      return 0;
    }

    $tracking_diff_count = count($diff['tracking_diff']);
    $interest_diff_count = count($diff['interest_diff']);

    if ($tracking_diff_count === $interest_diff_count && $tracking_diff_count === 0) {
      $output->writeln('<info>Interest list and tracking table are good to work with.</info>');
      return 0;
    }

    if ($tracking_diff_count > 0) {
      $output->writeln(sprintf('<error>There are %u entities in the tracking table which missing from the interest list.</error>', $tracking_diff_count));
    }

    if ($interest_diff_count > 0) {
      $output->writeln(sprintf('<error>There are %u entities on the interest list but missing from the tracking table(s).</error>', $interest_diff_count));
    }

    $output->writeln('<info>Listing the difference use ach:health-check:interest-diff command</info>');

    return 2;
  }

}
