<?php

namespace Acquia\Console\ContentHub\Command\Tests\PqCommands;

use Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory;
use Acquia\Console\ContentHub\Command\PqCommands\ContentHubPqDependencies;
use Acquia\Console\ContentHub\Tests\Drupal\DrupalServiceMockGeneratorTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @coversDefaultClass \Acquia\Console\ContentHub\Command\PqCommands\ContentHubPqDependencies
 *
 * @group contenthub_console_pq_commands
 */
class ContentHubPqDependenciesTest extends TestCase {

  use DrupalServiceMockGeneratorTrait;
  use ProphecyTrait;

  /**
   * SUT object.
   *
   * @var \Acquia\Console\ContentHub\Command\PqCommands\ContentHubPqDependencies
   */
  protected $command;

  /**
   * Mocked Drupal service factory.
   *
   * @var \Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $serviceFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->serviceFactory = $this->prophesize(DrupalServiceFactory::class);

    $this->command = new ContentHubPqDependencies(
      $this->serviceFactory->reveal()
    );
  }

  /**
   * Tests content eligibility for export.
   *
   * @throws \Exception
   */
  public function testGetExportEligibleContentEntities(): void {
    $entityDefinitions = [
      'node' => $this->generateDrupalServiceMock([]),
    ];
    $entityTypeManager = $this->generateDrupalServiceMock([
      'getDefinitions' => $entityDefinitions,
    ]);
    $this->serviceFactory->getDrupalService(Argument::exact('entity_type.manager'))
      ->willReturn($entityTypeManager);
  }

}
