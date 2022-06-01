<?php

namespace Acquia\Console\ContentHub\Tests\Command\PqCommands;

use Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory;
use Acquia\Console\ContentHub\Command\Helpers\VersionFetcher;
use Acquia\Console\ContentHub\Command\PqCommands\ContentHubPqGeneral;
use Acquia\Console\ContentHub\Command\PqCommands\PqCommandResult;
use Acquia\Console\ContentHub\Command\PqCommands\PqCommandResultViolations;
use Acquia\Console\ContentHub\Tests\Drupal\DrupalServiceMockGeneratorTrait;
use Acquia\Console\ContentHub\Tests\Helpers\TempFileGeneratorTrait;
use EclipseGc\CommonConsole\Tests\CommonConsoleTestBase;
use Prophecy\Argument;
use Symfony\Component\Yaml\Yaml;

/**
 * @coversDefaultClass \Acquia\Console\ContentHub\Command\PqCommands\ContentHubPqGeneral
 *
 * @group contenthub_console_pq_commands
 */
class ContentHubPqGeneralTest extends CommonConsoleTestBase {

  use DrupalServiceMockGeneratorTrait;
  use TempFileGeneratorTrait;

  /**
   * The command to test.
   *
   * @var \Acquia\Console\ContentHub\Command\PqCommands\ContentHubPqGeneral
   */
  protected $command;

  /**
   * The drupal service factory mock to alter.
   *
   * @var \Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $drupalServiceFactory;

  /**
   * The version fetcher to alter.
   *
   * @var \Acquia\Console\ContentHub\Command\Helpers\VersionFetcher|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $versionFetcher;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->drupalServiceFactory = $this->prophesize(DrupalServiceFactory::class);
    $this->versionFetcher = $this->prophesize(VersionFetcher::class);

    $this->command = new ContentHubPqGeneral(
      $this->drupalServiceFactory->reveal(),
      $this->versionFetcher->reveal()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    $this->deleteCreatedTmpFiles();
  }

  /**
   * Tests drupal version compatibility.
   *
   * One minor version before the current stable is always supported.
   */
  public function testGetOldestSupportedDrupalVersion(): void {
    $this->versionFetcher->fetchProjectVersion(Argument::exact('drupal'))
      ->willReturn('9.3.14');
    $version = $this->command->getOldestSupportedDrupalVersion();
    $this->assertEquals('9.2.0', $version);
  }

  /**
   * Tests module version retrieval.
   *
   * Returns the latest version without the 8.x prefix.
   */
  public function testGetLatestModuleVersion(): void {
    $this->versionFetcher->fetchProjectVersion(Argument::exact('acquia_contenthub'))
      ->willReturn('8.x-2.33');
    $version = $this->command->getLatestModuleVersion();
    $this->assertEquals('2.33', $version);
  }

  /**
   * Tests whether module version can read the acquia_contenthub_versions file.
   *
   * The version file is located at ./fixtures folder.
   */
  public function testGetSiteModuleVersion(): void {
    $file = $this->generateTmpFile(
      'acquia_contenthub_versions.yml',
      Yaml::dump(['acquia_contenthub' => '2.33'])
    );
    $extension = $this->generateDrupalServiceMock([
      'getPath' => $file->dirname,
    ]);
    $moduleHandler = $this->generateDrupalServiceMock([
      'getModule' => $extension,
    ]);

    $this->drupalServiceFactory->getDrupalService(Argument::exact('module_handler'))
      ->willReturn($moduleHandler);

    $version = $this->command->getSiteModuleVersion();
    $this->assertEquals('2.33', $version);
  }

  /**
   * Tests the module version with various scenarios.
   *
   * @dataProvider checkDrupalVersionDataProvider
   *
   * @throws \Acquia\Console\ContentHub\Command\PqCommands\PqCommandException
   */
  public function testCheckDrupalVersion(string $availableVersion, string $siteVersion, array $expectedKri): void {
    $this->versionFetcher->fetchProjectVersion(Argument::exact('drupal'))
      ->willReturn($availableVersion);
    $this->drupalServiceFactory->getDrupalVersion()
      ->willReturn($siteVersion);

    $result = new PqCommandResult();
    $this->command->checkDrupalVersion($result);
    $kri = $result->getResult()[0];
    $kriValues = $kri->toArray();

    $this->assertEquals('Drupal Version', $kriValues['name']);
    $this->assertEquals($expectedKri['value'], $kriValues['value']);
    $this->assertEquals($expectedKri['message'], $kriValues['message']);
    $this->assertEquals($expectedKri['risky'], $kriValues['risky']);
  }

  /**
   * Provides input cases for testCheckDrupalVersion.
   *
   * @return array
   *   Input cases.
   */
  public function checkDrupalVersionDataProvider(): array {
    return [
      [
        '9.3.14', '9.2.14',
        [
          'value' => 'Supported Drupal version: >=9.2.0 - Site Drupal version: 9.2.14',
          'message' => 'Current Drupal version is supported!',
          'risky' => FALSE,
        ],
      ],
      [
        '9.3.14', '9.1.14',
        [
          'value' => 'Supported Drupal version: >=9.2.0 - Site Drupal version: 9.1.14',
          'message' => PqCommandResultViolations::$drupalCompatibility,
          'risky' => TRUE,
        ],
      ],
    ];
  }

  /**
   * Tests the module version with various scenarios.
   *
   * @dataProvider checkChModuleVersionDataProvider
   *
   * @throws \Acquia\Console\ContentHub\Command\PqCommands\PqCommandException
   */
  public function testCheckChModuleVersion(string $availableVersion, string $siteVersion, array $expectedKri): void {
    $this->versionFetcher->fetchProjectVersion(Argument::exact('acquia_contenthub'))
      ->willReturn($availableVersion);

    $file = $this->generateTmpFile(
      'acquia_contenthub_versions.yml',
      Yaml::dump(['acquia_contenthub' => $siteVersion])
    );

    $extension = $this->generateDrupalServiceMock([
      'getPath' => $file->dirname,
    ]);
    $moduleHandler = $this->generateDrupalServiceMock([
      'getModule' => $extension,
    ]);
    $this->drupalServiceFactory->getDrupalService(Argument::exact('module_handler'))
      ->willReturn($moduleHandler);

    $result = new PqCommandResult();
    $this->command->checkChModuleVersion($result);
    $kri = $result->getResult()[0];
    $kriValues = $kri->toArray();

    $this->assertEquals('Content Hub Module Version', $kriValues['name']);
    $this->assertEquals($expectedKri['value'], $kriValues['value']);
    $this->assertEquals($expectedKri['message'], $kriValues['message']);
    $this->assertEquals($expectedKri['risky'], $kriValues['risky']);
  }

  /**
   * Provides input cases for testCheckChModuleVersion.
   *
   * @return array
   *   Input cases.
   */
  public function checkChModuleVersionDataProvider(): array {
    return [
      [
        '8.x-2.33', '2.33',
        [
          'value' => 'Latest module version: 2.33 - Site module version: 2.33',
          'message' => 'Current Module version is the latest!',
          'risky' => FALSE,
        ],
      ],
      [
        '8.x-2.33', '2.32',
        [
          'value' => 'Latest module version: 2.33 - Site module version: 2.32',
          'message' => PqCommandResultViolations::$moduleVersionOutdated,
          'risky' => TRUE,
        ],
      ],
    ];
  }

}
