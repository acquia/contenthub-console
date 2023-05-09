<?php

namespace Acquia\Console\ContentHub\Tests;

use Acquia\Console\Cloud\Tests\Command\CommandTestHelperTrait;
use Acquia\Console\ContentHub\Client\ContentHubServiceInterface;
use Acquia\Console\ContentHub\Command\Helpers\DrupalServiceFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Class ContentHubCommandTestBase.
 *
 * TestBase for testing classes which extend ContentHubCommandBase.
 *
 * @package Acquia\Console\ContentHub\Tests
 */
class ContentHubCommandTestBase extends TestCase {

  use CommandTestHelperTrait;
  use ProphecyTrait;

  /**
   * Drupal Service Factory double.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $drupalServiceFactory;

  /**
   * Content Hub Service double.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $contentHubService;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->drupalServiceFactory = $this->prophesize(DrupalServiceFactory::class);
    $this->contentHubService = $this->prophesize(ContentHubServiceInterface::class);
  }

}
