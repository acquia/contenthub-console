<?php

namespace Acquia\Console\ContentHub\Tests\EventSubscriber;

use Acquia\Console\Cloud\Platform\AcquiaCloudPlatform;
use Acquia\Console\Cloud\Tests\Command\PlatformCommandTestHelperTrait;
use Acquia\Console\Cloud\Tests\TestFixtureHelperTrait;
use Acquia\Console\ContentHub\Event\ServiceClientUuidEvent;
use Acquia\Console\ContentHub\EventSubscriber\SetClientServiceUuid;
use Acquia\Console\Helpers\PlatformCommandExecutioner;
use EclipseGc\CommonConsole\PlatformInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SetClientServiceUuidTest to test service uuid.
 *
 * @coversDefaultClass \Acquia\Console\ContentHub\EventSubscriber\SetClientServiceUuid
 *
 * @package Acquia\Console\ContentHub\Tests\EventSubscriber
 */
class SetClientServiceUuidTest extends TestCase {

  use PlatformCommandTestHelperTrait;
  use TestFixtureHelperTrait;
  use ProphecyTrait;

  /**
   * Platform Instance.
   *
   * @var \EclipseGc\CommonConsole\PlatformInterface
   */
  protected $platform;

  /**
   * SetClientServiceUuid Instance.
   *
   * @var \Acquia\Console\ContentHub\EventSubscriber\SetClientServiceUuid
   */
  protected $clientUuidSetter;

  /**
   * Platform Command Executioner instance.
   *
   * @var \Acquia\Console\Helpers\PlatformCommandExecutioner
   */
  protected $executioner;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    $this->executioner = $this->prophesize(PlatformCommandExecutioner::class);
    $environment_response = $this->getFixture('ace_environment_response.php')['111111-11111111-c36a-401a-9724-fd8072a607d7'];
    $arguments = [
      'create backup' => [
        'arguments' => [
          // First case.
          ['get', '/environments/111111-11111111-c36a-401a-9724-fd8072a607d7'],
          // Second case.
          ['get', '/environments/111111-11111111-c36a-401a-9724-fd8072a607d7'],
          // Third case.
        ],
        'returns' => [
          // First case.
          $environment_response,
          // Second case.
          $environment_response,
        ],
      ],
    ];
    $this->platform = $this->getPlatform($arguments);
  }

  /**
   * Tests SetClientServiceUuid Event Subscriber.
   *
   * @param array $output
   *   Array Output for runWithMemoryOutput.
   * @param string $expected_service_uuid
   *   Service Uuid.
   *
   * @covers ::getClientServiceUuid
   *
   * @dataProvider dataProvider
   */
  public function testSetClientServiceUuid(array $output, string $expected_service_uuid) {
    $this
      ->executioner
      ->runWithMemoryOutput(Argument::any(), Argument::any(), Argument::any())
      ->shouldBeCalled()
      ->willReturn($this->runWithMemoryOutputMocks($output));

    $this->clientUuidSetter = new SetClientServiceUuid($this->executioner->reveal());
    $input = $this->prophesize(InputInterface::class);
    $input->hasOption(Argument::any())->willReturn(FALSE);
    $output = $this->prophesize(OutputInterface::class)->reveal();
    $event = new ServiceClientUuidEvent($this->platform, $input->reveal(), $output);
    $this->clientUuidSetter->getClientServiceUuid($event);
    $service_uuid = $event->getClientServiceUuid();
    $this->assertEquals($expected_service_uuid, $service_uuid);
  }

  /**
   * Helper function to get the output depending on the command.
   *
   * @param array $output
   *   Array containing exit code and command output.
   *
   * @return object
   *   Object with output.
   */
  private function runWithMemoryOutputMocks(array $output): object {
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
   * Data provider.
   *
   * @return array
   *   Data provider array.
   */
  public function dataProvider(): array {
    return [
      [
        [
          'exit_code' => 0,
          'command_output' => '{"success":true,"data":{"service_client_uuid":"acquiacomtesting"}}',
        ],
        'acquiacomtesting',
      ],
      [
        [
          'exit_code' => 1,
          'command_output' => '',
        ],
        '',
      ],
    ];
  }

  /**
   * Returns mock platform.
   *
   * @param array $args
   *   Array for arguments.
   *
   * @return \EclipseGc\CommonConsole\PlatformInterface
   *   Platform object.
   */
  private function getPlatform(array $args = []): PlatformInterface {
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
          '111111-11111111-c36a-401a-9724-fd8072a607d7' => '111111-11111111-c36a-401a-9724-fd8072a607d7',
        ],
        PlatformInterface::PLATFORM_ALIAS_KEY => 'test',
      ],
      $client_mock_callback
    );
  }

}
