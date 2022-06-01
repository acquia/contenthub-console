<?php

namespace Acquia\Console\ContentHub\Tests\Command\PqCommands;

use Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory;
use Acquia\Console\ContentHub\Command\Helpers\ModuleDiscoverer;
use Acquia\Console\ContentHub\Command\PqCommands\ContentHubPqEntityTypes;
use Acquia\Console\ContentHub\Tests\Drupal\DrupalServiceMockGeneratorTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @coversDefaultClass \Acquia\Console\ContentHub\Command\PqCommands\ContentHubPqEntityTypes
 *
 * @group contenthub_console_pq_commands
 */
class ContentHubPqEntityTypesTest extends TestCase {

  use DrupalServiceMockGeneratorTrait;
  use ProphecyTrait;

  /**
   * SUT object.
   *
   * @var \Acquia\Console\ContentHub\Command\PqCommands\ContentHubPqEntityTypes
   */
  protected $command;

  /**
   * Mocked module discoverer.
   *
   * @var \Acquia\Console\ContentHub\Command\Helpers\ModuleDiscoverer|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $moduleDiscoverer;

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
    $this->moduleDiscoverer = $this->prophesize(ModuleDiscoverer::class);
    $this->serviceFactory = $this->prophesize(DrupalServiceFactory::class);

    $this->command = new ContentHubPqEntityTypes(
      $this->moduleDiscoverer->reveal(),
      $this->serviceFactory->reveal()
    );
  }

  /**
   * Tests the retrieval of unsupported entity types in a mocked Drupal env.
   *
   * @throws \Exception
   */
  public function testGetAllUnsupportedEntityTypes(): void {
    $definitions = $this->generateMockEntityTypeDefinitions();
    $entityTypeManager = $this->generateDrupalServiceMock([
      'getDefinitions' => $definitions,
    ]);
    $this->serviceFactory
      ->getDrupalService(Argument::exact('entity_type.manager'))
      ->willReturn($entityTypeManager);

    $this->moduleDiscoverer
      ->getAvailableModules()
      ->willReturn([
        'core' => ['node'],
      ]);

    $entityTypes = $this->command->getAllUnsupportedEntityTypes();
    $this->assertEquals(['contrib_entity'], $entityTypes);
  }

  /**
   * Returns a number of possible variations of entity definition.
   *
   * @return array
   *   Mocked entity definitions.
   */
  protected function generateMockEntityTypeDefinitions(): array {
    return [
      $this->generateMockEntityTypeDefinition('Drupal\Core\Entity\SomeEntity', 'some_entity'),
      $this->generateMockEntityTypeDefinition('Drupal\contrib\Entity\ContribEntity', 'contrib_entity'),
      $this->generateMockEntityTypeDefinition('Drupal\paragraphs\Entity\Paragraph', 'paragraph'),
      $this->generateMockEntityTypeDefinition('Drupal\Core\Entity\SomeEntity2', 'some_entity2'),
      $this->generateMockEntityTypeDefinition('Drupal\node\Entity\Node', 'node'),
    ];
  }

  /**
   * Returns a mocked entity definition.
   *
   * @param string $class
   *   The class of the entity definition.
   * @param string $id
   *   The entity type id.
   *
   * @return object
   *   The definition object.
   */
  protected function generateMockEntityTypeDefinition(string $class, string $id): object {
    return $this->generateDrupalServiceMock([
      'getClass' => $class,
      'id' => $id,
    ]);
  }

}
