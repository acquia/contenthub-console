<?php

namespace Acquia\Console\ContentHub\Tests\Command\Backups;

use Acquia\Console\Acsf\Client\AcsfClient;
use Acquia\Console\Acsf\Client\AcsfClientFactory;
use Acquia\Console\Acsf\Platform\ACSFPlatform;
use Acquia\Console\Cloud\Client\AcquiaCloudClientFactory;
use Acquia\Console\Cloud\Platform\AcquiaCloudPlatform;
use Acquia\Console\Cloud\Tests\Command\CommandTestHelperTrait;
use Acquia\Console\Cloud\Tests\Command\PlatformCommandTestHelperTrait;
use AcquiaCloudApi\Connector\Client;
use Symfony\Component\EventDispatcher\EventDispatcher;
use EclipseGc\CommonConsole\Platform\PlatformStorage;
use EclipseGc\CommonConsole\PlatformInterface;
use EclipseGc\CommonConsole\ProcessRunner;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * Class AcsfBackupTestBase.
 *
 * @package Acquia\Console\Acsf\Tests\Command\Backups
 */
abstract class AcsfBackupTestBase extends TestCase {

  use CommandTestHelperTrait;
  use PlatformCommandTestHelperTrait;

  /**
   * Returns a command tester instance.
   *
   * @param string $cmd
   *   The command to instantiate.
   * @param array $input_data
   *   Input data required for the command.
   *
   * @return \Symfony\Component\Console\Tester\CommandTester
   *   The command tester.
   *
   * @throws \ReflectionException
   */
  protected function getCmdTesterInstanceOf(string $cmd, array $input_data) {
    $reflection = new \ReflectionClass($cmd);
    /** @var \Acquia\Console\Cloud\Command\AcquiaCloudCommandBase $cmd */
    $cmd = $reflection->newInstanceArgs([
      $this->getDispatcher(),
      $this->getConfigStorageMock($input_data['config_exists'], $input_data['config_save_method_call']),
      $this->getPlatformCommandExecutionMocks($input_data['local_data'], $input_data['platform_data'])
    ]);
    $platform = $this->getPlatform(array_key_exists('sites_array', $input_data) ? $input_data['sites_array'] : []);
    $cmd->addPlatform('test', $platform);
    return $this->getCommandTester($cmd);
  }

  /**
   * Helper function to get mock ACSF Platform.
   *
   * @param array $sites_array
   *   Array of sites for given ACSF Platform.
   *
   * @return \EclipseGc\CommonConsole\PlatformInterface
   *   Mock platform object.
   */
  public function getPlatform(array $sites_array = []): PlatformInterface {
    return $this->getAcsfPlatform(
      [
        ACSFPlatform::SITEFACTORY_USER => 'user_name',
        ACSFPlatform::SITEFACTORY_TOKEN => 'secret_token',
        ACSFPlatform::SITEFACTORY_URL => 'https://example.com',
        // Need to set Platform alias because getAlias() method is being called on platform.
        PlatformInterface::PLATFORM_ALIAS_KEY => 'test',
        // ACE creds are needed because AcsfBackupCreate extends AcquiaCloudBackupCreate
        // which in turn extends AcquiaCloudCommandBase which asks for AceClient instead of AcsfClient.
        AcquiaCloudPlatform::ACE_API_KEY => 'test_key',
        AcquiaCloudPlatform::ACE_API_SECRET => 'test_secret',
        AcquiaCloudPlatform::ACE_APPLICATION_ID => ['test1'],
        // Setting up the http protocol for the sites.
        AcquiaCloudPlatform::ACE_SITE_HTTP_PROTOCOL => [
          '123' => 'https://',
          '456' => 'https://'
        ],
        // Sites array for listSites() method.
        'sites_array' => $sites_array
      ]);
  }

  /**
   * Helper function to provide ACSFPlatform object.
   * Overridden from method written in PlatformCommandTestHelperTrait due to some differences.
   *
   * @param array $platform_config
   *   Platform config array.
   *
   * @return \Acquia\Console\Acsf\Platform\ACSFPlatform
   *   Mock ACSFPlatform object.
   */
  protected function getAcsfPlatform(array $platform_config): ACSFPlatform {
    $ace_client = $this->prophesize(Client::class);

    $ace_factory = $this->prophesize(AcquiaCloudClientFactory::class);
    $ace_factory
      ->fromCredentials(Argument::any(), Argument::any(), Argument::any())
      ->shouldBeCalled()
      ->willReturn($ace_client);

    $acsf_client = $this->prophesize(AcsfClient::class);
    $acsf_client
      ->deleteAcsfSiteBackup(Argument::any(), Argument::any())
      ->willReturn([]);

    $sites_array = $platform_config['sites_array'] ?? [];
    unset($platform_config['sites_array']);
    if ($sites_array) {
      $acsf_client
        ->listSites()
        ->willReturn($sites_array);
    }

    $acsf_factory = $this->prophesize(AcsfClientFactory::class);
    $acsf_factory
      ->fromCredentials(Argument::any(), Argument::any(), Argument::any())
      ->willReturn($acsf_client->reveal());

    $platform_storage = $this->prophesize(PlatformStorage::class);

    $process_runner = $this->prophesize(ProcessRunner::class);
    $dispatcher = $this->prophesize(EventDispatcher::class);

    return new ACSFPlatform(
      $this->parseConfigArray($platform_config),
      $process_runner->reveal(),
      $platform_storage->reveal(),
      $ace_factory->reveal(),
      $acsf_factory->reveal(),
      $dispatcher->reveal()
    );
  }

  /**
   * Helper function to get the config storage mock.
   *
   * @param array $config_exists
   *   Mock return value for checking whether config for backup name already exists.
   * @param bool $config_save_method_call
   *   Mock return value whether config save() method will be called.
   *
   * @return object
   *   Mock object of config storage.
   */
  abstract protected function getConfigStorageMock(array $config_exists = [], bool $config_save_method_call = TRUE): object;

  /**
   * Helper function to get the PlatformCommandExecutioner service mock.
   *
   * @param array $local_data
   *   Local Data to mock passed from data provider.
   * @param array $platform_data
   *   Platform data to mock passed from data provider.
   *
   * @return object
   *   Mock object of the command running.
   */
  abstract protected function getPlatformCommandExecutionMocks(array $local_data = [], array $platform_data = []): object;

}
