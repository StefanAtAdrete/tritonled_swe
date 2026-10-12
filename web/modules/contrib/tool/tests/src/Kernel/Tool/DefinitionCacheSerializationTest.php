<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Tool;

use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\Tool\ToolDefinition;
use Drupal\tool\TypedData\InputDefinitionInterface;
use Drupal\tool\TypedData\MapInputDefinition;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the re-parented definition families survive the plugin cache.
 *
 * Tool definitions are serialized into the discovery cache backend; the
 * typed-data parentage (#3583017) and value storage (#3583018) must not
 * break that round trip, especially for nested map definitions whose
 * property definitions are objects of their own.
 *
 * @group tool
 */
#[RunTestsInSeparateProcesses]
class DefinitionCacheSerializationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'serialization',
    'tool',
    'tool_test',
    'user',
  ];

  /**
   * Tests a definition read back from the cache backend is intact.
   */
  public function testDefinitionsSurvivePluginCache(): void {
    $manager = $this->container->get('plugin.manager.tool');
    $manager->clearCachedDefinitions();
    $fresh = $manager->getDefinitions();
    $this->assertArrayHasKey('schema_matrix_tool', $fresh);

    // Read the cache item the manager just wrote and let the backend
    // unserialize it - the same medium a later request consumes.
    $cache_item = $this->container->get('cache.discovery')->get('tool_plugins');
    $this->assertNotFalse($cache_item, 'The plugin definitions were cached.');
    $cached = $cache_item->data['schema_matrix_tool'];
    $this->assertInstanceOf(ToolDefinition::class, $cached);

    $this->assertDefinitionIntact($cached);
    // An explicit serialize round trip covers backends that store live
    // objects (memory cache) and would hide serialization failures.
    $round_tripped = unserialize(serialize($fresh['schema_matrix_tool'])); // phpcs:ignore DrupalPractice.FunctionCalls.InsecureUnserialize -- Round-tripping a value this test itself just serialized.
    $this->assertDefinitionIntact($round_tripped);

    // The declared permission crosses both boundaries unchanged.
    $permission_definitions = [
      $cache_item->data['declared_permission_tool'],
      unserialize(serialize($fresh['declared_permission_tool'])), // phpcs:ignore DrupalPractice.FunctionCalls.InsecureUnserialize -- Round-tripping a value this test itself just serialized.
    ];
    foreach ($permission_definitions as $definition) {
      $this->assertInstanceOf(ToolDefinition::class, $definition);
      $this->assertSame('operate test tools,audit test tools', $definition->getPermission());
    }
  }

  /**
   * Asserts the nested structures of the matrix tool definition are intact.
   *
   * @param \Drupal\tool\Tool\ToolDefinition $definition
   *   A definition that crossed a serialization boundary.
   */
  private function assertDefinitionIntact(ToolDefinition $definition): void {
    $inputs = $definition->getInputDefinitions(TRUE);
    foreach ($inputs as $name => $input) {
      $this->assertInstanceOf(InputDefinitionInterface::class, $input, $name);
    }

    $nested = $inputs['nested_map'];
    $this->assertInstanceOf(MapInputDefinition::class, $nested);
    $properties = $nested->getPropertyDefinitions();
    $this->assertInstanceOf(MapInputDefinition::class, $properties['inner']);
    $this->assertNotEmpty($properties['inner']->getPropertyDefinitions());

    // The advertised schema built from the round-tripped definition matches
    // the one built from a freshly discovered definition.
    $serializer = $this->container->get('serializer');
    $fresh_definition = $this->freshDefinition();
    $this->assertEquals(
      $serializer->normalize($fresh_definition->getInputDefinition('nested_map'), 'json_schema'),
      $serializer->normalize($nested, 'json_schema'),
    );
  }

  /**
   * Discovers the matrix tool definition without any caches.
   *
   * @return \Drupal\tool\Tool\ToolDefinition
   *   The freshly discovered definition.
   */
  private function freshDefinition(): ToolDefinition {
    $manager = $this->container->get('plugin.manager.tool');
    $manager->useCaches(FALSE);
    try {
      $definition = $manager->getDefinition('schema_matrix_tool');
    }
    finally {
      $manager->useCaches(TRUE);
    }
    return $definition;
  }

}
