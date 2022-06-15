<?php

namespace Acquia\Console\ContentHub\Tests\Command;

use Acquia\Console\ContentHub\Client\ContentHubServiceVersion2;
use Acquia\Console\ContentHub\Command\ContentHubServiceUuid;
use Acquia\Console\ContentHub\Tests\ContentHubCommandTestBase;
use Prophecy\Argument;

/**
 * Class ContentHubServiceUuidTest.
 *
 * @coversDefaultClass \Acquia\Console\ContentHub\Command\ContentHubServiceUuid
 *
 * @group acquia-console-contenthub
 *
 * @package Acquia\Console\ContentHub\Tests\Command
 */
class ContentHubServiceUuidTest extends ContentHubCommandTestBase {

  /**
   * Test Content Hub Service Uuid Command.
   *
   * @param int $module_version
   *   Module version for CH.
   * @param string $uuid
   *   Expected return value of uuid.
   * @param string $needle
   *   String to look for within display.
   * @param int $exit_code
   *   Expected return value of execute() method.
   *
   * @covers ::execute
   *
   * @dataProvider dataProvider
   */
  public function testContentHubServiceUuid(int $module_version, string $uuid, string $needle, int $exit_code) {
    $this
      ->drupalServiceFactory
      ->getModuleVersion()
      ->shouldBeCalled()
      ->willReturn($module_version);

    $this
      ->drupalServiceFactory
      ->getDrupalService(Argument::any())
      ->willReturn($this->getDrupalServiceMocks($uuid));

    if ($module_version === 2) {
      $this->contentHubService = $this->prophesize(ContentHubServiceVersion2::class);
      $this
        ->contentHubService
        ->getRemoteSettings()
        ->willReturn(['uuid' => $uuid]);
    }

    $command = new ContentHubServiceUuid();
    $command->setDrupalServiceFactory($this->drupalServiceFactory->reveal());
    $command->setAchClientService($this->contentHubService->reveal());

    /** @var \Symfony\Component\Console\Tester\CommandTester $command_tester */
    $command_tester = $this->doRunCommand($command, [], [
      'alias' => 'test',
    ]);

    $this->assertEquals($needle, $command_tester->getDisplay());
    $this->assertEquals($exit_code, $command_tester->getStatusCode());
  }

  /**
   * Helper method to get the drupal service mock.
   *
   * @param string $uuid
   *   Mock value for UUID.
   *
   * @return object
   *   Object for drupal service.
   */
  private function getDrupalServiceMocks(string $uuid): object {
    return new class($uuid) {

      /**
       *  Inline constructor.
       *
       * @param string $uuid
       *   Mock UUID.
       */
      public function __construct(string $uuid) {
        $this->uuid = $uuid;
      }

      /**
       * Get mock uuid.
       *
       * @return string
       *   UUID to return.
       */
      public function getUuid() {
        return $this->uuid;
      }

    };
  }

  /**
   * Data provider for testContentHubServiceUuid()
   *
   * @return array[]
   *   Data provider array.
   */
  public function dataProvider(): array {
    return [
      [
        1,
        'acquiacomtesting',
        '{"success":true,"data":{"service_client_uuid":"acquiacomtesting"}}' . PHP_EOL,
        0,
      ],
      [
        2,
        'acquiacomtesting',
        '{"success":true,"data":{"service_client_uuid":"acquiacomtesting"}}' . PHP_EOL,
        0,
      ],
      [
        1,
        '',
        '{"success":false,"error":{"message":"Client Service Uuid doesn\'t exist<\/error>"}}' . PHP_EOL,
        1,
      ],
      [
        2,
        '',
        '{"success":false,"error":{"message":"Client Service Uuid doesn\'t exist<\/error>"}}' . PHP_EOL,
        1,
      ],
    ];
  }

}
