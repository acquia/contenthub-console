<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use Acquia\Console\ContentHub\Command\Helpers\PlatformCmdOutputFormatterTrait;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubAuditClients
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubAuditClients extends ContentHubCommandBase implements PlatformBootStrapCommandInterface {

  use PlatformCmdOutputFormatterTrait;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:clients';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setDescription('Lists the clients registered in the Acquia Content Hub Subscription.');
    $this->setAliases(['ach-cl']);
    $this->addOption('--count', 'c', InputOption::VALUE_NONE, 'Returns the number of clients.');
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
    $clients = $this->achClientService->getClients();
    if ($input->getOption('count')) {
      $output->writeln($this->toJsonSuccess([
        'count' => count($clients),
      ]));
      return 0;
    }

    $rows = [];
    foreach ($clients as $key => $client) {
      $rows[] = [$key, $client];
    }

    $table = new Table($output);
    $table->setHeaders(['Client name', 'Url']);
    $table->addRows($rows);
    $table->render();

    return 0;
  }

}
