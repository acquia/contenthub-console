<?php

namespace Acquia\Console\ContentHub\Tests\Command\Backups;

use Acquia\Console\Cloud\Command\Backups\AcquiaCloudBackupRestore;
use Acquia\Console\Helpers\PlatformCommandExecutioner;
use Consolidation\Config\Config;
use EclipseGc\CommonConsole\Config\ConfigStorage;
use Exception;
use Prophecy\Argument;

/**
 * Class AcquiaCloudBackupRestoreTest
 *
 * @coversDefaultClass \Acquia\Console\Cloud\Command\Backups\AcquiaCloudBackupRestore
 *
 * @group acquia-console-cloud
 *
 * @package Acquia\Console\ContentHub\Tests\Command\Backups
 */
class AcquiaCloudBackupRestoreTest extends AcquiaCloudBackupTestBase {

  /**
   * Tests backup list.
   *
   * @covers ::execute
   *
   * @param array $load_all_value
   *   Configuration status.
   * @param $load_value
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
   * @dataProvider dataProvider
   */
  public function testBackupRestore(array $load_all_value, $load_value, array $local_data, array $platform_data, string $needle, int $exit_code) {
    $environment_response = $this->getFixture('ace_environment_response.php')['111111-11111111-c36a-401a-9724-fd8072a607d7'];
    $arguments = [
      'create backup' => [
        'arguments' => [
          // First case.
          ['get','/environments/111111-11111111-c36a-401a-9724-fd8072a607d7'],
          // Second case.
          ['get','/environments/111111-11111111-c36a-401a-9724-fd8072a607d7']
        ],
        'returns' => [
          // First case.
          $environment_response,
          // Second case.
          $environment_response
        ],
      ],
    ];

    $arr = [
      'load_all_value' => $load_all_value,
      'load_value' => $load_value,
      'local_data' => $local_data,
      'platform_data' => $platform_data,
    ];

    $tester = $this->getCmdTesterInstanceOf(AcquiaCloudBackupRestore::class, $arguments, $arr);
    $tester->setInputs(['Test'])->execute([]);
    $output = $tester->getDisplay();
    $this->assertEquals($needle, $output);
    $this->assertEquals($exit_code, $tester->getStatusCode());

  }

  /**
   * A data provider for ::testBackupList()
   *
   * @return array[]
   */
  public function dataProvider() {
    return [
      [
        // loadAll mock data.
        [
          new Config([
            'name' => 'Test',
          ]),
        ],
        // load mock data.
        new Config([
          'backups' => [
            'database' => [
              '160342340' => [
                'environment_id' => '91837-cfa1cffd-739e-4e4e-90fc-33e7d9bc046b',
                'database_name' => 'db',
                'created_at' => '2020-12-22T11:57:22+00:00',
              ]
            ],
            'ach_snapshot' => '2020-12-22_1158_17_454583857',
          ]
        ]),
        // Local mock data.
        ['exit_code' => 0, 'command_output' => ''],
        // Platform mock data.
        ['exit_code' => 0, 'command_output' => ''],
        '<warning>We are about to restore backups of all databases in this platform and a snapshot of the subscription.</warning>'
        . PHP_EOL . 'Please pick a configuration to restore:'
        . PHP_EOL . '  [0] Test'
        . PHP_EOL . ' > T[K7est8e[K7st8s[K7t8t[K78'
        . PHP_EOL . 'Starting Acquia Content Hub service restoration.'
        . PHP_EOL . 'Acquia Content Hub service restoration is completed successfully.'
        . PHP_EOL . 'Database backup restoration started. It can take several minutes to complete.'
        . PHP_EOL . 'Acquia Content Hub service and site\'s database backups have been restored successfully!' . PHP_EOL,
        0
      ],
      [
        // loadAll mock data.
        [
          new Config([
            'name' => 'Test',
          ]),
        ],
        // load mock data.
        new Config([
          'backups' => [
            'database' => [
              '160342340' => [
                'environment_id' => '91837-cfa1cffd-739e-4e4e-90fc-33e7d9bc046b',
                'database_name' => 'db',
                'created_at' => '2020-12-22T11:57:22+00:00',
              ]
            ],
            'ach_snapshot' => '2020-12-22_1158_17_454583857',
          ]
        ]),
        // Local mock data.
        ['exit_code' => 1, 'command_output' => ''],
        // Platform mock data.
        ['exit_code' => 0, 'command_output' => ''],
        '<warning>We are about to restore backups of all databases in this platform and a snapshot of the subscription.</warning>'
        . PHP_EOL . 'Please pick a configuration to restore:'
        . PHP_EOL . '  [0] Test'
        . PHP_EOL . ' > T[K7est8e[K7st8s[K7t8t[K78'
        . PHP_EOL . 'Starting Acquia Content Hub service restoration.'
        . PHP_EOL . 'Acquia Content Hub service restoration is completed successfully.'
        . PHP_EOL . 'Database backup restoration started. It can take several minutes to complete.'
        . PHP_EOL . 'Backup restoration command failed with exit code: 1.' . PHP_EOL,
        1
      ],
      [
        // loadAll mock data.
        [
          new Config([
            'name' => 'Test',
          ]),
        ],
        // load mock data.
        new Config([
          'backups' => [
            'database' => [
              '160342340' => [
                'environment_id' => '91837-cfa1cffd-739e-4e4e-90fc-33e7d9bc046b',
                'database_name' => 'db',
                'created_at' => '2020-12-22T11:57:22+00:00',
              ]
            ],
            'ach_snapshot' => '2020-12-22_1158_17_454583857',
          ]
        ]),
        // Local mock data.
        ['exit_code' => 0, 'command_output' => ''],
        // Platform mock data.
        ['exit_code' => 1, 'command_output' => ''],
        '<warning>We are about to restore backups of all databases in this platform and a snapshot of the subscription.</warning>'
        . PHP_EOL . 'Please pick a configuration to restore:'
        . PHP_EOL . '  [0] Test'
        . PHP_EOL . ' > T[K7est8e[K7st8s[K7t8t[K78'
        . PHP_EOL . 'Starting Acquia Content Hub service restoration.'
        . PHP_EOL . 'Acquia Content Hub service restoration failed with exit code: 1.' . PHP_EOL,
        1
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
   * @throws Exception
   */
  public function getConfigStorage(array $arr): object {
    $config_storage = $this->prophesize(ConfigStorage::class);
    $config_storage->loadAll(Argument::any())->shouldBeCalled()->willReturn($arr['load_all_value']);
    $config_storage->load(Argument::any(), Argument::any())->shouldBeCalled()->willReturn($arr['load_value']);

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
   * @throws Exception
   */
  public function getPlatformCommandExecutioner(array $arr): object {
    $platform_command = $this->prophesize(PlatformCommandExecutioner::class);
    $platform_command
      ->runLocallyWithMemoryOutput(Argument::any(), Argument::any(), Argument::any())
      ->willReturn(
        $this->runLocallyWithMemoryOutputMocks($arr['local_data'])
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
      public function __construct(int $exit_code, string $command_output) {
        $this->exit_code = $exit_code;
        $this->command_output = $command_output;
      }

      public function getReturnCode() {return $this->exit_code;}

      public function __toString() {return $this->command_output;}
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
      public function __construct(int $exit_code, string $command_output) {
        $this->exit_code = $exit_code;
        $this->command_output = $command_output;
      }

      public function getReturnCode() {return $this->exit_code;}

      public function __toString() {return $this->command_output;}
    };
  }

}
