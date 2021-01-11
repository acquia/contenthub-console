<?php

namespace Acquia\Console\ContentHub\Tests\Command\Backups;

use Acquia\Console\ContentHub\Command\Backups\BackupList;
use Consolidation\Config\Config;
use EclipseGc\CommonConsole\Config\ConfigStorage;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class BackupListTest
 *
 * @coversDefaultClass \Acquia\Console\ContentHub\Command\Backups\BackupList
 *
 * @group acquia-console-cloud
 *
 * @package Acquia\Console\ContentHub\Tests\Command\Backups
 */
class BackupListTest extends TestCase {

  /**
   * Tests backup list.
   *
   * @covers ::execute
   *
   * @param array $load_all_value
   *
   * @param string $expected
   *
   * @dataProvider dataProvider
   */
  public function testBackupList(array $load_all_value, string $expected) {
    $config_storage = $this->prophesize(ConfigStorage::class);
    $config_storage->loadAll(Argument::any())->shouldBeCalled()->willReturn($load_all_value);

    $cmd = new BackupList($config_storage->reveal());
    $command_tester = new CommandTester($cmd);
    $command_tester->execute([]);
    $this->assertEquals($expected, $command_tester->getDisplay());

  }

  /**
   * A data provider for ::testBackupList()
   *
   * @return array[]
   */
  public function dataProvider() {
    return [
      [
        [],
        "<warning>No configuration found.</warning>\n"
      ],
      [
        [
          new Config([
            'name' => 'Test',
            'platform' => [
              'name' => 'Platform alias',
              'type' => 'Acquia Cloud',
              'backupCreated' => 1606302941,
            ],
            'backups' => [
              'database' => [
                1,
                2,
              ]
            ]
          ]),
        ],
        '+-------------+----------------+---------------+----------------+------------+---------------------+
| Backup name | Platform alias | Platform type | Module version | Site count | Created             |
+-------------+----------------+---------------+----------------+------------+---------------------+
| Test        | Platform alias | Acquia Cloud  | 8.x-2.x        | 2          | 2020-11-25 11:15:41 |
+-------------+----------------+---------------+----------------+------------+---------------------+
'
      ],
      [
        [
          new Config([
            'name' => 'Test',
            'platform' => [
              'name' => 'Platform alias',
              'type' => 'Acquia Cloud',
              'backupCreated' => 1606302941,
            ],
            'backups' => [
              'database' => [
                1,
                2,
              ]
            ]
          ]),
          new Config([
            'name' => 'Test2',
            'platform' => [
              'name' => 'Platform alias2',
              'type' => 'Acquia Cloud',
              'backupCreated' => 1606303941,
            ],
            'backups' => [
              'database' => [
                1,
                2,
                3,
                4,
              ]
            ]
          ]),
        ],
        '+-------------+-----------------+---------------+----------------+------------+---------------------+
| Backup name | Platform alias  | Platform type | Module version | Site count | Created             |
+-------------+-----------------+---------------+----------------+------------+---------------------+
| Test        | Platform alias  | Acquia Cloud  | 8.x-2.x        | 2          | 2020-11-25 11:15:41 |
| Test2       | Platform alias2 | Acquia Cloud  | 8.x-2.x        | 4          | 2020-11-25 11:32:21 |
+-------------+-----------------+---------------+----------------+------------+---------------------+
'
      ],
    ];
  }

}
