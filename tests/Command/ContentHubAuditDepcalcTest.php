<?php

namespace Acquia\Console\ContentHub\Tests\Command;

use Acquia\Console\ContentHub\Command\ContentHubAuditDepcalc;
use Acquia\Console\ContentHub\Tests\ContentHubCommandTestBase;
use Prophecy\Argument;

/**
 * Class ContentHubAuditDepcalcTest.
 *
 * @coversDefaultClass \Acquia\Console\ContentHub\Command\ContentHubAuditDepcalc
 *
 * @group acquia-console-contenthub
 *
 * @package Acquia\Console\ContentHub\Tests\Command
 */
class ContentHubAuditDepcalcTest extends ContentHubCommandTestBase {

  /**
   * Test depcalc module audit.
   *
   * @covers ::execute
   *
   * @param bool $module_exist
   *   Expected return value of exists() method.
   * @param bool $module_enabled
   *   Expected return value of moduleExists() method.
   * @param string $needle
   *   String to look for within display.
   * @param int $exit_code
   *   Expected return value of execute() method.
   *
   * @dataProvider dataProvider
   */
  public function testContentHubAuditDepcalc(bool $module_exist, bool $module_enabled, string $needle, int $exit_code) {
    $this
      ->drupalServiceFactory
      ->getDrupalService(Argument::any())
      ->shouldBeCalled()
      ->willReturn($this->getDrupalServiceMocks($module_exist, $module_enabled));

    $command = new ContentHubAuditDepcalc();
    $command->setDrupalServiceFactory($this->drupalServiceFactory->reveal());
    $command->setAchClientService($this->contentHubService->reveal());

    /** @var \Symfony\Component\Console\Tester\CommandTester $command_tester */
    $command_tester = $this->doRunCommand($command, [], ['alias' => 'test']);
    $this->assertStringContainsString($needle, $command_tester->getDisplay());
    $this->assertEquals($exit_code, $command_tester->getStatusCode());
  }

  /**
   * Returns mock instance for getDrupalService().
   *
   * @param $module_exist
   *   Return value for exists().
   * @param $module_enabled
   *   Return value for moduleExists().
   *
   * @return object
   *   Mock of getDrupalService function return.
   */
  public function getDrupalServiceMocks(bool $module_exist, bool $module_enabled): object {
    return new class ($module_exist, $module_enabled) {
      public function __construct(bool $module_exist, bool $module_enabled) {
        $this->exists = $module_exist;
        $this->enabled = $module_enabled;
      }
      public function exists(): bool {return $this->exists;}
      public function moduleExists(): bool {return $this->enabled;}
    };
  }

  /**
   * A data provider for ::testContentHubAuditDepcalc()
   *
   * @return array[]
   */
  public function dataProvider() {
    return [
      [
        TRUE,
        TRUE,
        'Depcalc module is present. You may proceed.',
        0,
      ],
      [
        TRUE,
        FALSE,
        'Depcalc module is not enabled.',
        1,
      ],
      [
        FALSE,
        TRUE,
        'Depcalc module is missing from dependencies! Please run: composer require drupal/depcalc and deploy to your environment.',
        2,
      ],
      [
        FALSE,
        FALSE,
        'Depcalc module is missing from dependencies! Please run: composer require drupal/depcalc and deploy to your environment.',
        2,
      ],
    ];
  }

}
