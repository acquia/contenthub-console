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
   * @param bool $module_exist
   *   Expected return value of exists() method.
   * @param bool $module_enabled
   *   Expected return value of moduleExists() method.
   * @param bool $enable_depcalc
   *   Enable depcalc module.
   * @param string $needle
   *   String to look for within display.
   * @param int $exit_code
   *   Expected return value of execute() method.
   *
   * @covers ::execute
   *
   * @dataProvider dataProvider
   */
  public function testContentHubAuditDepcalc(bool $module_exist, bool $module_enabled, bool $enable_depcalc, string $needle, int $exit_code) {
    $this
      ->drupalServiceFactory
      ->isModulePresentInCodebase(Argument::any())
      ->shouldBeCalled()
      ->willReturn($module_exist);
    if ($module_exist) {
      $this
        ->drupalServiceFactory
        ->isModuleEnabled(Argument::any())
        ->shouldBeCalled()
        ->willReturn($module_enabled);
    }
    if ($enable_depcalc) {
      $this
        ->drupalServiceFactory
        ->enableModules(Argument::any())
        ->shouldBeCalled()
        ->willReturn($enable_depcalc);
    }

    $command = new ContentHubAuditDepcalc();
    $command->setDrupalServiceFactory($this->drupalServiceFactory->reveal());
    $command->setAchClientService($this->contentHubService->reveal());

    /** @var \Symfony\Component\Console\Tester\CommandTester $command_tester */
    $command_tester = $this->doRunCommand($command, [], ['alias' => 'test']);
    $this->assertStringContainsString($needle, $command_tester->getDisplay());
    $this->assertEquals($exit_code, $command_tester->getStatusCode());
  }

  /**
   * A data provider for ::testContentHubAuditDepcalc()
   *
   * @return array[]
   *   Array for data provider.
   */
  public function dataProvider() {
    return [
      [
        TRUE,
        TRUE,
        FALSE,
        "Depcalc module is enabled. You may proceed.\n",
        0,
      ],
      [
        TRUE,
        FALSE,
        FALSE,
        'Depcalc module is not enabled.',
        1,
      ],
      [
        FALSE,
        TRUE,
        FALSE,
        'Depcalc module is missing from dependencies! Please run: composer require drupal/depcalc and deploy to your environment.',
        2,
      ],
      [
        FALSE,
        FALSE,
        FALSE,
        'Depcalc module is missing from dependencies! Please run: composer require drupal/depcalc and deploy to your environment.',
        2,
      ],
    ];
  }

}
