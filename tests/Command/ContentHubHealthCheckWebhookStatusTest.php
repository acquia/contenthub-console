<?php

namespace Acquia\Console\ContentHub\Tests\Command;

use Acquia\Console\ContentHub\Client\ContentHubServiceVersion2;
use Acquia\Console\ContentHub\Command\ContentHubHealthCheckWebhookStatus;
use Acquia\Console\ContentHub\Tests\ContentHubCommandTestBase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * Class ContentHubHealthCheckWebhookStatusTest.
 *
 * @coversDefaultClass \Acquia\Console\ContentHub\Command\ContentHubHealthCheckWebhookStatus
 *
 * @group acquia-console-contenthub
 *
 * @package Acquia\Console\ContentHub\Tests\Command
 */
class ContentHubHealthCheckWebhookStatusTest extends ContentHubCommandTestBase {

  use ProphecyTrait;

  /**
   * Output stream.
   *
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  protected $output;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    // Open up new output stream to print the output for assertion.
    $this->output = new StreamOutput(fopen('php://memory', 'w', FALSE));
    // This needs to be done because removeWebhookSuppression method doesn't exist in ContentHubServiceInterface.
    $this->contentHubService = $this->prophesize()->willExtend(ContentHubServiceVersion2::class);
  }

  /**
   * Test ContentHubHealthCheckWebhookStatus Command.
   *
   * @param array $alias_options
   *   Array of alias and options(if any) which needs to be passed in command.
   * @param array $webhook_data
   *   Expected return value of getWebhooks() method.
   * @param string $needle
   *   Initial String which is part of the output.
   * @param int $exit_code
   *   Expected return value of execute() method.
   *
   * @covers ::execute
   *
   * @dataProvider dataProvider
   */
  public function testContentHubHealthCheckWebhookStatus(array $alias_options, array $webhook_data, string $needle, int $exit_code) {
    $this
      ->contentHubService
      ->getWebHooks()
      ->shouldBeCalled()
      ->willReturn($this->getWebhooksMocks($webhook_data));

    if (!empty($webhook_data) && array_key_exists('--fix', $alias_options) && ($this->getRemoveWebhookSuppressionCount($webhook_data) > 0)) {
      $this
        ->contentHubService
        ->removeWebhookSuppression(Argument::any())
        ->shouldBeCalled()
        ->willReturn($this->removeWebhookSuppressionMocks());
    }

    $command = new ContentHubHealthCheckWebhookStatus();
    $command->setDrupalServiceFactory($this->drupalServiceFactory->reveal());
    $command->setAchClientService($this->contentHubService->reveal());

    // Guzzle Client only needs to be set if fix option is available.
    if (!empty($webhook_data) && array_key_exists('--fix', $alias_options)) {
      $guzzleClient = $this->setGuzzleClientMock($webhook_data);
      $command->setGuzzleClient($guzzleClient);
    }

    /** @var \Symfony\Component\Console\Tester\CommandTester $command_tester */
    $command_tester = $this->doRunCommand($command, [], $alias_options);

    // Get output for assertion.
    $data_provider_output = $this->getOutput($alias_options, $webhook_data, $needle);

    $this->assertStringContainsString($data_provider_output, $command_tester->getDisplay());
    $this->assertEquals($exit_code, $command_tester->getStatusCode());
  }

  /**
   * Returns mock instance of removeWebhookSuppression().
   *
   * @return array
   *   Success array.
   */
  private function removeWebhookSuppressionMocks() : array {
    return ['success' => 'Webhook suppression removed'];
  }

  /**
   * Returns mock instance of getWebhooks().
   *
   * @param array $webhooks_data
   *   Return value of $webhooks.
   *
   * @return array
   *   Mock of getWebhooks() function return.
   */
  private function getWebhooksMocks(array $webhooks_data): array {
    return $webhooks_data;
  }

  /**
   * Sets the guzzleClient mock.
   *
   * @param array $webhook_data
   *   Webhook array.
   *
   * @return \GuzzleHttp\Client
   *   Returns Mocked Guzzle Client with predefined response for suppressed webhooks.
   */
  private function setGuzzleClientMock(array $webhook_data): Client {
    $response_array = [];
    $response_array_options = [
      'success' => new Response(200, [], 'Webhook Online'),
      'unavailable' => new Response(201, [], 'Webhook Not available'),
      'error' => new RequestException('Could not find domain.', new Request('OPTIONS', 'test'))
    ];
    foreach ($webhook_data as $webhook) {
      if ($this->isSuppressed($webhook['suppressed_until'])) {
        $response_array[] = $response_array_options[$webhook['response_type']];
      }
    }
    $mock = new MockHandler($response_array);
    $handlerStack = HandlerStack::create($mock);
    return new Client(['handler' => $handlerStack]);
  }

  /**
   * A data provider for ::testContentHubHealthCheckWebhookStatus()
   *
   * Format for data:
   *  - Alias and options if any.
   *  - Webhook array.
   *  - Initial needle.
   *  - Exit code.
   *
   * @return array[]
   *   Array for data provider.
   */
  public function dataProvider() {
    return [
      [
        ['alias' => 'test'],
        $this->getWebhookData(5),
        'Webhook status:',
        0,
      ],
      [
        ['alias' => 'test', '--fix' => TRUE],
        $this->getWebhookData(5),
        'Webhook status:',
        0,
      ],
      [
        ['alias' => 'test'],
        $this->getWebhookData(10),
        'Webhook status:',
        0,
      ],
      [
        ['alias' => 'test', '--fix' => TRUE],
        $this->getWebhookData(10),
        'Webhook status:',
        0,
      ],
      [
        ['alias' => 'test'],
        [],
        'No webhooks found.',
        1,
      ],
      [
        ['alias' => 'test', '--fix' => TRUE],
        [],
        'No webhooks found.',
        1,
      ]
    ];
  }

  /**
   * Helper function to check count of online suppressed webhooks to mock Guzzle Client.
   *
   * @param array $webhook_data
   *   Webhook data.
   *
   * @return int
   *   Count for suppressed webhooks.
   */
  private function getRemoveWebhookSuppressionCount(array $webhook_data) : int {
    $count = 0;
    foreach ($webhook_data as $webhook) {
      // When webhook is suppressed and is online.
      if ($this->isSuppressed($webhook['suppressed_until']) && $webhook['response_type'] === 'success') {
        $count++;
      }
    }
    return $count;
  }

  /**
   * Helper function to provide array of webhooks.
   *
   * @param int $webhook_count
   *   Number of webhooks.
   *
   * @return array
   *   Array of webhooks.
   */
  private function getWebhookData(int $webhook_count): array {
    $webhooks = [];
    $status_array = ['ENABLED', "DISABLED"];
    $response_array = ['success', 'error', 'unavailable'];
    for ($i = 0; $i < $webhook_count; $i++) {
      $webhooks[] = [
        'uuid' => $this->getRandomString(32) . $i,
        'url' => 'https://' . $this->getRandomString(10) . $i . '.devcloud.acquia-sites.com/acquia-contenthub/webhook',
        'suppressed_until' => mt_rand(time() - 100, time() + 100),
        'client_name' => $this->getRandomString(10) . $i,
        'status' => $status_array[array_rand($status_array)],
        'response_type' => $response_array[array_rand($response_array)]
      ];
    }
    return $webhooks;
  }

  /**
   * Helper function to get random string of given length from current time.
   *
   * @param int $length
   *   Lenght of the string required.
   *
   * @return string
   *   Random string.
   */
  private function getRandomString(int $length): string {
    return substr(md5(time()), 0, $length);
  }

  /**
   * Create Table object from settings information.
   *
   * @param array $webhook_data
   *   Webhooks array.
   */
  private function renderWebhooksTable(array $webhook_data): void {
    $table = new Table($this->output);
    $table->setHeaders(['Client name', 'Status', 'Suppressed until']);

    $rows = [];
    foreach ($webhook_data as $webhook) {
      $rows[] = [
        $webhook['client_name'],
        $webhook['status'],
        $this->isSuppressed($webhook['suppressed_until']) ?
          $this->formatTimestamp($webhook['suppressed_until'])
          : 'Not suppressed'
      ];
    }
    $table->addRows($rows);
    $table->render();
  }

  /**
   * Decides if webhook is suppressed or not based on timestamp.
   *
   * @param int $timestamp
   *   Field (suppressed_until) value from response.
   *
   * @return bool
   *   TRUE if suppressed, FALSE otherwise.
   */
  private function isSuppressed(int $timestamp): bool {
    return $timestamp > time();
  }

  /**
   * Format timestamp into a user friendly format.
   *
   * @param int $timestamp
   *   Field (suppressed_until) value from response.
   *
   * @return string
   *   Date format.
   */
  private function formatTimestamp(int $timestamp): string {
    $date = new \DateTime();
    $date->setTimestamp($timestamp);
    return $date->format('Y-m-d H:i:s');
  }

  /**
   * Helper function to get the output for assertion.
   *
   * @param array $alias_options
   *   Alias options passed in the command.
   * @param array $webhook_data
   *   Mocked webhook data.
   * @param string $needle
   *   Initial string provided in the data provider.
   *
   * @return string
   *   Actual output of the command for assertion.
   */
  private function getOutput(array $alias_options, array $webhook_data, string $needle): string {
    $this->output->writeln($needle);

    // Print the webhook table for assertion.
    if (!empty($webhook_data)) {
      $this->renderWebhooksTable($webhook_data);
    }

    // If fix option is passed then print out the webhook suppression info.
    if (array_key_exists('--fix', $alias_options)) {
      $webhook_online_status = [
        // Webhook is online.
        'success' => "Removing suppression from webhook: ",
        // Webhook is not available. Client throwing exception.
        'error' => "Could not find domain.",
        // Webhook is unavailable and code other than 200.
        'unavailable' => 'Webhook is offline, cannot remove suppression. ID: '
      ];
      foreach ($webhook_data as $webhook) {
        // Print only if webhook is suppressed.
        if ($this->isSuppressed($webhook['suppressed_until'])) {
          if (($response_type = $webhook['response_type']) === 'success') {
            $this->output->writeln($webhook_online_status[$response_type] . $webhook['client_name'] . ': ' . $webhook['uuid']);
          }
          elseif (($response_type = $webhook['response_type']) === 'error') {
            $this->output->writeln($webhook_online_status[$response_type]);
            $this->output->writeln($webhook_online_status['unavailable'] . $webhook['uuid']);
          }
          else {
            $this->output->writeln($webhook_online_status['unavailable'] . $webhook['uuid']);
          }
        }
      }
    }

    rewind($this->output->getStream());
    // Get the stream output and return for assertion.
    return stream_get_contents($this->output->getStream()) ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    $this->output = NULL;
  }

}
