<?php

namespace Acquia\Console\ContentHub\Tests\Command\Backups;

use Acquia\Console\ContentHub\Command\Backups\AcsfBackupCreate;
use Acquia\Console\Helpers\PlatformCommandExecutioner;
use EclipseGc\CommonConsole\Config\ConfigStorage;
use Prophecy\Argument;

/**
 * Class AcsfBackupCreateTest.
 *
 * @coversDefaultClass \Acquia\Console\ContentHub\Command\Backups\AcsfBackupCreate
 *
 * @group acquia-console-acsf
 *
 * @package Acquia\Console\Acsf\Tests\Command\Backups
 */
class AcsfBackupCreateTest extends AcsfBackupTestBase {

  /**
   * Test coverage for Acsf Backup Creation operation.
   *
   * @param array $backup_config_name
   *   Mock value of backup config name.
   * @param array $config_exists
   *   Mock value to check whether backup config name already exists or not.
   * @param array $local_data
   *   Mock values for runLocallyWithMemoryOutput() method calls.
   * @param array $platform_data
   *   Mock values for runWithMemoryOutput() method call.
   * @param array $sites_array
   *   Mock value for listSites() method.
   * @param bool $config_save_method_call
   *   Mock value to check whether config save method is called or not.
   * @param int $exit_code
   *   Exit code of the command for assertion.
   * @param string $needle
   *   Needle for assertion.
   *
   * @throws \ReflectionException
   *
   * @dataProvider dataProvider
   */
  public function testAcsfBackupCreate(array $backup_config_name, array $config_exists, array $local_data, array $platform_data, array $sites_array, bool $config_save_method_call, int $exit_code, string $needle) {
    $cmd_tester = $this->getCmdTesterInstanceOf(AcsfBackupCreate::class, [
      'config_exists' => $config_exists,
      'local_data' => $local_data,
      'platform_data' => $platform_data,
      'sites_array' => $sites_array,
      'config_save_method_call' => $config_save_method_call
    ]);

    $cmd_tester->setInputs($backup_config_name)->execute([]);
    $this->assertEquals($needle, $cmd_tester->getDisplay());
    $this->assertEquals($exit_code, $cmd_tester->getStatusCode());
  }

  /**
   * {@inheritdoc}
   */
  protected function getPlatformCommandExecutionMocks(array $local_data = [], array $platform_data = []): object {
    $platform_command_executioner = $this->prophesize(PlatformCommandExecutioner::class);
    // Consecutive Calls for AcsfDatabaseBackupList, AcsfDatabaseBackupCreate and AcsfDatabaseBackupList.
    if ($local_data) {
      $platform_command_executioner
        ->runLocallyWithMemoryOutput(Argument::any(), Argument::any(), Argument::any())
        ->willReturn(
          $this->runOutputMocks($local_data[0]),
          $this->runOutputMocks($local_data[1]),
          $this->runOutputMocks($local_data[2])
        );
    }
    // Call for ContentHubSnapshotCreate.
    if ($platform_data) {
      $platform_command_executioner
        ->runWithMemoryOutput(Argument::any(), Argument::any(), Argument::any())
        ->willReturn($this->runOutputMocks($platform_data));
    }
    return $platform_command_executioner->reveal();
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigStorageMock(array $config_exists = [], bool $config_save_method_call = TRUE): object {
    $config_storage = $this->prophesize(ConfigStorage::class);
    // Call for configExists().
    if ($config_exists) {
      $config_storage
        ->configExists(Argument::any(), Argument::any())
        ->shouldBeCalled()
        ->willReturn(...$config_exists);
    }
    // Call for config save().
    if ($config_save_method_call) {
      $config_storage
        ->save(Argument::any(), Argument::any(), Argument::any())
        ->shouldBeCalled();
    }
    return $config_storage->reveal();
  }

  /**
   * Helper function to get the output depending on the command.
   *
   * @param array $output
   *   Array containing exit code and command output.
   *
   * @return object
   *   Command execution object mock.
   */
  private function runOutputMocks(array $output): object {
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
   * A data provider for ::testAcsfBackupCreate.
   *
   * @return array[]
   *   Data provider array for testAcsfBackupCreate.
   */
  public function dataProvider(): array {
    return [
      [
        // Database backup config input name.
        ['test_backup_name'],
        // Whether database config name already exists.
        [FALSE],
        // ACSF DB Creation output.
        [
          [
            'exit_code' => 0,
            'command_output' => '{"success":true,"data":{"351":[1291,1276,1261,1246,1231,1216,1201,1186,1171,1156],"356":[1296,1281,1266,1251,1236,1221,1206,1191,1176,1161]}}'
          ],
          ['exit_code' => 0, 'command_output' => ''],
          [
            'exit_code' => 0,
            'command_output' => '{"success":true,"data":{"351":[1306,1291,1276,1261,1246,1231,1216,1201,1186,1171],"356":[1311,1296,1281,1266,1251,1236,1221,1206,1191,1176]}}'
          ]
        ],
        // Snapshot creation output.
        [
          'exit_code' => 0,
          'command_output' => '{"success":true,"data":{"snapshot_id": "12312312312", "module_version": 2}}'
        ],
        // Sites array for listSites() method called on $acsfClient.
        [
          // Site 1.
          [
            'id' => 351,
            'db_name' => '32dsref',
            'site' => 'site1',
            'stack_id' => 1,
            'domain' => 'site1.acsitefactory.com',
            'groups' =>
              [
                0 => 326,
              ],
            'site_collection' => FALSE,
            'is_primary' => TRUE,
          ],
          // Site 2.
          [
            'id' => 356,
            'db_name' => 'd34dasd',
            'site' => 'site2',
            'stack_id' => 1,
            'domain' => 'site2.acsitefactory.com',
            'groups' =>
              [
                0 => 326,
              ],
            'site_collection' => FALSE,
            'is_primary' => TRUE,
          ]
        ],
        // Config Storage save() method called.
        TRUE,
        // Exit code.
        0,
        // Needle.
        'We are about to create a backup of all databases in this platform and a snapshot of the subscription.'
        . PHP_EOL . 'Please name this backup in order to restore it later (alphanumeric characters only)!'
        . PHP_EOL . 'Please enter a name:Starting the creation of database backups for all sites in the platform.'
        . PHP_EOL . 'Database backups are successfully created! Starting Content Hub service snapshot creation!'
        . PHP_EOL . 'Content Hub Service Snapshot is successfully created. Current Content Hub version is 2.x .'
        . PHP_EOL
      ],
      [
        // Database backup config input name.
        ['test backup name', '', 'test_backup_name', 'test_backup_name1'],
        // Whether database config name already exists.
        [TRUE, FALSE],
        // ACSF DB Creation output.
        [
          [
            'exit_code' => 0,
            'command_output' => '{"success":true,"data":{"351":[1291,1276,1261,1246,1231,1216,1201,1186,1171,1156],"356":[1296,1281,1266,1251,1236,1221,1206,1191,1176,1161]}}'
          ],
          ['exit_code' => 0, 'command_output' => ''],
          [
            'exit_code' => 0,
            'command_output' => '{"success":true,"data":{"351":[1306,1291,1276,1261,1246,1231,1216,1201,1186,1171],"356":[1311,1296,1281,1266,1251,1236,1221,1206,1191,1176]}}'
          ]
        ],
        // Snapshot creation output.
        [
          'exit_code' => 0,
          'command_output' => '{"success":true,"data":{"snapshot_id": "12312312312", "module_version": 2}}'
        ],
        // Sites array for listSites() method called on $acsfClient.
        [
          // Site 1.
          [
            'id' => 351,
            'db_name' => '32dsref',
            'site' => 'site1',
            'stack_id' => 1,
            'domain' => 'site1.acsitefactory.com',
            'groups' =>
              [
                0 => 326,
              ],
            'site_collection' => FALSE,
            'is_primary' => TRUE,
          ],
          // Site 2.
          [
            'id' => 356,
            'db_name' => 'd34dasd',
            'site' => 'site2',
            'stack_id' => 1,
            'domain' => 'site2.acsitefactory.com',
            'groups' =>
              [
                0 => 326,
              ],
            'site_collection' => FALSE,
            'is_primary' => TRUE,
          ]
        ],
        // Config Storage save() method called.
        TRUE,
        // Exit code.
        0,
        // Needle.
        'We are about to create a backup of all databases in this platform and a snapshot of the subscription.'
        . PHP_EOL . 'Please name this backup in order to restore it later (alphanumeric characters only)!'
        . PHP_EOL . 'Please enter a name:Name cannot contain white spaces!'
        . PHP_EOL . 'Please enter a name:Name cannot be empty!'
        . PHP_EOL . 'Please enter a name:Configuration with given name already exists!'
        . PHP_EOL . 'Please enter a name:Starting the creation of database backups for all sites in the platform.'
        . PHP_EOL . 'Database backups are successfully created! Starting Content Hub service snapshot creation!'
        . PHP_EOL . 'Content Hub Service Snapshot is successfully created. Current Content Hub version is 2.x .'
        . PHP_EOL
      ],
      [
        // Database backup config input name.
        ['test_backup_name'],
        // Whether database config name already exists.
        [FALSE],
        // ACSF DB Creation output.
        [
          [
            'exit_code' => 0,
            'command_output' => '{"success":true,"data":{"351":[1291,1276,1261,1246,1231,1216,1201,1186,1171,1156],"356":[1296,1281,1266,1251,1236,1221,1206,1191,1176,1161]}}'
          ],
          ['exit_code' => 0, 'command_output' => ''],
          [
            'exit_code' => 0,
            'command_output' => '{"success":true,"data":{"351":[1306,1291,1276,1261,1246,1231,1216,1201,1186,1171],"356":[1311,1296,1281,1266,1251,1236,1221,1206,1191,1176]}}'
          ]
        ],
        // Snapshot creation output.
        ['exit_code' => 1, 'command_output' => ''],
        // Sites array for listSites() method called on $acsfClient.
        [
          // Site 1.
          [
            'id' => 351,
            'db_name' => '32dsref',
            'site' => 'site1',
            'stack_id' => 1,
            'domain' => 'site1.acsitefactory.com',
            'groups' =>
              [
                0 => 326,
              ],
            'site_collection' => FALSE,
            'is_primary' => TRUE,
          ],
          // Site 2.
          [
            'id' => 356,
            'db_name' => 'd34dasd',
            'site' => 'site2',
            'stack_id' => 1,
            'domain' => 'site2.acsitefactory.com',
            'groups' =>
              [
                0 => 326,
              ],
            'site_collection' => FALSE,
            'is_primary' => TRUE,
          ]
        ],
        // Config Storage save() method called.
        FALSE,
        // Exit code.
        2,
        // Needle.
        'We are about to create a backup of all databases in this platform and a snapshot of the subscription.'
        . PHP_EOL . 'Please name this backup in order to restore it later (alphanumeric characters only)!'
        . PHP_EOL . 'Please enter a name:Starting the creation of database backups for all sites in the platform.'
        . PHP_EOL . 'Database backups are successfully created! Starting Content Hub service snapshot creation!'
        . PHP_EOL . 'Cannot create Content Hub service snapshot. Exit code: 1'
        . PHP_EOL . '<warning>Cannot create Content Hub service snapshot. Please check your Content Hub service credentials and try again.</warning>'
        . PHP_EOL . '<warning>The previously created database backups are being deleted because the service snapshot creation failed.</warning>'
        . PHP_EOL
      ],
      [
        // Database backup config input name.
        ['test_backup_name'],
        // Whether database config name already exists.
        [FALSE],
        // ACSF DB Creation output.
        [
          [
            'exit_code' => 0,
            'command_output' => '{"success":true,"data":{"351":[1291,1276,1261,1246,1231,1216,1201,1186,1171,1156],"356":[1296,1281,1266,1251,1236,1221,1206,1191,1176,1161]}}'
          ],
          ['exit_code' => 0, 'command_output' => ''],
          [
            'exit_code' => 0,
            'command_output' => '{"success":true,"data":{"351":[1306,1291,1276,1261,1246,1231,1216,1201,1186,1171],"356":[1311,1296,1281,1266,1251,1236,1221,1206,1191,1176]}}'
          ]
        ],
        // Snapshot creation output.
        [
          'exit_code' => 0,
          'command_output' => '{"success":false,"error":{"message":"Couldn\'t create snapshot."}}'
        ],
        // Sites array for listSites() method called on $acsfClient.
        [
          // Site 1.
          [
            'id' => 351,
            'db_name' => '32dsref',
            'site' => 'site1',
            'stack_id' => 1,
            'domain' => 'site1.acsitefactory.com',
            'groups' =>
              [
                0 => 326,
              ],
            'site_collection' => FALSE,
            'is_primary' => TRUE,
          ],
          // Site 2.
          [
            'id' => 356,
            'db_name' => 'd34dasd',
            'site' => 'site2',
            'stack_id' => 1,
            'domain' => 'site2.acsitefactory.com',
            'groups' =>
              [
                0 => 326,
              ],
            'site_collection' => FALSE,
            'is_primary' => TRUE,
          ]
        ],
        // Config Storage save() method called.
        FALSE,
        // Exit code.
        2,
        // Needle.
        'We are about to create a backup of all databases in this platform and a snapshot of the subscription.'
        . PHP_EOL . 'Please name this backup in order to restore it later (alphanumeric characters only)!'
        . PHP_EOL . 'Please enter a name:Starting the creation of database backups for all sites in the platform.'
        . PHP_EOL . 'Database backups are successfully created! Starting Content Hub service snapshot creation!'
        . PHP_EOL . 'Couldn\'t create snapshot.'
        . PHP_EOL . '<warning>Cannot create Content Hub service snapshot. Please check your Content Hub service credentials and try again.</warning>'
        . PHP_EOL . '<warning>The previously created database backups are being deleted because the service snapshot creation failed.</warning>'
        . PHP_EOL
      ],
      [
        // Database backup config input name.
        ['test_backup_name1'],
        // Whether database config name already exists.
        [FALSE],
        // ACSF DB Creation output.
        [
          [
            'exit_code' => 0,
            'command_output' => '{"success":true,"data":{"351":[1291,1276,1261,1246,1231,1216,1201,1186,1171,1156],"356":[1296,1281,1266,1251,1236,1221,1206,1191,1176,1161]}}'
          ],
          ['exit_code' => 0, 'command_output' => ''],
          [
            'exit_code' => 0,
            'command_output' => '{"success":true,"data":{"351":[1291,1276,1261,1246,1231,1216,1201,1186,1171,1156],"356":[1296,1281,1266,1251,1236,1221,1206,1191,1176,1161]}}'
          ]
        ],
        // Snapshot creation output.
        [],
        // Sites array for listSites() method called on $acsfClient.
        [],
        // Config Storage save() method called.
        FALSE,
        // Exit code.
        1,
        // Needle.
        'We are about to create a backup of all databases in this platform and a snapshot of the subscription.'
        . PHP_EOL . 'Please name this backup in order to restore it later (alphanumeric characters only)!'
        . PHP_EOL . 'Please enter a name:Starting the creation of database backups for all sites in the platform.'
        . PHP_EOL . '<warning>Cannot find the recently created backup.</warning>'
        . PHP_EOL
      ]
    ];
  }

}
