<?php

namespace Acquia\Console\ContentHub\Tests\Command;

use Acquia\Console\ContentHub\Command\ContentHubLiftVersion;
use Acquia\Console\ContentHub\Tests\ContentHubCommandTestBase;
use Prophecy\Argument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class ContentHubLiftVersionTest.
 *
 * @coversDefaultClass \Acquia\Console\ContentHub\Command\ContentHubLiftVersion
 *
 * @group acquia-console-contenthub
 *
 * @package Acquia\Console\ContentHub\Tests\Command
 */
class ContentHubLiftVersionTest extends ContentHubCommandTestBase {

  /**
   * Test lift version module.
   *
   * @covers ::execute
   *
   * @param array $module_exist
   *   Array containing expected return value of exist method for each module.
   * @param bool $module_enabled
   *   Expected return value of moduleExists() method.
   * @param string $lift_account_id
   *   Expected return value of lift credential account id.
   * @param string $needle
   *   String to look for within display.
   * @param int $exit_code
   *   Expected return value of execute() method.
   *
   * @dataProvider dataProvider
   */
  public function testContentHubLiftVersion(array $module_exist, bool $module_enabled, string $lift_account_id, string $needle, int $exit_code) {
    $this
      ->drupalServiceFactory
      ->getDrupalService(Argument::any())
      ->shouldBeCalled()
      ->willReturn($this->getDrupalServiceMocks($module_exist, $module_enabled, $lift_account_id));

    $command = $this->getCommand();
    $command->setDrupalServiceFactory($this->drupalServiceFactory->reveal());
    $command->setAchClientService($this->contentHubService->reveal());

    /** @var \Symfony\Component\Console\Tester\CommandTester $command_tester */
    $command_tester = $this->doRunCommand($command, [], ['alias' => 'test', '--uri' => 'dev.acquiacloud.com']);
    $this->assertStringContainsString($needle, $command_tester->getDisplay());
    $this->assertEquals($exit_code, $command_tester->getStatusCode());

  }

  /**
   * Returns mock instance for getDrupalService().
   *
   * @param array $module_exist
   *   Array containing return value of exists() for each module.
   * @param bool $module_enabled
   *   Return value for moduleExists().
   * @param string $lift_account_id
   *   Return value for lift credential account id.
   *
   * @return object
   *   Mock of getDrupalService function return.
   */
  public function getDrupalServiceMocks(array $module_exist, bool $module_enabled, string $lift_account_id): object {
    return new class ($module_exist, $module_enabled, $lift_account_id) {

      /**
       *
       */
      public function __construct(array $module_exist, bool $module_enabled, string $lift_account_id) {
        $this->module_exist = $module_exist;
        $this->enabled = $module_enabled;
        $this->lift_account_id = $lift_account_id;
      }

      /**
       *
       */
      public function exists(string $module_name): bool {
        return $this->module_exist[$module_name];
      }

      /**
       *
       */
      public function moduleExists(): bool {
        return $this->enabled;
      }

      /**
       *
       */
      public function getEditable(): object {
        return $this;
      }

      /**
       *
       */
      public function get(): string {
        return $this->lift_account_id;
      }

    };
  }

  /**
   * A data provider for ::testContentHubLiftVersion()
   *
   * @return array[]
   */
  public function dataProvider() {
    return [
      [
        ['acquia_lift' => TRUE, 'acquia_lift_publisher' => TRUE],
        TRUE,
        'a123er4w3e4',
        '{"success":true,"data":{"module_version":4,"configured":true,"base_url":"dev.acquiacloud.com"}}',
        0,
      ],
      [
        ['acquia_lift' => TRUE, 'acquia_lift_publisher' => FALSE],
        TRUE,
        'a123er4w3e4',
        '{"success":true,"data":{"module_version":3,"configured":true,"base_url":"dev.acquiacloud.com"}}',
        0,
      ],
      [
        ['acquia_lift' => TRUE, 'acquia_lift_publisher' => TRUE],
        TRUE,
        '',
        '',
        3,
      ],
      [
        ['acquia_lift' => TRUE, 'acquia_lift_publisher' => FALSE],
        TRUE,
        '',
        '',
        3,
      ],
      [
        ['acquia_lift' => TRUE, 'acquia_lift_publisher' => TRUE],
        FALSE,
        '',
        '',
        2,
      ],
      [
        ['acquia_lift' => TRUE, 'acquia_lift_publisher' => FALSE],
        FALSE,
        '',
        '',
        2,
      ],
      [
        ['acquia_lift' => FALSE, 'acquia_lift_publisher' => FALSE],
        TRUE,
        '',
        '',
        1,
      ],
      [
        ['acquia_lift' => FALSE, 'acquia_lift_publisher' => TRUE],
        FALSE,
        '',
        '',
        1,
      ],
      [
        ['acquia_lift' => FALSE, 'acquia_lift_publisher' => FALSE],
        FALSE,
        '',
        '',
        1,
      ]
    ];
  }

  /**
   * Helper method to get object of ContentHubLiftVersion Command with URI InputOption.
   *
   * @return \Acquia\Console\ContentHub\Command\ContentHubLiftVersion
   */
  private function getCommand() : ContentHubLiftVersion {
    return new class extends ContentHubLiftVersion {

      /**
       *
       */
      protected function configure() {
        parent::configure();
        $this->addOption('uri', NULL, InputOption::VALUE_OPTIONAL, 'The url from which to mock a request.');
      }

    };
  }

}
