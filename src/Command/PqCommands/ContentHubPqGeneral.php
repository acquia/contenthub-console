<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides general checks for the drupal sites.
 */
class ContentHubPqGeneral extends ContentHubPqCommandBase {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:pq:general';

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    if ($input->getOption('format') === 'json') {
      $output->write(json_encode(['some' => 'test']));
    }
    else {
      $output->writeln('some: test');
    }
    return 0;
  }

}
