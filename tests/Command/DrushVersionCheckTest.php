<?php

namespace Acquia\Console\ContentHub\Tests\Command;

use Acquia\Console\Cloud\Tests\Command\CommandTestHelperTrait;
use Acquia\Console\Helpers\PlatformCommandExecutioner;
use Acquia\Console\ContentHub\Command\DrushVersionCheck;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class DrushVersionCheckTest.
 *
 * @coversDefaultClass \Acquia\Console\ContentHub\Command\DrushVersionCheck
 *
 * @group acquia-console-contenthub
 *
 * @package Acquia\Console\ContentHub\Tests\Command
 */
class DrushVersionCheckTest extends TestCase {

  use CommandTestHelperTrait;

  /**
   * Platform Command Executioner double.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $platformCommandExecutioner;

  /**
   * Event dispatcher double.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $dispatcher;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->platformCommandExecutioner = $this->prophesize(PlatformCommandExecutioner::class);
    $this->dispatcher = $this->prophesize(EventDispatcherInterface::class);
  }

  /**
   * Test whether the drush version is correct or not.
   *
   * @param int $drush_exit_code
   *   Exit code from DrushWrapper command.
   * @param int $exit_code
   *   Expected return value of execute() method.
   * @param string $drush_output
   *   Output from DrushWrapper command.
   * @param string $needle
   *   Needle to assert.
   *
   * @covers ::execute
   *
   * @dataProvider dataProvider
   */
  public function testDrushVersionCheck(int $drush_exit_code, int $exit_code, string $drush_output, string $needle) {
    $this
      ->platformCommandExecutioner
      ->runWithMemoryOutput(Argument::any(), Argument::any(), Argument::any())
      ->shouldBeCalled()
      ->willReturn($this->runWithMemoryOutputMocks($drush_exit_code, $drush_output));

    $command = new DrushVersionCheck($this->dispatcher->reveal(), $this->platformCommandExecutioner->reveal());

    /** @var \Symfony\Component\Console\Tester\CommandTester $command_tester */
    $command_tester = $this->doRunCommand($command, [], ['alias' => 'test']);
    $this->assertEquals($needle, $command_tester->getDisplay());
    $this->assertEquals($exit_code, $command_tester->getStatusCode());
  }

  /**
   * Mock of runWithMemoryOutput function.
   *
   * @param int $exit_code
   *   Mock Exit code of Drush Wrapper command.
   * @param string $drush_output
   *   Mock Output of Drush Wrapper command.
   *
   * @return object
   *   Inline class containing response.
   */
  private function runWithMemoryOutputMocks(int $exit_code, string $drush_output): object {
    return new class($exit_code, $drush_output) {

      /**
       * Constructor.
       *
       * @param int $exit_code
       *   Exit code.
       * @param string $drush_output
       *   Drush output.
       */
      public function __construct(int $exit_code, string $drush_output) {
        $this->exit_code = $exit_code;
        $this->drush_output = $drush_output;

      }

      /**
       * Returns the response return code.
       */
      public function getReturnCode(): int {
        return $this->exit_code;
      }

      /**
       * Returns the response body.
       */
      public function __toString() {
        return $this->drush_output;
      }

    };
  }

  /**
   * A data provider for ::testDrushVersionCheck()
   *
   * @return array[]
   *   Array for data provider.
   */
  public function dataProvider(): array {
    return [
      [
        0,
        0,
        '{"success":true,"data":{"drush_output":"10.3.4","drush_error":""}}',
        'Checking drush version...' . PHP_EOL . 'Current drush version is: 10.3.4'
        . PHP_EOL
      ],
      [
        0,
        0,
        '{"success":true,"data":{"drush_output":"9.0.0","drush_error":""}}',
        'Checking drush version...' . PHP_EOL . 'Current drush version is: 9.0.0'
        . PHP_EOL
      ],
      [
        0,
        1,
        '{"success":true,"data":{"drush_output":"8.3.4","drush_error":""}}',
        'Checking drush version...' . PHP_EOL . 'Current drush version is: 8.3.4'
        . PHP_EOL . 'Drush version must be 9.0.0 or higher!'
        . PHP_EOL
      ],
      [
        1,
        2,
        '{"success":false,"error":{"message":"Couldn\'t find drush executable."}}',
        'Checking drush version...' . PHP_EOL
        . 'Error executing drush command "version" (Exit code = 1):'
        . PHP_EOL . 'Couldn\'t find drush executable.'
        . PHP_EOL . 'Attempted to run "drush". It might be missing or the executable name does not match the expected.'
        . PHP_EOL
      ],
      [
        2,
        2,
        '{"success":false,"error":{"message":"Couldn\'t find drush executable."}}',
        'Checking drush version...' . PHP_EOL
        . 'Error executing drush command "version" (Exit code = 2):'
        . PHP_EOL . 'Couldn\'t find drush executable.'
        . PHP_EOL . 'Attempted to run "drush". It might be missing or the executable name does not match the expected.'
        . PHP_EOL
      ]
    ];
  }

}
