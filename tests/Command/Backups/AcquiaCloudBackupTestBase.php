<?php

namespace Acquia\Console\ContentHub\Tests\Command\Backups;

use Acquia\Console\Cloud\Platform\AcquiaCloudPlatform;
use Acquia\Console\Cloud\Tests\Command\CommandTestHelperTrait;
use Acquia\Console\Cloud\Tests\Command\PlatformCommandTestHelperTrait;
use Acquia\Console\Cloud\Tests\TestFixtureHelperTrait;
use EclipseGc\CommonConsole\PlatformInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Class AcquiaCloudDatabaseBackupTestBase.
 *
 * @package Acquia\ContentHub\Cloud\Tests\Command\DatabaseBackup
 */
abstract class AcquiaCloudBackupTestBase extends TestCase {

  use TestFixtureHelperTrait;
  use PlatformCommandTestHelperTrait;
  use CommandTestHelperTrait;

  /**
   * Returns command tester instance.
   *
   * @param string $cmd
   *   The command to instantiate.
   * @param $arguments
   *   Environment mock data.
   * @param array $arr
   *   Input data required for the command.
   *
   * @return \Symfony\Component\Console\Tester\CommandTester
   *   The command tester.
   *
   * @throws \ReflectionException
   */
  public function getCmdTesterInstanceOf(string $cmd, array $arguments = [], array $arr) {
    $reflection = new \ReflectionClass($cmd);
    /** @var \Acquia\Console\Cloud\Command\DatabaseBackup\AcquiaCloudDatabaseBackupBase $cmd */
    $cmd = $reflection->newInstanceArgs([$this->getDispatcher(), $this->getConfigStorage($arr), $this->getPlatformCommandExecutioner($arr)]);
    $platform = $this->getPlatform($arguments);
    $cmd->addPlatform('test', $platform);
    return $this->getCommandTester($cmd);
  }

  /**
   * {@inheritdoc}
   */
  public function getPlatform(array $args = []): PlatformInterface {
    $client_mock_callback = function (ObjectProphecy $client) use ($args) {
      foreach ($args as $arg) {
        if (empty($arg['returns'])) {
          continue;
        }
        if (!empty($arg['arguments'])) {
          $client->request(Argument::any(), Argument::any())
            ->willReturn(...$arg['returns']);
        }
        else {
          $client->request(Argument::any(), Argument::any())
            ->willReturn(...$arg['returns']);
        }
      }
    };

    return $this->getAcquiaCloudPlatform(
      [
        AcquiaCloudPlatform::ACE_API_KEY => 'test_key',
        AcquiaCloudPlatform::ACE_API_SECRET => 'test_secret',
        AcquiaCloudPlatform::ACE_APPLICATION_ID => ['test1'],
        AcquiaCloudPlatform::ACE_ENVIRONMENT_DETAILS => [
          '111111-11111111-c36a-401a-9724-fd8072a607d7' => '111111-11111111-c36a-401a-9724-fd8072a607d7'
        ],
        PlatformInterface::PLATFORM_ALIAS_KEY => 'test'
      ],
      $client_mock_callback
    );
  }

  /**
   * ConfigStorage mock return.
   *
   * @param array $arr
   *   Array containing mock configuration data.
   *
   * @return object
   *   Object containing ConfigStorage data.
   */
  abstract public function getConfigStorage(array $arr): object;

  /**
   * @param array $arr
   *   Array containing mock configuration data.
   *
   * @return object
   *   Object containing PlatformCommandExecutioner instance.
   */
  abstract public function getPlatformCommandExecutioner(array $arr): object;

}
