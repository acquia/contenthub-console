<?php

namespace Acquia\Console\ContentHub\Tests\Command;

use Acquia\Console\ContentHub\Command\ContentHubModuleVersion;
use Acquia\Console\ContentHub\Tests\ContentHubCommandTestBase;
use Prophecy\Argument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class ContentHubModuleVersionTest
 *
 * @coversDefaultClass \Acquia\Console\ContentHub\Command\ContentHubModuleVersion
 *
 * @group acquia-console-contenthub
 *
 * @package Acquia\Console\ContentHub\Tests\Command
 */
class ContentHubModuleVersionTest extends ContentHubCommandTestBase {

  /**
   * Test content hub module version.
   *
   * @covers ::execute
   *
   * @param bool $module_exist
   *   Expected return value of exists() method.
   * @param int $module_version
   *   Expected return value of getModuleVersion() method.
   * @param string $needle
   *   String to look for within display.
   * @param int $exit_code
   *   Expected return value of execute() method.
   *
   * @dataProvider dataProvider
   */
  public function testContentHubModuleVersion(bool $module_exist, int $module_version, string $needle, int $exit_code) {
    $this
      ->drupalServiceFactory
      ->isModuleEnabled(Argument::any())
      ->shouldBeCalled()
      ->willReturn($module_exist);

    if ($module_exist) {
      $this
        ->drupalServiceFactory
        ->getModuleVersion(Argument::any())
        ->shouldBeCalled()
        ->willReturn($this->getModuleVersionMocks($module_version));
    }

    $command = $this->getCommand();
    $command->setDrupalServiceFactory($this->drupalServiceFactory->reveal());
    $command->setAchClientService($this->contentHubService->reveal());

    /** @var \Symfony\Component\Console\Tester\CommandTester $command_tester */
    $command_tester = $this->doRunCommand($command, [], ['alias' => 'test', '--uri' => 'dev.acquiacloud.com']);
    $this->assertStringContainsString($needle, $command_tester->getDisplay());
    $this->assertEquals($exit_code, $command_tester->getStatusCode());
  }

  /**
   * Returns mock instance for getModuleVersion().
   *
   * @param int $module_version
   *   Return value of getModuleVersion().
   *
   * @return int
   *   Mock of getModuleVersion function return.
   */
  public function getModuleVersionMocks(int $module_version): int {return $module_version;}

  /**
   * A data provider for ::testContentHubModuleVersion
   *
   * @return array[]
   */
  public function dataProvider() {
    return [
      [
        TRUE,
        1,
        '{"success":true,"data":{"module_version":1,"base_url":"dev.acquiacloud.com"}}',
        0,
      ],
      [
        TRUE,
        2,
        '{"success":true,"data":{"module_version":2,"base_url":"dev.acquiacloud.com"}}',
        0,
      ],
      [
        FALSE,
        0,
        '',
        1,
      ],
      [
        FALSE,
        0,
        '',
        1,
      ],
    ];
  }

  /**
   * Helper method to get object of ContentHubModuleVersion Command with URI InputOption.
   *
   * @return \Acquia\Console\ContentHub\Command\ContentHubModuleVersion
   */
  private function getCommand(): ContentHubModuleVersion {
    return new class extends ContentHubModuleVersion {
      protected function configure() {
        parent::configure();
        $this->addOption('uri', NULL, InputOption::VALUE_OPTIONAL, 'The url from which to mock a request.');
      }
    };
  }

}
