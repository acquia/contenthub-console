<?php

namespace Acquia\Console\ContentHub\Tests\Command;

use Acquia\Console\Cloud\Tests\Command\CommandTestHelperTrait;
use Acquia\Console\ContentHub\Client\PlatformCommandExecutioner;
use Acquia\Console\ContentHub\Command\ContentHubVersion;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class ContentHubVersionTest
 *
 * @coversDefaultClass \Acquia\Console\ContentHub\Command\ContentHubVersion
 *
 * @group acquia-console-contenthub
 *
 * @package Acquia\Console\ContentHub\Tests\Command
 */
class ContentHubVersionTest extends TestCase {

  use CommandTestHelperTrait;

  /**
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $platformCommandExecutioner;

  /**
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
   * Test ContentHubVersion command.
   *
   * @covers ::execute
   *
   * @param array $alias_options
   *   Alias options to be passed to command.
   * @param array $diff_output
   *   Output for ContentHubDiff command.
   * @param array $drush_output
   *   Output for DrushWrapper command.
   * @param array $ch_mv_output
   *   Output for ContentHubModuleVersion command.
   * @param array $lift_version_output
   *   Output for ContentHubLiftVersion command.
   * @param int $exit_code
   *   Expected return value of execute() method.
   *
   * @param string $needle
   *   Needle to assert.
   *
   * @dataProvider dataProvider
   */
  public function testContentHubVersion(array $alias_options, array $diff_output, array $drush_output, array $ch_mv_output, array $lift_version_output, int $exit_code, string $needle) {
    $input_array = [];
    if (array_key_exists('--lift-support', $alias_options) && array_key_exists('input', $alias_options)) {
      $input_array = [$alias_options['input']];
      unset($alias_options['input']);
      $this
        ->platformCommandExecutioner
        ->runWithMemoryOutput(Argument::any(), Argument::any(), Argument::any())
        ->shouldBeCalled()
        ->willReturn(
          // Depending on the call stack, output needs to be changed as these are consecutive calls.
          // In first iteration: Lift version is sent as 3, In next iteration: Lift version is sent as 4.
          $this->runWithMemoryOutputMocks($diff_output),
          $this->runWithMemoryOutputMocks($drush_output),
          $this->runWithMemoryOutputMocks($ch_mv_output),
          $this->runWithMemoryOutputMocks($lift_version_output[0]),
          $this->runWithMemoryOutputMocks($diff_output),
          $this->runWithMemoryOutputMocks($drush_output),
          $this->runWithMemoryOutputMocks($ch_mv_output),
          $this->runWithMemoryOutputMocks($lift_version_output[1])
        );
    }
    else if(array_key_exists('--lift-support', $alias_options)) {
      $this
        ->platformCommandExecutioner
        ->runWithMemoryOutput(Argument::any(), Argument::any(), Argument::any())
        ->shouldBeCalled()
        ->willReturn(
          $this->runWithMemoryOutputMocks($diff_output),
          $this->runWithMemoryOutputMocks($drush_output),
          $this->runWithMemoryOutputMocks($ch_mv_output),
          $this->runWithMemoryOutputMocks($lift_version_output));
    }
    else {
      $this
        ->platformCommandExecutioner
        ->runWithMemoryOutput(Argument::any(), Argument::any(), Argument::any())
        ->shouldBeCalled()
        ->willReturn($this->runWithMemoryOutputMocks($diff_output), $this->runWithMemoryOutputMocks($drush_output), $this->runWithMemoryOutputMocks($ch_mv_output));
    }
    $command = $this->getCommand();

    /** @var \Symfony\Component\Console\Tester\CommandTester $command_tester */
    $command_tester = $this->doRunCommand($command, $input_array, $alias_options);
    $this->assertEquals($needle, $command_tester->getDisplay());
    $this->assertEquals($exit_code, $command_tester->getStatusCode());
  }

  /**
   * Helper function to get the output depending on the command.
   *
   * @param array $output
   *   Array containing exit code and command output.
   *
   * @return object
   */
  private function runWithMemoryOutputMocks(array $output): object {
    return new class($output['exit_code'], $output['command_output']) {
      public function __construct(int $exit_code, string $command_output) {
        $this->exit_code = $exit_code;
        $this->command_output = $command_output;
      }

      public function getReturnCode() {return $this->exit_code;}

      public function __toString() {return $this->command_output;}
    };
  }

  /**
   * A data provider for ::testContentHubVersion
   *
   * @return array[]
   */
  public function dataProvider() {
    return [
      [
        // Test case to validate command works fine without Lift version.
        // Alias options.
        ['alias' => 'test', '--uri' => "dev.acquiacloud.com"],
        // DrushWrapper output.
        ['exit_code' => 0, 'command_output' => '{"success":true,"data":{"drush_output":"","drush_error":"[success] Cache rebuild complete."}}'],
        // ContentHubDiff output.
        ['exit_code' => 0, 'command_output' => '{"dummy_output":"dummy"}'],
        // ContentHubModuleVersion output.
        ['exit_code' => 0, 'command_output' => '{"success":true,"data":{"module_version":2,"base_url":"dev.acquiacloud.com"}}'],
        // ContentHubLiftVersion output.
        [],
        // Exit code of overall ContentHubVersion command.
        0,
        // Needle.
        'Looking for 2.x version of Content Hub module'
        // This new line is due to drush_output as it is set to empty string and is getting printed with new line.
        . PHP_EOL
        . PHP_EOL . '[success] Cache rebuild complete.'
        . PHP_EOL . 'All sites are up-to-date. You may proceed.'
        . PHP_EOL
      ],
      // Test case to validate lift module availability if lift-support input option is provided.
      [
        // Alias options.
        ['alias' => 'test', '--uri' => "dev.acquiacloud.com", '--lift-support' => TRUE],
        // DrushWrapper output.
        ['exit_code' => 0, 'command_output' => '{"success":true,"data":{"drush_output":"","drush_error":"[success] Cache rebuild complete."}}'],
        // ContentHubDiff output.
        ['exit_code' => 0, 'command_output' => '{"dummy_output":"dummy"}'],
        // ContentHubModuleVersion output.
        ['exit_code' => 0, 'command_output' => '{"success":true,"data":{"module_version":2,"base_url":"dev.acquiacloud.com"}}'],
        // ContentHubLiftVersion output.
        ['exit_code' => 0, 'command_output' => '{"success":true,"data":{"module_version":4,"configured":true,"base_url":"dev.acquiacloud.com"}}'],
        // Exit code of overall ContentHubVersion command.
        0,
        // Needle.
        'Looking for 2.x version of Content Hub module'
        // This new line is due to drush_output as it is set to empty string and is getting printed with new line.
        . PHP_EOL
        . PHP_EOL . '[success] Cache rebuild complete.'
        . PHP_EOL . 'All sites are up-to-date. You may proceed.'
        . PHP_EOL
      ],
      // Test case to check at first lift version is 3 then in 2nd iteration it is set to 4.
      [
        // Alias options. input set to yes so that command becomes interactive.
        ['alias' => 'test', '--uri' => "dev.acquiacloud.com", '--lift-support' => TRUE, 'input' => 'yes'],
        // DrushWrapper output.
        ['exit_code' => 0, 'command_output' => '{"success":true,"data":{"drush_output":"","drush_error":"[success] Cache rebuild complete."}}'],
        // ContentHubDiff output.
        ['exit_code' => 0, 'command_output' => '{"dummy_output":"dummy"}'],
        // ContentHubModuleVersion output.
        ['exit_code' => 0, 'command_output' => '{"success":true,"data":{"module_version":2,"base_url":"dev.acquiacloud.com"}}'],
        // ContentHubLiftVersion output. Since in the first iteration Lift version is 3, command becomes interactive.
        [['exit_code' => 0, 'command_output' => '{"success":true,"data":{"module_version":3,"configured":true,"base_url":"dev.acquiacloud.com"}}'],['exit_code' => 0, 'command_output' => '{"success":true,"data":{"module_version":4,"configured":true,"base_url":"dev.acquiacloud.com"}}']],
        // Exit code of overall ContentHubVersion command.
        0,
        // Needle.
        'Looking for 2.x version of Content Hub module'
        // This new line is due to drush_output as it is set to empty string and is getting printed with new line.
        . PHP_EOL
        . PHP_EOL . '[success] Cache rebuild complete.'
        . PHP_EOL . 'The following sites do not have 4.x version of Lift'
        . PHP_EOL .   '+---------------------+
| Url                 |
+---------------------+
| dev.acquiacloud.com |
+---------------------+'
        . PHP_EOL . 'Please include the up-to-date version of Acquia Lift (8.x-4.x) in the deploy!'
        . PHP_EOL . 'Please deploy and hit enter once the code is up-to-date!'
        . PHP_EOL . '[success] Cache rebuild complete.'
        . PHP_EOL . 'All sites are up-to-date. You may proceed.'
        . PHP_EOL
      ],
    ];
  }

  /**
   * Helper method to get object of ContentHubVersion Command with URI InputOption.
   *
   * @return \Acquia\Console\ContentHub\Command\ContentHubVersion
   */
  private function getCommand(): ContentHubVersion {
    return new class($this->dispatcher->reveal(), $this->platformCommandExecutioner->reveal()) extends ContentHubVersion {
      public function configure() {
        parent::configure();
        $this->addOption('uri', NULL, InputOption::VALUE_OPTIONAL, 'The url from which to mock a request.');
      }
    };
  }

}
