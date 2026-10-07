<?php

declare(strict_types=1);

namespace Drupal\Tests\ai\Kernel\Guardrail;

use Drupal\ai\Entity\AiGuardrail;
use Drupal\ai\Guardrail\AiGuardrailInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that guardrail plugins survive form cache serialization.
 *
 * The guardrail add/edit form has an `#ajax` callback on its guardrail select,
 * so Drupal caches the form and its form state. The cached graph reaches the
 * guardrail plugin instance through the entity form's entity. Guardrail
 * plugins that hold injected services must therefore replace those services
 * with their service IDs on serialization, or the whole form cache write
 * fails and the settings subform can never be rendered.
 *
 * Every test walks all discoverable guardrail plugins so that guardrails added
 * later are covered without touching this file.
 *
 * @group ai
 * @group 3586620
 *
 * @covers \Drupal\ai\Guardrail\AiGuardrailPluginBase
 *
 * @runTestsInSeparateProcesses
 */
class GuardrailSerializationKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ai',
    'ai_test',
    'key',
    'file',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('ai_mock_provider_result');
    $this->installConfig(['system']);
  }

  /**
   * Returns every discoverable guardrail plugin ID.
   *
   * @return string[]
   *   The plugin IDs.
   */
  protected function guardrailPluginIds(): array {
    $ids = array_keys(
      \Drupal::service('plugin.manager.ai_guardrail')->getDefinitions()
    );
    $this->assertNotEmpty($ids, 'At least one guardrail plugin must exist.');
    return $ids;
  }

  /**
   * A bare guardrail plugin instance can be serialized and restored.
   */
  public function testPluginIsSerializable(): void {
    foreach ($this->guardrailPluginIds() as $plugin_id) {
      $plugin = \Drupal::service('plugin.manager.ai_guardrail')
        ->createInstance($plugin_id);

      $serialized = serialize($plugin);
      // Unrestricted on purpose: this mirrors what the form cache does.
      // phpcs:ignore DrupalPractice.FunctionCalls.InsecureUnserialize.InsecureUnserialize
      $restored = unserialize($serialized);

      $this->assertInstanceOf(AiGuardrailInterface::class, $restored, $plugin_id);
      $this->assertSame($plugin_id, $restored->getPluginId());

      // The serialized payload must not drag the container or a database
      // connection along with it. Both are symptoms reported in the issue.
      $this->assertStringNotContainsString(
        'Drupal\Core\DependencyInjection\Container',
        $serialized,
        sprintf('The container must not be embedded in serialized %s.', $plugin_id)
      );
      $this->assertStringNotContainsString(
        'Driver\Database',
        $serialized,
        sprintf('A database connection must not be embedded in serialized %s.', $plugin_id)
      );
    }
  }

  /**
   * Injected services are restored as live objects after unserialization.
   *
   * Replacing a service with its ID is only half the contract: the plugin has
   * to be fully functional again once it comes back out of the form cache.
   */
  public function testInjectedServicesAreRestored(): void {
    foreach ($this->guardrailPluginIds() as $plugin_id) {
      $plugin = \Drupal::service('plugin.manager.ai_guardrail')
        ->createInstance($plugin_id);

      // Unrestricted on purpose: this mirrors what the form cache does.
      // phpcs:ignore DrupalPractice.FunctionCalls.InsecureUnserialize.InsecureUnserialize
      $restored = unserialize(serialize($plugin));

      $reflection = new \ReflectionObject($restored);
      foreach ($reflection->getProperties() as $property) {
        // Skip the serialization trait's own bookkeeping properties.
        if (str_starts_with($property->getName(), '_')) {
          continue;
        }
        $this->assertTrue(
          $property->isInitialized($restored),
          sprintf(
            'Property $%s on %s must be initialized after unserialization.',
            $property->getName(),
            $plugin_id
          )
        );
      }

      // The guardrail must still answer its basic interface calls.
      $this->assertIsString($restored->label());
      $this->assertIsBool($restored->isAvailable());
    }
  }

  /**
   * The guardrail entity form and its form state can be cached.
   *
   * This is the exact operation that fails when the subform will not load:
   * FormBuilder serializes the built form together with the form state so the
   * AJAX rebuild can restore it.
   */
  public function testGuardrailFormIsCacheable(): void {
    foreach ($this->guardrailPluginIds() as $plugin_id) {
      $form_state = new FormState();
      $form = $this->buildGuardrailForm($plugin_id, $form_state);

      // Serializing must not throw; that throw is the reported bug.
      // Unrestricted on purpose: this mirrors what the form cache does.
      // phpcs:ignore DrupalPractice.FunctionCalls.InsecureUnserialize.InsecureUnserialize
      $restored = unserialize(serialize([$form, $form_state]));

      $this->assertIsArray($restored[0], $plugin_id);
      $this->assertArrayHasKey('guardrail_wrapper', $restored[0], $plugin_id);
    }
  }

  /**
   * The settings subform is actually built for every guardrail plugin.
   *
   * Guards against a regression where the subform silently renders empty
   * instead of failing loudly.
   */
  public function testSettingsSubformIsBuilt(): void {
    $manager = \Drupal::service('plugin.manager.ai_guardrail');
    $asserted = 0;

    foreach ($this->guardrailPluginIds() as $plugin_id) {
      // Guardrails without a plugin form legitimately have no settings.
      if (!$manager->createInstance($plugin_id) instanceof PluginFormInterface) {
        continue;
      }
      $asserted++;

      $form_state = new FormState();
      $form = $this->buildGuardrailForm($plugin_id, $form_state);

      $subform = $form['guardrail_wrapper']['guardrail_settings'];
      $elements = array_filter(
        array_keys($subform),
        static fn(string $key): bool => !str_starts_with($key, '#')
      );

      $this->assertNotEmpty(
        $elements,
        sprintf('The %s guardrail must render at least one settings element.', $plugin_id)
      );
    }

    $this->assertGreaterThan(
      0,
      $asserted,
      'At least one guardrail with a settings subform must have been checked.'
    );
  }

  /**
   * The guardrail entity keeps its plugin out of the serialized payload.
   *
   * The entity holds the plugin in a DefaultSingleLazyPluginCollection, which
   * ConfigEntityBase::__sleep() strips after writing the plugin's current
   * configuration back onto the entity. That is what keeps injected services
   * from reaching the form cache through the entity in the first place.
   */
  public function testEntityDoesNotSerializeThePlugin(): void {
    foreach ($this->guardrailPluginIds() as $plugin_id) {
      $entity = AiGuardrail::create([
        'id' => 'test_' . $plugin_id,
        'label' => 'Test ' . $plugin_id,
        'description' => 'Serialization test guardrail.',
        'guardrail' => $plugin_id,
        'guardrail_settings' => [],
      ]);
      // Instantiate the plugin so the collection is populated.
      $this->assertSame($plugin_id, $entity->getGuardrail()?->getPluginId());

      $serialized = serialize($entity);
      $this->assertStringNotContainsString(
        'pluginCollection',
        $serialized,
        sprintf('The plugin collection must not be serialized with the %s entity.', $plugin_id)
      );

      // Unrestricted on purpose: this mirrors what the form cache does.
      // phpcs:ignore DrupalPractice.FunctionCalls.InsecureUnserialize.InsecureUnserialize
      $restored = unserialize($serialized);
      $this->assertSame(
        $plugin_id,
        $restored->getGuardrail()?->getPluginId(),
        sprintf('The %s plugin must be re-instantiated after unserialization.', $plugin_id)
      );
    }
  }

  /**
   * Builds the guardrail add form for a given guardrail plugin.
   *
   * @param string $plugin_id
   *   The guardrail plugin ID to select on the form.
   * @param \Drupal\Core\Form\FormState $form_state
   *   The form state to build with.
   *
   * @return array
   *   The built form array.
   */
  protected function buildGuardrailForm(string $plugin_id, FormState $form_state): array {
    $entity = AiGuardrail::create([
      'id' => 'test_' . $plugin_id,
      'label' => 'Test ' . $plugin_id,
      'description' => 'Serialization test guardrail.',
      'guardrail' => $plugin_id,
      'guardrail_settings' => [],
    ]);

    $form_object = \Drupal::entityTypeManager()
      ->getFormObject('ai_guardrail', 'add');
    $form_object->setEntity($entity);

    return \Drupal::formBuilder()->buildForm($form_object, $form_state);
  }

}
