<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Drush;

use Drupal\tool\Drush\RequirementStatus;
use Drupal\tool\Drush\UnmetFilter;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the requirement status probe behind the Drush commands.
 *
 * @group tool
 */
#[RunTestsInSeparateProcesses]
class RequirementStatusTest extends DrushCommandTestBase {

  /**
   * Tests a tool whose requirements are met.
   */
  public function testMetRequirements(): void {
    $status = RequirementStatus::forTool($this->toolManager, 'no_requirements_tool');

    $this->assertTrue($status->met);
    $this->assertNull($status->message);
    $this->assertSame('Met', $status->label());
    $this->assertSame(['met' => TRUE, 'message' => NULL], $status->toArray());
  }

  /**
   * Tests a tool that throws RequirementsException.
   */
  public function testUnmetRequirements(): void {
    $status = RequirementStatus::forTool($this->toolManager, 'unmet_requirements_tool');

    $this->assertFalse($status->met);
    $this->assertSame('Test API key is not configured.', $status->message);
    $this->assertSame('Test API key is not configured.', $status->label());
  }

  /**
   * Tests a tool whose create() throws.
   */
  public function testInstantiationFailureReportsAsUnmet(): void {
    $status = RequirementStatus::forTool($this->toolManager, 'broken_constructor_tool');

    $this->assertFalse($status->met);
    $this->assertSame('Could not instantiate tool: Service "tool_test.missing" not found.', $status->message);
  }

  /**
   * Tests which statuses each --unmet value keeps.
   */
  public function testUnmetFilter(): void {
    $met = RequirementStatus::forTool($this->toolManager, 'no_requirements_tool');
    $unmet = RequirementStatus::forTool($this->toolManager, 'unmet_requirements_tool');

    $this->assertTrue(UnmetFilter::Show->keeps($met));
    $this->assertTrue(UnmetFilter::Show->keeps($unmet));
    $this->assertTrue(UnmetFilter::Hide->keeps($met));
    $this->assertFalse(UnmetFilter::Hide->keeps($unmet));
    $this->assertFalse(UnmetFilter::Only->keeps($met));
    $this->assertTrue(UnmetFilter::Only->keeps($unmet));
    $this->assertSame('show, hide, only', UnmetFilter::describeValues());
  }

}
