<?php

namespace Acquia\Console\ContentHub\Tests\Command\PqCommands\Services;

use Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory;
use Acquia\Console\ContentHub\Command\Helpers\ModuleDiscoverer;
use Acquia\Console\ContentHub\Tests\Drupal\DrupalServiceMockGeneratorTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @coversDefaultClass \Acquia\Console\ContentHub\Command\Helpers\ModuleDiscoverer
 *
 * @group contenthub_console_services
 */
class ModuleDiscovererTest extends TestCase {

  use DrupalServiceMockGeneratorTrait;
  use ProphecyTrait;

  /**
   * SUT object.
   *
   * @var \Acquia\Console\ContentHub\Command\Helpers\ModuleDiscoverer
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
    $this->serviceFactory = $this->prophesize(DrupalServiceFactory::class);
    $this->moduleDiscoverer = new ModuleDiscoverer(
      $this->serviceFactory->reveal()
    );
  }

  /**
   * Tests available modules with different scenarios.
   *
   * @throws \Exception
   */
  public function testGetAvailableModules(): void {
    $extListModule = $this->generateDrupalServiceMock([
      'getList' => $this->generateMockExtensions(),
    ]);
    $this->serviceFactory->getDrupalService(Argument::exact('extension.list.module'))
      ->shouldBeCalledOnce()
      ->willReturn($extListModule);
    $expectation = [
      'core' => [
        'module3',
      ],
      'non-core' => [
        'module1',
        'module2',
      ],
    ];
    $this->assertEquals($expectation, $this->moduleDiscoverer->getAvailableModules());
  }

  /**
   * Returns an array of extensions.
   *
   * @return array
   *   Extension list of every kind.
   */
  protected function generateMockExtensions(): array {
    return [
      $this->generateDrupalServiceMock([
        'getType' => 'theme',
        'getName' => 'theme1',
        'getPath' => 'modules/contrib',
      ]),
      $this->generateDrupalServiceMock([
        'getType' => 'theme',
        'getName' => 'theme2',
        'getPath' => 'core/themes',
      ]),
      $this->generateDrupalServiceMock([
        'getType' => 'profile',
        'getName' => 'profile1',
        'getPath' => 'core/profile',
      ]),
      $this->generateDrupalServiceMock([
        'getType' => 'profile',
        'getName' => 'profile2',
        'getPath' => 'modules/contrib',
      ]),
      $this->generateDrupalServiceMock([
        'getType' => 'module',
        'getName' => 'module1',
        'getPath' => 'modules/contrib',
      ]),
      $this->generateDrupalServiceMock([
        'getType' => 'module',
        'getName' => 'module2',
        'getPath' => 'modules/custom',
      ]),
      $this->generateDrupalServiceMock([
        'getType' => 'module',
        'getName' => 'module3',
        'getPath' => 'core/modules',
      ]),
    ];
  }

}
