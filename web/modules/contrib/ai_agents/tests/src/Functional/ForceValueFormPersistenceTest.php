<?php

declare(strict_types=1);

namespace Drupal\Tests\ai_agents\Functional;

use Drupal\ai_agents\Entity\AiAgent;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that a force_value tool restriction set in the agent form is saved.
 *
 * @group ai_agents
 */
#[RunTestsInSeparateProcesses]
final class ForceValueFormPersistenceTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'key',
    'ai',
    'ai_test',
    'ai_agents',
  ];

  /**
   * Plugin id of the tool whose "entity_type" argument is forced.
   */
  private const TOOL_PLUGIN_ID = 'ai_agent:list_config_entities';

  /**
   * The tool argument that is forced.
   */
  private const FORCED_PROPERTY = 'entity_type';

  /**
   * A visible forced value submitted via the form is saved to the config.
   *
   * @see \Drupal\ai_agents\Form\AiAgentForm::save()
   */
  public function testVisibleForcedValueIsPersisted(): void {
    $restriction = $this->submitForcedValue(hide_property: FALSE);

    $this->assertSame('force_value', $restriction['action']);
    $this->assertSame(['user_role'], $restriction['values']);
    // hide_property and not_break are stored as their raw checkbox integers.
    $this->assertFalse((bool) $restriction['hide_property']);
    $this->assertFalse((bool) $restriction['not_break']);
  }

  /**
   * A hidden forced value submitted via the form is saved to the config.
   *
   * @see \Drupal\ai_agents\Form\AiAgentForm::save()
   */
  public function testHiddenForcedValueIsPersisted(): void {
    $restriction = $this->submitForcedValue(hide_property: TRUE);

    $this->assertSame('force_value', $restriction['action']);
    $this->assertSame(['user_role'], $restriction['values']);
    $this->assertTrue((bool) $restriction['hide_property']);
    $this->assertFalse((bool) $restriction['not_break']);
  }

  /**
   * Submits a force_value restriction through the agent form.
   *
   * @param bool $hide_property
   *   Whether to check the "hide property" box for the restriction.
   *
   * @return array
   *   The persisted restriction for the forced property.
   */
  private function submitForcedValue(bool $hide_property): array {
    // Enable the tool so its usage sub-form is present on the edit form.
    AiAgent::create([
      'id' => 'test_agent',
      'label' => 'Force value test agent',
      'description' => 'Test agent.',
      'system_prompt' => 'Prompt.',
      'tools' => [self::TOOL_PLUGIN_ID => TRUE],
      'orchestration_agent' => FALSE,
      'triage_agent' => FALSE,
      'max_loops' => 3,
      'masquerade_roles' => [],
      'exclude_users_role' => FALSE,
    ])->save();

    $this->drupalLogin($this->rootUser);
    $this->drupalGet(AiAgent::load('test_agent')->toUrl('edit-form'));

    $field = 'tool_usage[' . self::TOOL_PLUGIN_ID . '][property_restrictions][' . self::FORCED_PROPERTY . ']';
    $edit = [
      $field . '[action]' => 'force_value',
      $field . '[values]' => 'user_role',
    ];
    if ($hide_property) {
      $edit[$field . '[hide_property]'] = TRUE;
    }
    $this->submitForm($edit, 'Save');

    $limits = AiAgent::load('test_agent')->get('tool_usage_limits');
    $restriction = $limits[self::TOOL_PLUGIN_ID][self::FORCED_PROPERTY] ?? NULL;
    $this->assertIsArray($restriction);

    return $restriction;
  }

}
