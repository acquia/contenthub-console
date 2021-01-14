<?php

namespace Acquia\Console\ContentHub\Tests\Drupal\Command;

use Acquia\Console\ContentHub\Command\ContentHubAuditCheckUuid;
use Drupal\Core\Entity\EntityInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\NodeType;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class ContentHubAuditCheckUuidTest.
 *
 * @coversDefaultClass \Acquia\Console\ContentHub\Command\ContentHubAuditCheckUuid
 *
 * @group acquia-console-contenthub
 *
 * @package Acquia\Console\ContentHub\Tests\Command
 */
class ContentHubAuditCheckUuidTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
  ];

  /**
   * @covers ::execute
   */
  public function testWithMissingUuids() {
    $this->createNodeType(['type' => 'test1']);
    $test2 = $this->createNodeType(['type' => 'test2']);
    $test3 = $this->createNodeType(['type' => 'test3']);
    $this->setConfig($test2->getConfigDependencyName(), 'uuid', NULL);
    $this->setConfig($test3->getConfigDependencyName(), 'uuid', NULL);

    $cmd_tester = $this->getCommandTester();
    $cmd_tester->execute([]);
    $output = $cmd_tester->getDisplay();

    $this->assertNotContains('node.type.test1', $output, 'Config entity was not listed as it has uuid.');
    $this->assertContains('node.type.test2', $output, 'Config entity was listed as it has missing uuid.');
    $this->assertContains('node.type.test3', $output, 'Config entity was listed as it has missing uuid.');
  }

  /**
   * @covers ::execute
   */
  public function testWithFixOption() {
    $test1 = $this->createNodeType(['type' => 'test1']);
    $this->setConfig($test1->getConfigDependencyName(), 'uuid', NULL);
    // Reload node type.
    $this->reload($test1);
    $this->assertEmpty($test1->uuid(), 'Uuid is missing');

    $cmd_tester = $this->getCommandTester();
    $cmd_tester->execute([]);

    $this->reload($test1);
    $this->assertEmpty($test1->uuid(), 'Uuid is still missing.');

    $cmd_tester->execute(['--fix' => TRUE]);
    $this->reload($test1);
    $this->assertNotEmpty($test1->uuid(), 'New uuid has been generated.');
  }

  /**
   * Returns a new command tester object.
   *
   * @return \Symfony\Component\Console\Tester\CommandTester
   */
  protected function getCommandTester(): CommandTester {
    $cmd = new ContentHubAuditCheckUuid();
    return new CommandTester($cmd);
  }

  /**
   * Creates a new node type from the given values.
   *
   * @param array $values
   *   The values to set.
   *
   * @return \Drupal\node\Entity\NodeType
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createNodeType(array $values): NodeType {
    $node = NodeType::create($values);
    $node->save();
    return $node;
  }

  /**
   * Reloads an arbitrary entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to reload.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function reload(EntityInterface &$entity): void {
    $entity = \Drupal::entityTypeManager()->getStorage($entity->bundle())->load($entity->id());
  }

  /**
   * Modifies an arbitrary config key's value.
   *
   * @param string $name
   *   The name of the configuration.
   * @param string $key
   *   The key of the configuration.
   * @param $value
   *   The value to set.
   */
  protected function setConfig(string $name, string $key, $value): void {
    \Drupal::configFactory()->getEditable($name)->set($key, $value)->save();
  }

}
