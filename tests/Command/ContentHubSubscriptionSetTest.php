<?php

namespace Acquia\Console\ContentHub\Tests\Command;

use Acquia\Console\Cloud\Tests\Command\CommandTestHelperTrait;
use Acquia\Console\Cloud\Tests\Command\PlatformCommandTestHelperTrait;
use Acquia\Console\ContentHub\Command\ContentHubSubscriptionSet;
use Acquia\Console\Cloud\Platform\AcquiaCloudPlatform;
use EclipseGc\CommonConsole\PlatformInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class ContentHubSubscriptionSetTest.
 *
 * @coversDefaultClass \Acquia\Console\ContentHub\Command\ContentHubSubscriptionSet
 *
 * @group acquia-console-contenthub
 *
 * @package Acquia\Console\ContentHub\Tests\Command
 */
class ContentHubSubscriptionSetTest extends TestCase {

  use CommandTestHelperTrait;
  use PlatformCommandTestHelperTrait;

  /**
   * Test acquia cloud subscription.
   *
   * @covers ::execute
   */
  public function testContentHubSubscriptionSet() {
    $platform = $this->getPlatform();
    $command = new ContentHubSubscriptionSet(
      $this->getDispatcher(),
      ContentHubSubscriptionSet::getDefaultName()
    );
    $command->addPlatform('test', $platform);
    /** @var \Symfony\Component\Console\Tester\CommandTester $command_tester */

    $command_tester = $this->doRunCommand($command, ['hostname',
      'api_key',
      'secret_key',
      'yes'
    ], ['alias' => 'test']);
    $this->assertStringContainsString('Please provide a valid Hostname', $command_tester->getDisplay());
    $this->assertEquals(1, $command_tester->getStatusCode());

    $command_tester = $this->doRunCommand($command, ['https://example.com',
      'api_key',
      'secret_key',
      'yes'
    ], ['alias' => 'test']);
    $this->assertStringContainsString('https://example.com', $command_tester->getDisplay());
    $this->assertStringContainsString('api_key', $command_tester->getDisplay());
    $this->assertStringContainsString('secret_key', $command_tester->getDisplay());
    $this->assertEquals(0, $command_tester->getStatusCode());

    $hostname = $platform->get(ContentHubSubscriptionSet::CONFIG_HOSTNAME);
    $api_key = $platform->get(ContentHubSubscriptionSet::CONFIG_API_KEY);
    $secret_key = $platform->get(ContentHubSubscriptionSet::CONFIG_SECRET_KEY);
    $this->assertEquals('https://example.com', $hostname, 'Hostname has been stored');
    $this->assertEquals('api_key', $api_key, 'API key has been stored');
    $this->assertEquals('secret_key', $secret_key, 'Secret key has been stored');
  }

  /**
   * {@inheritdoc}
   */
  public function getPlatform(array $args = []): PlatformInterface {
    return $this->getAcquiaCloudPlatform(
      [
        PlatformInterface::PLATFORM_ALIAS_KEY => 'test',
        PlatformInterface::PLATFORM_NAME_KEY => 'test',
        PlatformInterface::PLATFORM_TYPE_KEY => AcquiaCloudPlatform::getPlatformId(),
        AcquiaCloudPlatform::ACE_API_KEY => 'test_key',
        AcquiaCloudPlatform::ACE_API_SECRET => 'test_secret',
      ]
    );
  }

}
