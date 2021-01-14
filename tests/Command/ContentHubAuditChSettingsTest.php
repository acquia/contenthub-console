<?php

namespace Acquia\Console\ContentHub\Tests\Command;

use Acquia\Console\ContentHub\Command\ContentHubAuditChSettings;
use Acquia\Console\ContentHub\Tests\ContentHubCommandTestBase;
use Acquia\ContentHubClient\Settings;
use Prophecy\Argument;

/**
 * Class ContentHubAuditChSettingsTest.
 *
 * @coversDefaultClass \Acquia\Console\ContentHub\Command\ContentHubAuditChSettings
 *
 * @group acquia-console-contenthub
 *
 * @package Acquia\Console\ContentHub\Tests\Command
 */
class ContentHubAuditChSettingsTest extends ContentHubCommandTestBase {

  /**
   * Test Content Hub Audit CH Settings.
   *
   * @covers ::execute
   *
   * @param int $version
   *   The Content Hub module version.
   * @param array|object $overridden_conf
   *   Overridden configuration.
   * @param array|object $conf
   *   Raw configuration.
   * @param string $needle
   *   String to look for within display.
   * @param int $exit_code
   *   Expected return value of execute() method.
   *
   * @dataProvider dataProvider
   */
  public function testContentHubAuditChSettings(int $version, $overridden_conf, $conf, string $needle, int $exit_code) {
    $this
      ->drupalServiceFactory
      ->getDrupalService(Argument::any())
      ->shouldBeCalled()
      ->willReturn($this->getDrupalServiceMocks($overridden_conf, $conf));

    $this
      ->drupalServiceFactory
      ->getModuleVersion()
      ->shouldBeCalled()
      ->willReturn($version);

    $command = new ContentHubAuditChSettings();
    $command->setDrupalServiceFactory($this->drupalServiceFactory->reveal());
    $command->setAchClientService($this->contentHubService->reveal());

    /** @var \Symfony\Component\Console\Tester\CommandTester $command_tester */
    $command_tester = $this->doRunCommand($command, [], ['alias' => 'test']);
    $this->assertStringContainsString($needle, $command_tester->getDisplay());
    $this->assertEquals($exit_code, $command_tester->getStatusCode());
  }

  /**
   * Returns mock instance for getDrupalService().
   *
   * @param array|Settings $overridden_conf
   *   Overridden configuration.
   * @param array|Settings $conf
   *   Raw configuration.
   *
   * @return mixed
   *   Mock of getDrupalService function return.
   */
  public function getDrupalServiceMocks($overridden_conf, $conf): object {
    return new class ($overridden_conf, $conf) {

      /**
       *
       */
      public function __construct($overridden_conf, $conf) {
        $this->overridden_conf = $overridden_conf;
        $this->conf = $conf;
      }

      /**
       *
       */
      public function getOriginal() {
        return $this->overridden_conf;
      }

      /**
       *
       */
      public function getSettings() {
        return $this->overridden_conf;
      }

      /**
       *
       */
      public function getRawData() {
        return is_object($this->conf) ? $this->normalize($this->conf) : $this->conf;
      }

      /**
       *
       */
      public function get(): object {
        return $this;
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
      protected function normalize($settings):array {
        return [
          'hostname' => $settings->getUrl(),
          'api_key' => $settings->getApiKey(),
          'secret_key' => $settings->getSecretKey(),
          'origin' => $settings->getUuid(),
          'client_name' => $settings->getName(),
          'shared_secret' => $settings->getSharedSecret(),
          'webhook' => $settings->toArray()['webhook'],
        ];
      }

    };
  }

  /**
   * Sets Default values for Content Hub Admin Settings.
   *
   * @param array $overrides
   *   Override initial default values.
   * @param bool $settings
   *   TRUE if we want to return a Settings object, FALSE for array return.
   *
   * @return array|Settings
   *   Array or Settings object with configuration values.
   */
  public function getChConfigurationSettings($overrides = [], $settings = FALSE) {
    $config = [
      'hostname' => 'http://test.url',
      'api_key' => 'test_api',
      'secret_key' => 'test_secret',
      'origin' => '0000-00000000-0000-0000-0000-000000000000',
      'client_name' => 'test_clientname',
      'shared_secret' => 'test_shared_secret',
      'webhook' => [
        'uuid' => '11111111-1111-1111-1111-111111111111',
        'url' => 'http://test.url/acquia-contenthub/webhook',
        'settings_url' => 'http://test.url',
      ],
    ];
    $config_overridden = array_merge($config, $overrides);
    if ($settings) {
      return new Settings(
        $config_overridden['client_name'],
        $config_overridden['origin'],
        $config_overridden['api_key'],
        $config_overridden['secret_key'],
        $config_overridden['hostname'],
        $config_overridden['shared_secret'],
        $config_overridden['webhook']
      );
    }
    return $config_overridden;
  }

  /**
   * A data provider for ::ContentHubAuditChSettingsTest()
   *
   * @return array[]
   */
  public function dataProvider() {
    $ch_config1 = $this->getChConfigurationSettings();
    $ch_config2 = $this->getChConfigurationSettings([], TRUE);
    return [
      [
        1,
        [],
        $ch_config1,
        "Running Content Hub config audit...\nContent Hub configuration data should not be empty. Terminating...\n",
        2,
      ],
      [
        1,
        $ch_config1,
        [],
        "<warning>Content Hub configuration stored in database is empty. Please make sure it is intentional.</warning>
Configuration does not match the one stored in the database.
+----------------------+-------------------+-------------------------------------------+
| Config Key           | Value in Database | Overwritten Value                         |
+----------------------+-------------------+-------------------------------------------+
| hostname             |                   | http://test.url                           |
| api_key              |                   | test_api                                  |
| secret_key           |                   | test_secret                               |
| origin               |                   | 0000-00000000-0000-0000-0000-000000000000 |
| client_name          |                   | test_clientname                           |
| shared_secret        |                   | test_shared_secret                        |
| webhook:uuid         |                   | 11111111-1111-1111-1111-111111111111      |
| webhook:url          |                   | http://test.url/acquia-contenthub/webhook |
| webhook:settings_url |                   | http://test.url                           |
+----------------------+-------------------+-------------------------------------------+
<warning>Run `--fix` to synchronize Content Hub settings.",
        1,
      ],
      [
        1,
        $this->getChConfigurationSettings(['client_name' => 'test_clientname_new', 'origin' => '1111-00000000-0000-0000-0000-000000000000']),
        $ch_config1,
        "Configuration does not match the one stored in the database.
+-------------+-------------------------------------------+-------------------------------------------+
| Config Key  | Value in Database                         | Overwritten Value                         |
+-------------+-------------------------------------------+-------------------------------------------+
| origin      | 0000-00000000-0000-0000-0000-000000000000 | 1111-00000000-0000-0000-0000-000000000000 |
| client_name | test_clientname                           | test_clientname_new                       |
+-------------+-------------------------------------------+-------------------------------------------+
<warning>Run `--fix` to synchronize Content Hub settings.",
        1,
      ],
      [
        1,
        $ch_config1,
        $ch_config1,
        'Content Hub configuration is in order. You may proceed.',
        0,
      ],
      [
        2,
        $ch_config2,
        [],
      // New Settings(NULL, NULL, NULL, NULL, NULL),.
        "<warning>Content Hub configuration stored in database is empty. Please make sure it is intentional.</warning>
Configuration does not match the one stored in the database.
+----------------------+-------------------+-------------------------------------------+
| Config Key           | Value in Database | Overwritten Value                         |
+----------------------+-------------------+-------------------------------------------+
| hostname             |                   | http://test.url                           |
| api_key              |                   | test_api                                  |
| secret_key           |                   | test_secret                               |
| origin               |                   | 0000-00000000-0000-0000-0000-000000000000 |
| client_name          |                   | test_clientname                           |
| shared_secret        |                   | test_shared_secret                        |
| webhook:uuid         |                   | 11111111-1111-1111-1111-111111111111      |
| webhook:url          |                   | http://test.url/acquia-contenthub/webhook |
| webhook:settings_url |                   | http://test.url                           |
+----------------------+-------------------+-------------------------------------------+
<warning>Run `--fix` to synchronize Content Hub settings.",
        1,
      ],
      [
        2,
        $this->getChConfigurationSettings(['client_name' => 'test_clientname_new', 'origin' => '1111-00000000-0000-0000-0000-000000000000'], TRUE),
        $ch_config2,
        "Configuration does not match the one stored in the database.
+-------------+-------------------------------------------+-------------------------------------------+
| Config Key  | Value in Database                         | Overwritten Value                         |
+-------------+-------------------------------------------+-------------------------------------------+
| origin      | 0000-00000000-0000-0000-0000-000000000000 | 1111-00000000-0000-0000-0000-000000000000 |
| client_name | test_clientname                           | test_clientname_new                       |
+-------------+-------------------------------------------+-------------------------------------------+
<warning>Run `--fix` to synchronize Content Hub settings.",
        1,
      ],
      [
        2,
        $ch_config2,
        $ch_config2,
        'Content Hub configuration is in order. You may proceed.',
        0,
      ],
    ];
  }

}
