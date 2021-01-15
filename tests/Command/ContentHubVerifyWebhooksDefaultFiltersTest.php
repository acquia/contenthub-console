<?php

namespace Acquia\Console\ContentHub\Tests\Command;

use Acquia\Console\ContentHub\Command\ContentHubVerifyWebhooksDefaultFilters;
use Acquia\Console\ContentHub\Tests\ContentHubCommandTestBase;
use Prophecy\Argument;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * Class ContentHubVerifyWebhooksDefaultFiltersTest.
 *
 * @coversDefaultClass \Acquia\Console\ContentHub\Command\ContentHubVerifyWebhooksDefaultFilters
 *
 * @group acquia-console-contenthub
 *
 * @package Acquia\Console\ContentHub\Tests\Command
 */
class ContentHubVerifyWebhooksDefaultFiltersTest extends ContentHubCommandTestBase {

  /**
   * Tests whether all default filters are successfully attached to their corresponding webhooks.
   *
   * @covers ::execute
   *
   * @param array $webhooks_data
   *   Expected return value of getWebhooks() method.
   * @param array $filters_data
   *   Expected return value of listFilters() method.
   * @param array $unmitigated_filters
   *   Filters mock data.
   * @param string $needle
   *   String to look for within display.
   * @param int $exit_code
   *   Expected return value of execute() method.
   *
   * @dataProvider dataProvider
   */
  public function testContentHubVerifyWebhooksDefaultFilters(array $webhooks_data, array $filters_data, array $unmitigated_filters, string $needle, int $exit_code) {

    $this
      ->contentHubService
      ->getWebhooks()
      ->shouldBeCalled()
      ->willReturn($webhooks_data);

    if (!empty($webhooks_data)) {
      $this
        ->contentHubService
        ->listFilters()
        ->shouldBeCalled()
        ->willReturn($filters_data);
    }

    $this
      ->drupalServiceFactory
      ->getDrupalService(Argument::any())
      ->shouldBeCalled()
      ->willReturn($this->getDrupalServiceMocks($unmitigated_filters));

    $command = new ContentHubVerifyWebhooksDefaultFilters();
    $command->setDrupalServiceFactory($this->drupalServiceFactory->reveal());
    $command->setAchClientService($this->contentHubService->reveal());

    /** @var \Symfony\Component\Console\Tester\CommandTester $command_tester */
    $command_tester = $this->doRunCommand($command, [], ['alias' => 'test']);
    $this->assertEquals($needle, $command_tester->getDisplay());
    $this->assertEquals($exit_code, $command_tester->getStatusCode());

  }

  /**
   * Returns mock instance for getDrupalService().
   *
   * @param array $unmitigated_filters
   *   Unmitigated filters mock data.
   *
   * @return object
   *   Mock of getDrupalService function return.
   */
  public function getDrupalServiceMocks(array $unmitigated_filters): object {
    return new class ($unmitigated_filters) {

      /**
       * @var array
       *   Unmitigated filters.
       */
      protected $filters;

      /**
       * Class constructor.
       *
       * @param array $unmitigated_filters
       *   Unmitigated filter data.
       */
      public function __construct(array $unmitigated_filters) {
        $this->filters = $unmitigated_filters;
      }

      /**
       * Mock drupal state function returns.
       *
       * @param string $state_name
       *   Drupal state name.
       *
       * @return array
       *   Array of filters.
       */
      public function get(string $state_name): array {
        return $this->filters[$state_name];
      }

    };
  }

  /**
   * A data provider for ::testContentHubVerifyWebhooksDefaultFilters()
   *
   * @return array[]
   *   Array for data provider.
   */
  public function dataProvider(): array {
    return [
      // First case.
      [
        [
          'filters' => ['4489430c21'],
        ],
        [
          'data' => []
        ],
        [
          'acquia_contenthub_subscriber_82002_unmigrated_filters' => [],
          'acquia_contenthub_subscriber_82002_acquia_contenthub_filters' => []
        ],
        'Verifying default filters...
All default filters are successfully attached to their corresponding webhooks.
All filters have been successfully migrated from 1.x to 2.x' . PHP_EOL,
        0,
      ],
      // Second case.
      [
        [
          'filters' => ['4489430c20'],
        ],
        [
          'data' => [
            [
              'uuid' => '4489430c20',
              'name' => 'default_filter'
            ],
          ]
        ],
        [
          'acquia_contenthub_subscriber_82002_unmigrated_filters' => ['Unmigrated filters'],
          'acquia_contenthub_subscriber_82002_acquia_contenthub_filters' => ['Unmigrated filters']
        ],
        'Verifying default filters...
<warning>Some default filters are not attached to any webhook.<warning>
+------------+----------------+
| UUID       | Name           |
+------------+----------------+
| 4489430c20 | default_filter |
+------------+----------------+
<warning>Not all filters have been successfully migrated from 1.x to 2.x. Below is a list of the filters that were not migrated:</warning>
+-------------------------------+
| Unsuccessful migrated filters |
+-------------------------------+
| Unmigrated filters            |
+-------------------------------+' . PHP_EOL,
        1,
      ],
      // Third case.
      [
        [],
        [],
        [
          'acquia_contenthub_subscriber_82002_unmigrated_filters' => [],
          'acquia_contenthub_subscriber_82002_acquia_contenthub_filters' => []
        ],
        'Verifying default filters...
No webhooks found.
All filters have been successfully migrated from 1.x to 2.x' . PHP_EOL,
        1,
      ],
      // Fourth case.
      [
        [],
        [],
        [
          'acquia_contenthub_subscriber_82002_unmigrated_filters' => ['Unmigrated filters'],
          'acquia_contenthub_subscriber_82002_acquia_contenthub_filters' => ['Unmigrated filters']
        ],
        'Verifying default filters...
No webhooks found.
<warning>Not all filters have been successfully migrated from 1.x to 2.x. Below is a list of the filters that were not migrated:</warning>
+-------------------------------+
| Unsuccessful migrated filters |
+-------------------------------+
| Unmigrated filters            |
+-------------------------------+' . PHP_EOL,
        1,
      ],
    ];
  }

}
