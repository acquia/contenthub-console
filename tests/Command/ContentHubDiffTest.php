<?php

namespace Acquia\Console\ContentHub\Tests\Command;

use Acquia\Console\ContentHub\Command\ContentHubDiff;
use Acquia\Console\ContentHub\Tests\ContentHubCommandTestBase;
use Prophecy\Argument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class ContentHubDiffTest.
 *
 * @coversDefaultClass \Acquia\Console\ContentHub\Command\ContentHubDiff
 *
 * @group acquia-console-contenthub
 *
 * @package Acquia\Console\ContentHub\Tests\Command
 */
class ContentHubDiffTest extends ContentHubCommandTestBase {

  /**
   * Test whether the diff module codebase exists or not.
   *
   * @param bool $module_exist
   *   Expected return value of exists() method.
   * @param string $needle
   *   String to look for within display.
   * @param int $exit_code
   *   Expected return value of execute() method.
   *
   * @covers ::execute
   *
   * @dataProvider dataProvider
   */
  public function testContentHubDiff(bool $module_exist, string $needle, int $exit_code) {
    $this
      ->drupalServiceFactory
      ->getDrupalService(Argument::any())
      ->shouldBeCalled()
      ->willReturn($this->getDrupalServiceMocks($module_exist));

    $command = $this->getCommand();
    $command->setDrupalServiceFactory($this->drupalServiceFactory->reveal());
    $command->setAchClientService($this->contentHubService->reveal());

    /** @var \Symfony\Component\Console\Tester\CommandTester $command_tester */
    $command_tester = $this->doRunCommand($command, [], [
      'alias' => 'test',
      '--uri' => "dev.acquiacloud.com"
    ]);
    $this->assertStringContainsString($needle, $command_tester->getDisplay());
    $this->assertEquals($exit_code, $command_tester->getStatusCode());
  }

  /**
   * Returns mock instance for getDrupalService().
   *
   * @param bool $module_exist
   *   Return value for exists().
   *
   * @return object
   *   Mock of getDrupalService function return.
   */
  public function getDrupalServiceMocks(bool $module_exist): object {
    return new class ($module_exist) {

      /**
       * Constructor.
       *
       * @param bool $module_exist
       *   Module exists.
       */
      public function __construct(bool $module_exist) {
        $this->exists = $module_exist;
      }

      /**
       * Mock for exists().
       */
      public function exists(): bool {
        return $this->exists;
      }

    };
  }

  /**
   * A data provider for ::testContentHubDiff()
   *
   * @return array[]
   *   Array for data provider.
   */
  public function dataProvider() {
    return [
      [
        TRUE,
        '',
        0,
      ],
      [
        FALSE,
        '{"success":true,"data":{"base_url":"dev.acquiacloud.com"}}',
        1,
      ],
    ];
  }

  /**
   * Helper function to get ContentHubDiff object with URI InputOption.
   *
   * @return \Acquia\Console\ContentHub\Command\ContentHubDiff
   *   Mock of ContentHubDiff return
   */
  private function getCommand(): ContentHubDiff {
    return new class extends ContentHubDiff {

      /**
       * {@inheritDoc}
       */
      public function configure() {
        parent::configure();
        $this->addOption('uri', 'ur', InputOption::VALUE_OPTIONAL, 'Mock site uri');
      }

    };
  }

}
