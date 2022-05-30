<?php

namespace Acquia\Console\ContentHub\Command\PqCommands;

use Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory;
use Acquia\Console\ContentHub\Command\Helpers\VersionFetcher;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Provides general checks for the drupal sites.
 */
class ContentHubPqGeneral extends ContentHubPqCommandBase {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'ach:pq:general';

  /**
   * The Drupal service factory service.
   *
   * @var \Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory
   */
  protected $serviceFactory;

  /**
   * The project version fetcher service.
   *
   * @var \Acquia\Console\ContentHub\Command\Helpers\VersionFetcher
   */
  protected $versionFetcher;

  /**
   * Constructs a ContentHubPqGeneral object.
   *
   * @param \Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory $serviceFactory
   *   The Drupal service factory.
   * @param \Acquia\Console\ContentHub\Command\Helpers\VersionFetcher $versionFetcher
   *   The project version fetcher.
   * @param string|null $name
   *   The name of this command.
   */
  public function __construct(DrupalServiceFactory $serviceFactory, VersionFetcher $versionFetcher, string $name = NULL) {
    parent::__construct($name);

    $this->serviceFactory = $serviceFactory;
    $this->versionFetcher = $versionFetcher;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
    $this->setDescription('Runs general checks on the site');
  }

  /**
   * {@inheritdoc}
   */
  protected function runCommand(InputInterface $input, PqCommandResult $result): int {
    $this->checkDrupalVersion($result);

    if ($this->serviceFactory->isModulePresentInCodebase('acquia_contenthub')) {
      $this->checkChModuleVersion($result);
    }

    return 0;
  }

  /**
   * Runs check against the oldest supported Drupal version.
   *
   * @param \Acquia\Console\ContentHub\Command\PqCommands\PqCommandResult $result
   *   The result object to set the indicator for.
   *
   * @throws \Acquia\Console\ContentHub\Command\PqCommands\PqCommandException
   */
  protected function checkDrupalVersion(PqCommandResult $result): void {
    $supportedVersion = $this->getOldestSupportedDrupalVersion();
    $localVersion = $this->getSiteDrupalVersion();
    $kriName = 'Drupal Version';
    $kriVal = sprintf('Supported Drupal version: >=%s - Site Drupal version: %s', $supportedVersion, $localVersion);

    if (version_compare($localVersion, $supportedVersion) === -1) {
      $result->setIndicator($kriName,
        $kriVal,
        PqCommandResultViolations::$drupalCompatibility,
        TRUE,
      );
      return;
    }

    $result->setIndicator($kriName, $kriVal, 'Current Drupal version is supported!');
  }

  /**
   * Runs check against the latest acquia_contenthub module version.
   *
   * @param \Acquia\Console\ContentHub\Command\PqCommands\PqCommandResult $result
   *   The result object to set the indicator for.
   *
   * @throws \Acquia\Console\ContentHub\Command\PqCommands\PqCommandException
   */
  protected function checkChModuleVersion(PqCommandResult $result): void {
    $supportedVersion = $this->getLatestModuleVersion();
    $localVersion = $this->getSiteModuleVersion();
    $kriName = 'Content Hub Module Version';
    $kriVal = sprintf('Supported Drupal version: >=%s - Site Drupal version: %s', $supportedVersion, $localVersion);

    if (version_compare($localVersion, $supportedVersion) === -1) {
      $result->setIndicator(
        $kriName,
        $kriVal,
        PqCommandResultViolations::$moduleVersionOutdated,
        TRUE,
      );
      return;
    }

    $result->setIndicator($kriName, $kriVal, 'Current Module version is the latest!');
  }

  /**
   * Returns the oldest supported Drupal version.
   *
   * @return string
   *   The version string: major.minor.patch.
   *
   * @throws \Acquia\Console\ContentHub\Command\PqCommands\PqCommandException
   */
  public function getOldestSupportedDrupalVersion(): string {
    $latestStableDrupalVersion = $this->versionFetcher->fetchProjectVersion('drupal');
    if ($latestStableDrupalVersion === '') {
      throw ContentHubPqCommandErrors::newException(
        ContentHubPqCommandErrors::$versionRetrievalErrorWithContext,
        ['drupal']
      );
    }
    [$major, $minor] = explode('.', $latestStableDrupalVersion);
    return sprintf('%s.%s.%s', $major, (int) $minor - 1, 0);
  }

  /**
   * Returns the site's current Drupal version.
   *
   * @return string
   *   The version string: major.minor.patch.
   */
  public function getSiteDrupalVersion(): string {
    return \Drupal::VERSION;
  }

  /**
   * Returns the latest acquia_contenthub module version.
   *
   * @return string
   *   The version.
   *
   * @throws \Acquia\Console\ContentHub\Command\PqCommands\PqCommandException
   */
  public function getLatestModuleVersion(): string {
    $version = $this->versionFetcher->fetchProjectVersion('acquia_contenthub');
    if ($version === '') {
      throw ContentHubPqCommandErrors::newException(
        ContentHubPqCommandErrors::$versionRetrievalErrorWithContext,
        ['acquia_contenthub module']
      );
    }
    return substr($version, strlen('8.x-'));
  }

  /**
   * Returns the version of acquia_contenthub available in the code base.
   *
   * @return string
   *   The version.
   *
   * @throws \Exception
   */
  public function getSiteModuleVersion(): string {
    $module = $this->serviceFactory->getDrupalService('module_handler')->getModule('acquia_contenthub');
    $path = $module->getPath();
    $versions = Yaml::parseFile("$path/acquia_contenthub_versions.yml");
    return $versions['acquia_contenthub'];
  }

}
