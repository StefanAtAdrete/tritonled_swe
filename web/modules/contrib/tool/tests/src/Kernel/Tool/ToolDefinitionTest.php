<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Tool;

use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\Tool\ToolDefinition;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests ToolDefinition behavior.
 *
 * @group tool
 * @coversDefaultClass \Drupal\tool\Tool\ToolDefinition
 */
#[RunTestsInSeparateProcesses]
class ToolDefinitionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'tool',
    'tool_test',
    'user',
  ];

  /**
   * Tests get() returns NULL for uninitialized and unknown properties.
   *
   * Regression test: get() must use isset() semantics so declared-but-
   * uninitialized typed properties (e.g. a definition without a 'deriver')
   * return NULL as documented instead of throwing "Typed property must not
   * be accessed before initialization".
   *
   * @covers ::get
   */
  public function testGetReturnsNullForUninitializedProperties(): void {
    $definition = $this->container->get('plugin.manager.tool')->getDefinition('greeting_tool');
    $this->assertInstanceOf(ToolDefinition::class, $definition);

    // Set properties return their values.
    $this->assertSame('greeting_tool', $definition->get('id'));

    // greeting_tool declares no deriver; the typed property is uninitialized
    // and must read as NULL, not throw.
    $this->assertNull($definition->get('deriver'));

    // Unknown properties also return NULL.
    $this->assertNull($definition->get('does_not_exist'));
  }

}
