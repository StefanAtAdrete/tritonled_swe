<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\TypedData;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\Plugin\DataType\TextData;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the module-prefixed adapter identifiers (#3582963).
 *
 * The 'text' data type ID deliberately stays generic — it is core-aligned
 * and other modules registering it are expected to mean the same thing.
 *
 * @group tool
 */
#[RunTestsInSeparateProcesses]
class PrefixedIdentifierTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'tool',
    'user',
  ];

  /**
   * Tests the text data type resolves and the adapter applies to it.
   */
  public function testTextDataTypeAndAdapter(): void {
    $manager = $this->container->get('typed_data_manager');
    $definition = $manager->getDefinition('text');
    $this->assertSame(TextData::class, $definition['class']);

    $adapter = $this->container->get('plugin.manager.tool.typed_data_adapter')
      ->getDefinitionFromDataDefinition(DataDefinition::create('text'));
    $this->assertSame('text', $adapter['id']);
  }

  /**
   * Tests the adapter plugin cache uses the prefixed collection key.
   */
  public function testAdapterCacheKeyIsPrefixed(): void {
    $manager = $this->container->get('plugin.manager.tool.typed_data_adapter');
    $manager->getDefinitions();

    $cached = $this->container->get('cache.discovery')->get('tool_typed_data_adapter_plugins');
    $this->assertNotFalse($cached);
  }

}
