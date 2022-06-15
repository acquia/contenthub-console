<?php

namespace Acquia\Console\ContentHub\Command;

use Acquia\Console\ContentHub\Client\ContentHubCommandBase;
use Acquia\Console\ContentHub\Command\Helpers\DirectoryIteratorTrait;
use Acquia\Console\ContentHub\Exception\ContentHubVersionException;
use EclipseGc\CommonConsole\Command\PlatformBootStrapCommandInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ContentHubAuditChServices.
 *
 * @package Acquia\Console\ContentHub\Command
 */
class ContentHubAuditChServices extends ContentHubCommandBase implements PlatformBootStrapCommandInterface {

  use DirectoryIteratorTrait;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:audit:ch-services';

  /**
   * {@inheritdoc}
   */
  public function configure() {
    $this->setDescription('Audit deprecated 1.x service usage')
      ->setHidden(TRUE)
      ->setAliases(['ach-serv']);
  }

  /**
   * {@inheritdoc}
   */
  protected function initialize(InputInterface $input, OutputInterface $output) {
    parent::initialize($input, $output);
    if ($this->achClientService->getVersion() !== 1) {
      throw new ContentHubVersionException(1);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $output->writeln('Checking deprecated service usage in modules...');
    $files = [];

    try {
      $regex = $this->getFilesInfo('/^((?!acquia_contenthub).)*$/', 'modules');
    }
    catch (\Exception $exception) {
      $output->writeln($exception->getMessage());
      return 0;
    }

    $regex_pattern = $this->getRegexPattern();
    foreach ($regex as $file) {
      $content = file_get_contents($file[0]);
      if (preg_match_all("/$regex_pattern/", $content, $matches)) {
        $files[] = [$file[0], implode(', ', $matches[0])];
      }
    }

    if (!$files) {
      $output->writeln('<info>No deprecated service usage found!</info>');
      return 0;
    }

    $output->writeln('<warning>Following deprecated ACH services found.</warning>');
    $table = new Table($output);
    $table->setHeaders(['File', 'Matches found']);
    $table->addRows($files);
    $table->render();

    return 1;
  }

  /**
   * Returns regex pattern.
   *
   * @return string
   *   Returns a regex pattern which search for ACH services.
   */
  protected function getRegexPattern(): string {
    return implode('|', $this->getDeprecatedServices());
  }

  /**
   * Returns all ACH services from container.
   *
   * @return array
   *   String[] which contains deprecated ach services.
   */
  protected function getDeprecatedServices(): array {
    $deprecated_services = [];

    $container = \Drupal::getContainer();
    $kernel = $container->get('kernel');
    $services = $kernel->getCachedContainerDefinition()['services'];
    foreach ($services as $service_id => $value) {
      $service_definition = unserialize($value);
      if (strpos($service_definition['class'], 'acquia_contenthub')) {
        $deprecated_services[] = $service_definition['properties']['_serviceId'];
      }
    }

    return $deprecated_services;
  }

}
