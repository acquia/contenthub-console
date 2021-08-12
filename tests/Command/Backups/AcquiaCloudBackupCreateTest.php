<?php

namespace Acquia\Console\ContentHub\Tests\Command\Backups;

use Acquia\Console\ContentHub\Command\Backups\AcquiaCloudBackupCreate;
use Acquia\Console\Helpers\PlatformCommandExecutioner;
use EclipseGc\CommonConsole\Config\ConfigStorage;
use Prophecy\Argument;

/**
 * Class AcquiaCloudBackupCreateTest.
 *
 * @coversDefaultClass \Acquia\Console\ContentHub\Command\Backups\AcquiaCloudBackupCreate
 *
 * @group acquia-console-cloud
 *
 * @package Acquia\Console\ContentHub\Tests\Command\Backups
 */
class AcquiaCloudBackupCreateTest extends AcquiaCloudBackupTestBase {

  /**
   * Tests backup list.
   *
   * @param array $config_exists
   *   Configuration status.
   * @param array $backup_name
   *   Backup name.
   * @param array $local_data
   *   runLocallyWithMemoryOutput() mock return.
   * @param array $platform_data
   *   runWithMemoryOutput() mock return.
   * @param string $needle
   *   String to look for within display.
   * @param int $exit_code
   *   Expected return value of execute() method.
   *
   * @throws \ReflectionException
   *
   * @covers ::execute
   *
   * @dataProvider dataProvider
   */
  public function testBackupCreate(array $config_exists, array $backup_name, array $local_data, array $platform_data, string $needle, int $exit_code) {
    $environment_response = $this->getFixture('ace_environment_response.php')['111111-11111111-c36a-401a-9724-fd8072a607d7'];

    $arguments = [
      'create backup' => [
        'arguments' => [
          // First case.
          ['get', '/environments/111111-11111111-c36a-401a-9724-fd8072a607d7'],
          // Second case.
          ['get', '/environments/111111-11111111-c36a-401a-9724-fd8072a607d7'],
          // Third case.
          [
            'delete',
            '/environments/111111-11111111-c36a-401a-9724-fd8072a607d7/databases/chdemo1/backups/4676965',
          ],
        ],
        'returns' => [
          // First case.
          $environment_response,
          // Second case.
          $environment_response,
          // Third case.
          (object) ['message' => 'DB backup deleted'],
        ],
      ],
    ];

    $arr = [
      'config_exists' => $config_exists,
      'local_data' => $local_data,
      'platform_data' => $platform_data,
    ];

    /** @var \Symfony\Component\Console\Tester\CommandTester $command_tester */
    $tester = $this->getCmdTesterInstanceOf(AcquiaCloudBackupCreate::class, $arr, $arguments);
    $tester->setInputs($backup_name)->execute([]);
    $this->assertEquals($needle, $tester->getDisplay());
    $this->assertEquals($exit_code, $tester->getStatusCode());
  }

  /**
   * A data provider for ::testBackupList()
   *
   * @return array[]
   *   Array for data provider.
   */
  public function dataProvider(): array {
    return [
      [
        [FALSE],
        ['test'],
        [
          [
            'exit_code' => 0,
            'command_output' => '{"success":true,"data":[{"env_id":"111111-11111111-c36a-401a-9724-fd8072a607d7","database":"chdemo1","completed_at":"2020-12-16T13:45:53+00:00","backup_id":4676964}]}',
          ],
          ['exit_code' => 0, 'command_output' => ''],
          [
            'exit_code' => 0,
            'command_output' => '{"success":true,"data":[{"env_id":"111111-11111111-c36a-401a-9724-fd8072a607d7","database":"chdemo1","completed_at":"2020-12-16T13:45:53+00:00","backup_id":4676965}]}',
          ],
        ],
        [
          'exit_code' => 0,
          'command_output' => '{"success":true,"data":{"snapshot_id": "12312312312", "module_version": 2}}',
        ],
        'We are about to create a backup of all databases in this platform and a snapshot of the subscription.'
        . PHP_EOL . 'Please name this backup in order to restore it later (alphanumeric characters only)!'
        . PHP_EOL . 'Please enter a name:Starting the creation of database backups for all sites in the platform...'
        . PHP_EOL . 'Database backups are successfully created! Starting Content Hub service snapshot creation!'
        . PHP_EOL . 'Content Hub Service Snapshot is successfully created. Current Content Hub version is 2.x .' . PHP_EOL,
        0,
      ],
      [
        [TRUE, FALSE, FALSE],
        ['test', 'test test', 'test'],
        [
          [
            'exit_code' => 0,
            'command_output' => '{"success":true,"data":[{"env_id":"111111-11111111-c36a-401a-9724-fd8072a607d7","database":"chdemo1","completed_at":"2020-12-16T13:45:53+00:00","backup_id":4676964}]}',
          ],
          ['exit_code' => 0, 'command_output' => ''],
          [
            'exit_code' => 0,
            'command_output' => '{"success":true,"data":[{"env_id":"111111-11111111-c36a-401a-9724-fd8072a607d7","database":"chdemo1","completed_at":"2020-12-16T13:45:53+00:00","backup_id":4676964}]}',
          ],
        ],
        [
          'exit_code' => 0,
          'command_output' => '{"success":true,"data":{"snapshot_id": "12312312312", "module_version": 2}}',
        ],
        'We are about to create a backup of all databases in this platform and a snapshot of the subscription.'
        . PHP_EOL . 'Please name this backup in order to restore it later (alphanumeric characters only)!'
        . PHP_EOL . 'Please enter a name:Configuration with given name already exists!'
        . PHP_EOL . 'Please enter a name:Name cannot contain white spaces!'
        . PHP_EOL . 'Please enter a name:Starting the creation of database backups for all sites in the platform...'
        . PHP_EOL . '<warning>Cannot find the recently created backup.</warning>' . PHP_EOL,
        1,
      ],
      [
        [FALSE],
        ['test'],
        [
          [
            'exit_code' => 0,
            'command_output' => '{"success":true,"data":[{"env_id":"111111-11111111-c36a-401a-9724-fd8072a607d7","database":"chdemo1","completed_at":"2020-12-16T13:45:53+00:00","backup_id":4676964}]}',
          ],
          ['exit_code' => 0, 'command_output' => ''],
          [
            'exit_code' => 0,
            'command_output' => '{"success":true,"data":[{"env_id":"111111-11111111-c36a-401a-9724-fd8072a607d7","database":"chdemo1","completed_at":"2020-12-16T13:45:53+00:00","backup_id":4676965}]}',
          ],
        ],
        [
          'exit_code' => 0,
          'command_output' => '',
        ],
        'We are about to create a backup of all databases in this platform and a snapshot of the subscription.'
        . PHP_EOL . 'Please name this backup in order to restore it later (alphanumeric characters only)!'
        . PHP_EOL . 'Please enter a name:Starting the creation of database backups for all sites in the platform...'
        . PHP_EOL . 'Database backups are successfully created! Starting Content Hub service snapshot creation!' . PHP_EOL . ''
        . PHP_EOL . '<warning>Cannot create Content Hub service snapshot. Please check your Content Hub service credentials and try again.</warning>'
        . PHP_EOL . '<warning>The previously created database backups are being deleted because the service snapshot creation failed.</warning>' . PHP_EOL,
        2,
      ],
    ];
  }

  /**
   * ConfigStorage mock returns.
   *
   * @param array $arr
   *   Array containing mock configuration data.
   *
   * @return object
   *   Object containing ConfigStorage data.
   *
   * @throws \Exception
   */
  public function getConfigStorage(array $arr): object {
    $config_storage = $this->prophesize(ConfigStorage::class);
    $config_storage
      ->configExists(Argument::any(), Argument::any())
      ->shouldBeCalled()
      ->willReturn(...$arr['config_exists']);

    $config_storage
      ->save(Argument::any(), Argument::any(), Argument::any());

    return $config_storage->reveal();
  }

  /**
   * PlatformCommandExecutioner mock returns.
   *
   * @param array $arr
   *   Array containing mock command output.
   *
   * @return object
   *   Object containing PlatformCommandExecutioner instance.
   *
   * @throws \Exception
   */
  public function getPlatformCommandExecutioner(array $arr): object {
    /** @var \Acquia\Console\Helpers\PlatformCommandExecutioner $platform_command */
    $platform_command = $this->prophesize(PlatformCommandExecutioner::class);
    $platform_command
      ->runLocallyWithMemoryOutput(Argument::any(), Argument::any(), Argument::any())
      ->shouldBeCalled()
      ->willReturn(
        $this->runLocallyWithMemoryOutputMocks($arr['local_data'][0]),
        $this->runLocallyWithMemoryOutputMocks($arr['local_data'][1]),
        $this->runLocallyWithMemoryOutputMocks($arr['local_data'][2])
      );

    $platform_command
      ->runWithMemoryOutput(Argument::any(), Argument::any(), Argument::any())
      ->willReturn(
        $this->runWithMemoryOutputMocks($arr['platform_data'])
      );

    return $platform_command->reveal();
  }

  /**
   * Helper function to get the output depending on the command.
   *
   * @param array $output
   *   Array containing exit code and command output.
   *
   * @return object
   *   Object containing command mock output.
   */
  protected function runLocallyWithMemoryOutputMocks(array $output): object {
    return new class($output['exit_code'], $output['command_output']) {

      /**
       * Constructor.
       *
       * @param int $exit_code
       *   Exit code.
       * @param string $command_output
       *   Command output.
       */
      public function __construct(int $exit_code, string $command_output) {
        $this->exit_code = $exit_code;
        $this->command_output = $command_output;
      }

      /**
       * Mock of getReturnCode().
       */
      public function getReturnCode(): int {
        return $this->exit_code;
      }

      /**
       * Mock of __toString().
       */
      public function __toString(): string {
        return $this->command_output;
      }

    };
  }

  /**
   * Helper function to get the output depending on the command.
   *
   * @param array $output
   *   Array containing exit code and command output.
   *
   * @return object
   *   Object containing command mock output.
   */
  protected function runWithMemoryOutputMocks(array $output): object {
    return new class($output['exit_code'], $output['command_output']) {

      /**
       * Constructor.
       *
       * @param int $exit_code
       *   Exit code.
       * @param string $command_output
       *   Command output.
       */
      public function __construct(int $exit_code, string $command_output) {
        $this->exit_code = $exit_code;
        $this->command_output = $command_output;
      }

      /**
       * Mock of getReturnCode().
       */
      public function getReturnCode(): int {
        return $this->exit_code;
      }

      /**
       * Mock of __toString().
       */
      public function __toString(): string {
        return $this->command_output;
      }

    };
  }

}
