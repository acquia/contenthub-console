<?php

namespace Acquia\Console\ContentHub\Command\Tests\PqCommands;

use Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory;
use Acquia\Console\ContentHub\Command\PqCommands\ContentHubPqDependencies;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

/**
 * @coversDefaultClass \Acquia\Console\ContentHub\Command\PqCommands\ContentHubPqDependencies
 *
 * @group contenthub_console_pq_commands
 */
class ContentHubPqDependenciesTest extends TestCase {

  use ProphecyTrait;

  /**
   * SUT object.
   *
   * @var \Acquia\Console\ContentHub\Command\PqCommands\ContentHubPqDependencies
   */
  protected $command;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->command = new ContentHubPqDependencies(
      $this->prophesize(DrupalServiceFactory::class)->reveal()
    );
  }

  /**
   * Tests content eligibility for export.
   *
   * @dataProvider getExportEligibleContentEntitiesDataProvider
   *
   * @throws \Exception
   */
  public function testParseInput(string $inputValue, array $expectation): void {
    $definition = new InputDefinition();
    $definition->addOption(new InputOption('--entity-type'));
    $input = new ArrayInput(
      [
        '--entity-type' => $inputValue,
      ],
      $definition
    );
    $actual = $this->command->parseInput($input);
    $this->assertEquals($expectation, $actual);
  }

  /**
   * Provides test input for testParseInput.
   *
   * @return array[]
   *   The test cases.
   */
  public function getExportEligibleContentEntitiesDataProvider(): array {
    return [
      [
        'node', ['node' => []],
      ],
      [
        'node:page', ['node' => ['page']],
      ],
      [
        'node:page,node:article', ['node' => ['page', 'article']],
      ],
      [
        'node,shortcut', ['node' => [], 'shortcut' => []],
      ],
      [
        'node:page,shortcut', ['node' => ['page'], 'shortcut' => []],
      ],
      [
        'node:article,block_content:general',
        [
          'node' => ['article'],
          'block_content' => ['general'],
        ],
      ],
      [
        'node,block_content:general',
        [
          'node' => [],
          'block_content' => ['general'],
        ],
      ],
      ['', []],
    ];
  }

}
