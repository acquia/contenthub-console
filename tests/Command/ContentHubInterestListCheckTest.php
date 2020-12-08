<?php

namespace Acquia\Console\ContentHub\Tests\Command;

use Acquia\Console\ContentHub\Client\ContentHubServiceVersion2;
use Acquia\Console\ContentHub\Command\ContentHubInterestListCheck;
use Acquia\Console\ContentHub\Tests\ContentHubCommandTestBase;
use Prophecy\Argument;

/**
 * Class ContentHubInterestListCheckTest
 *
 * @coversDefaultClass \Acquia\Console\ContentHub\Command\ContentHubInterestListCheck
 *
 * @group acquia-console-contenthub
 *
 * @package Acquia\Console\ContentHub\Tests\Command
 */
class ContentHubInterestListCheckTest extends ContentHubCommandTestBase {

  /**
   * Test Content Hub interest list.
   *
   * @covers ::execute
   *
   * @param bool $check_client
   *   Expected return value of checkClient() method.
   * @param array $diff
   *   Expected return value of getTrackingAndInterestDiff() method.
   * @param string $needle
   *   String to look for within display.
   * @param int $exit_code
   *   Expected return value of execute() method.
   *
   * @dataProvider dataProvider
   */
  public function testContentHubInterestListCheck(bool $check_client, array $diff, string $needle, int $exit_code) {
    $this->contentHubService = $this
      ->prophesize()
      ->willExtend(ContentHubServiceVersion2::class);

    $this
      ->contentHubService
      ->checkClient(Argument::any())
      ->shouldBeCalled()
      ->willReturn($check_client);

    if (!empty($check_client)) {
      $this
        ->contentHubService
        ->getTrackingAndInterestDiff(Argument::any())
        ->shouldBeCalled()
        ->willReturn($diff);
    }

    $command = new ContentHubInterestListCheck();
    $command->setDrupalServiceFactory($this->drupalServiceFactory->reveal());
    $command->setAchClientService($this->contentHubService->reveal());

    /** @var \Symfony\Component\Console\Tester\CommandTester $command_tester */
    $command_tester = $this->doRunCommand($command, [], ['alias' => 'test']);
    $this->assertEquals($needle, $command_tester->getDisplay());
    $this->assertEquals($exit_code, $command_tester->getStatusCode());
  }

  /**
   * A data provider for ::testContentHubInterestListCheck()
   *
   * @return array[]
   */
  public function dataProvider(): array {
    return [
      [
        FALSE,
        [],
        'Client connection to service is not healthy.' . PHP_EOL,
        1
      ],
      [
        TRUE,
        [],
        'There are no entities in tracking table(s) and interest list.' . PHP_EOL,
        0
      ],
      [
        TRUE,
        [
          'tracking_diff' => [],
          'interest_diff' => [],
        ],
        'There are no differences between this Webhook\'s Interest list and export/import tracking table.' . PHP_EOL,
        0
      ],
      [
        TRUE,
        [
          'tracking_diff' => [1],
          'interest_diff' => [],
        ],
        'There are 1 entities in the tracking table which missing from the interest list.' . PHP_EOL .
        'For listing the actual differences, please use the ach:health-check:interest-diff command.' . PHP_EOL,
        2,
      ],
      [
        TRUE,
        [
          'tracking_diff' => [],
          'interest_diff' => [1],
        ],
        'There are 1 entities on the interest list but missing from the tracking table(s).' . PHP_EOL .
        'For listing the actual differences, please use the ach:health-check:interest-diff command.' . PHP_EOL,
        2,
      ],
      [
        TRUE,
        [
          'tracking_diff' => [1],
          'interest_diff' => [1],
        ],
        'There are 1 entities in the tracking table which missing from the interest list.' . PHP_EOL .
        'There are 1 entities on the interest list but missing from the tracking table(s).' . PHP_EOL .
        'For listing the actual differences, please use the ach:health-check:interest-diff command.' . PHP_EOL,
        2,
      ],
    ];
  }
}
