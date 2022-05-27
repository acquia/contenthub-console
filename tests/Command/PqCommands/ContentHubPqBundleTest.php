<?php

namespace Acquia\Console\ContentHub\Tests\Command\PqCommands;

use Acquia\Console\ContentHub\Command\PqCommands\ContentHubPqBundle;
use EclipseGc\CommonConsole\Tests\CommonConsoleTestBase;
use Prophecy\Argument;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @coversDefaultClass \Acquia\Console\ContentHub\Command\PqCommands\ContentHubPqBundle
 *
 * @group contenthub_console_pq_commands
 */
class ContentHubPqBundleTest extends CommonConsoleTestBase {

  /**
   * The command to test.
   *
   * @var \Acquia\Console\ContentHub\Command\PqCommands\ContentHubPqBundle
   */
  protected $command;

  /**
   * List of mocked commands used for testing.
   *
   * @var array
   */
  protected $testCommands;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $testPqCommand1 = $this->prophesize(Command::class);
    $testPqCommand1->getName()->willReturn('ach:pq:test1');
    $testPqCommand2 = $this->prophesize(Command::class);
    $testPqCommand2->getName()->willReturn('ach:pq:test2');
    $this->testCommands = [
      'test1' => $testPqCommand1->reveal(),
      'test2' => $testPqCommand2->reveal(),
    ];

    $application = $this->prophesize(Application::class);
    $application->all(Argument::type('string'))
      ->willReturn(array_values($this->testCommands));
    $application->getHelperSet()
      ->willReturn($this->prophesize(HelperSet::class)->reveal());

    $this->command = new ContentHubPqBundle();
    $this->command->setApplication($application->reveal());
  }

  /**
   * Tests --exclude option.
   */
  public function testGetPqCommandsWithExcludedCommands() {
    $excluded = ['test1'];
    $list = $this->command->getPqCommands($excluded, 'exclude');
    $this->assertEquals([$this->testCommands['test2']], $list);

    $excluded = ['test2'];
    $list = $this->command->getPqCommands($excluded, 'exclude');
    $this->assertEquals([$this->testCommands['test1']], $list);

    $excluded = ['test1', 'test2'];
    $list = $this->command->getPqCommands($excluded, 'exclude');
    $this->assertEquals([], $list);

    $list = $this->command->getPqCommands([], 'exclude');
    $this->assertEquals(array_values($this->testCommands), $list);
  }

  /**
   * Tests --checks option.
   */
  public function testGetPqCommandsWithIncludedCommands() {
    $included = ['test1'];
    $list = $this->command->getPqCommands($included, 'checks');
    $this->assertEquals([$this->testCommands['test1']], $list);

    $included = ['test2'];
    $list = $this->command->getPqCommands($included, 'checks');
    $this->assertEquals([$this->testCommands['test2']], $list);

    $included = ['test1', 'test2'];
    $list = $this->command->getPqCommands($included, 'checks');
    $this->assertEquals(array_values($this->testCommands), $list);

    $list = $this->command->getPqCommands([], 'checks');
    $this->assertEquals([], $list);
  }

  /**
   * Tests options when --checks and --exclude are both provided.
   */
  public function testExecuteWithExcludeAndInclude() {
    /** @var \Symfony\Component\Console\Application $application */
    $application = $this->getContainer()->get('common_console_application');
    $allCommand = $application->find('ach:pq:all');
    $commandTester = new CommandTester($allCommand);
    $commandTester->execute([
      '--exclude' => 'general',
      '--checks' => 'general',
    ]);
    $display = $commandTester->getDisplay();
    $this->assertStringContainsString(
      'The options "exclude" and "checks" cannot be used together',
      $display
    );
    $this->assertEquals(1, $commandTester->getStatusCode());
  }

}
