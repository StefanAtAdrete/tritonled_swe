<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Tool;

use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\Tool\ToolOperation;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the icon reference on tool definitions.
 *
 * The icon is stored as a core Icon API full icon ID ("pack_id:icon_id")
 * and resolved only by consumers: the definition carries the reference
 * verbatim, cores without the Icon API simply never resolve it, and an
 * unresolvable reference means no icon rather than an error.
 *
 * @group tool
 */
#[Group('tool')]
#[RunTestsInSeparateProcesses]
class ToolIconTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'tool',
    'tool_test',
  ];

  /**
   * Tests that the attribute icon reaches the definition verbatim.
   */
  public function testIconReferenceOnDefinition(): void {
    $manager = $this->container->get('plugin.manager.tool');
    $this->assertSame('tool_test:wrench', $manager->getDefinition('locked_input_tool')->getIcon());
    // A tool without a declared icon has none.
    $this->assertNull($manager->getDefinition('comprehensive_types_tool')->getIcon());
  }

  /**
   * Tests that the reference resolves through the core Icon API.
   */
  public function testIconResolvesThroughIconPackManager(): void {
    if (!$this->container->has('plugin.manager.icon_pack')) {
      $this->markTestSkipped('The core Icon API (11.1+) is not available.');
    }
    $reference = $this->container->get('plugin.manager.tool')->getDefinition('locked_input_tool')->getIcon();
    $icon = $this->container->get('plugin.manager.icon_pack')->getIcon($reference);
    $this->assertNotNull($icon);
    $this->assertSame('tool_test', $icon->getPackId());
    $this->assertSame('wrench', $icon->getIconId());
    $this->assertStringEndsWith('wrench.svg', (string) $icon->getSource());

    // An unknown reference resolves to no icon, not an error.
    $this->assertNull($this->container->get('plugin.manager.icon_pack')->getIcon('tool_test:no_such_icon'));
  }

  /**
   * Tests that every operation has a fallback icon in the tool pack.
   *
   * The Tool Explorer falls back to "tool:operation-<operation>" for tools
   * that declare no icon, so adding a ToolOperation case without adding a
   * matching icon must fail here.
   */
  public function testEveryOperationHasFallbackIcon(): void {
    if (!$this->container->has('plugin.manager.icon_pack')) {
      $this->markTestSkipped('The core Icon API (11.1+) is not available.');
    }
    $manager = $this->container->get('plugin.manager.icon_pack');
    foreach (ToolOperation::cases() as $operation) {
      $this->assertNotNull(
        $manager->getIcon('tool:operation-' . $operation->value),
        sprintf('The tool icon pack has no fallback icon for the %s operation.', $operation->value),
      );
    }
  }

}
