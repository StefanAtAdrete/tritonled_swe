<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Tool;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\Tool\ToolDefinition;
use Drupal\tool\Tool\ToolManager;
use Drupal\tool\Exception\InputException;
use Drupal\tool\TypedData\EntityInputDefinition;
use Drupal\tool\TypedData\EntityOutputDefinition;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests typed-data value storage on the typed inputs and outputs traits.
 *
 * Values are stored as typed data since #3583018; the context accessors
 * are a deprecated compatibility layer that mints core contexts on demand.
 *
 * @group tool
 * @coversDefaultClass \Drupal\tool\TypedInputsTrait
 */
#[RunTestsInSeparateProcesses]
class TypedDataStorageTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'field',
    'system',
    'tool',
    'tool_test',
    'user',
  ];

  /**
   * The tool plugin manager.
   */
  protected ToolManager $toolManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->toolManager = $this->container->get('plugin.manager.tool');
  }

  /**
   * Tests getInputData() is an inspection accessor.
   *
   * It carries the stored value but never the stored object, so mutating
   * the result does not change the input and cannot bypass the setter's
   * guards and refinement processing.
   *
   * @covers ::getInputData
   */
  public function testGetInputDataIsInspectionOnly(): void {
    $tool = $this->toolManager->createInstance('numeric_input_tool');
    $tool->setInputValue('number', 5);

    $data = $tool->getInputData('number');
    $this->assertInstanceOf(TypedDataInterface::class, $data);
    $this->assertSame(5, $data->getValue());
    $this->assertNotSame($data, $tool->getInputData('number'), 'Each read builds fresh typed data.');

    $data->setValue(9);
    $this->assertSame(5, $tool->getInputValue('number'), 'Mutating the inspection object does not change the input.');
  }

  /**
   * Tests the inspection accessor shares contained objects.
   *
   * The wrapper is detached, not a deep copy: replacing its value does
   * not change the input, but an object value is the shared instance, so
   * mutating the contained entity remains visible through the tool.
   *
   * @covers ::getInputData
   */
  public function testInspectionAccessorSharesContainedObjects(): void {
    $this->installEntitySchema('user');
    $account = User::create(['name' => 'shared']);
    $tool = $this->toolManager->createInstance('plain_structure_tool');
    // Added on the instance rather than declared on the fixture, so the
    // discovery-wide schema acceptance matrix never feeds it samples.
    $tool->setInputDefinitions($tool->getInputDefinitions(TRUE) + [
      'subject' => new InputDefinition(
        data_type: 'entity',
        label: new TranslatableMarkup('Subject'),
        description: new TranslatableMarkup('A bare entity input.'),
        required: FALSE,
      ),
    ]);
    $tool->setInputValue('subject', $account);

    $data = $tool->getInputData('subject');
    $this->assertSame($account, $data->getValue(), 'The contained entity is the shared instance.');

    $data->getValue()->set('name', 'mutated');
    $this->assertSame('mutated', $tool->getInputValue('subject')->getAccountName(), 'Entity mutations remain visible through the tool.');

    $data->setValue(User::create(['name' => 'other']));
    $this->assertSame($account, $tool->getInputValue('subject'), 'Replacing the wrapper value does not change the input.');
  }

  /**
   * Tests getInputData() for an unset input carries the default value.
   *
   * @covers ::getInputData
   */
  public function testGetInputDataForUnsetInputCarriesDefault(): void {
    $tool = $this->toolManager->createInstance('numeric_input_tool');
    $tool->getInputDefinition('number')->setDefaultValue(7);

    $data = $tool->getInputData('number');
    $this->assertSame(7, $data->getValue());
    $this->assertFalse($tool->hasInputValue('number'), 'Reading does not store.');
    $this->assertNotSame($data, $tool->getInputData('number'), 'Each read builds fresh typed data.');
  }

  /**
   * Tests an explicit NULL is stored as a value-less marker.
   *
   * Core Context never stored a NULL either: it produced a value-less
   * context whose reads resolved through the definition default. The
   * marker keeps the input the executable source - an explicit NULL does
   * not fall through to a configuration value.
   *
   * @covers ::setInputValue
   */
  public function testExplicitNullStoresValuelessMarker(): void {
    $tool = $this->toolManager->createInstance('numeric_input_tool');
    $tool->setConfiguration(['number' => 21]);
    $tool->setInputValue('number', 5);
    $this->assertTrue($tool->hasInputValue('number'));

    $tool->setInputValue('number', NULL);
    $this->assertTrue($tool->hasInputValue('number'), 'An explicit NULL still counts as a set input.');

    // With a definition default, all reads resolve to it.
    $tool->getInputDefinition('number')->setDefaultValue(3);
    $this->assertSame(3, $tool->getInputValue('number'));
    $this->assertSame(3, $tool->getExecutableValue('number'));
    $this->assertSame(['number' => 3], $tool->getInputValues());

    // Without one, the required input throws on a value read and is
    // reported as a violation by validation.
    $tool->getInputDefinition('number')->setDefaultValue(NULL);
    $this->assertGreaterThan(0, $tool->validateInputs()->count());
    $this->expectException(ContextException::class);
    $tool->getInputValue('number');
  }

  /**
   * Tests structured data types declared on the plain definition classes.
   *
   * A 'map' or 'list' data type on the scalar InputDefinition and
   * OutputDefinition classes must build typed data of the matching core
   * definition class (#3583018).
   *
   * @covers ::setInputValue
   * @covers \Drupal\tool\TypedData\EffectiveDataDefinition::createFrom
   */
  public function testPlainStructuredDefinitions(): void {
    $tool = $this->toolManager->createInstance('plain_structure_tool');
    $tool->setInputValue('options', ['color' => 'blue']);
    $tool->setInputValue('rows', ['a', 'b']);

    $this->assertSame(['color' => 'blue'], $tool->getInputValue('options'));
    $this->assertSame(['a', 'b'], $tool->getInputValue('rows'));
    $this->assertCount(0, $tool->validateInputs());

    $tool->setOutputValue('summary', ['count' => 2]);
    $tool->setOutputValue('items', ['a', 'b']);
    $this->assertCount(0, $tool->validateOutputs());
    $this->assertSame(['count' => 2], $tool->getOutputValue('summary'));

    // A structurally impossible value still fails as invalid input rather
    // than crashing typed data creation.
    try {
      $tool->setInputValue('options', 'not-a-map');
      $this->fail('A string is not storable under a map data type.');
    }
    catch (InputException $e) {
      $this->assertStringContainsString('options', $e->getMessage());
    }
  }

  /**
   * Tests map children that are list definitions.
   *
   * A list property, declared as the list a multiple property converts to
   * (#3583049), must stay addressable through the map's effective
   * definition.
   *
   * @covers ::validateInputs
   * @covers \Drupal\tool\TypedOutputsTrait::validateOutputs
   */
  public function testStructuredChildren(): void {
    $tool = $this->toolManager->createInstance('plain_structure_tool');
    $tool->setInputValue('profile', ['tags' => ['a', 'b']]);
    $this->assertCount(0, $tool->validateInputs());
    $this->assertSame(['tags' => ['a', 'b']], $tool->getInputValue('profile'));

    $tool->setOutputValue('stats', ['tags' => ['a', 'b']]);
    $this->assertCount(0, $tool->validateOutputs());
  }

  /**
   * Tests getOutputData() and the output value resolution.
   *
   * @covers \Drupal\tool\TypedOutputsTrait::getOutputData
   * @covers \Drupal\tool\TypedOutputsTrait::getOutputValue
   */
  public function testOutputDataAndValueResolution(): void {
    $tool = $this->toolManager->createInstance('numeric_input_tool');

    // A required output that was never set throws on a value read but
    // still yields fresh typed data.
    $this->assertNull($tool->getOutputData('doubled')->getValue());
    try {
      $tool->getOutputValue('doubled');
      $this->fail('A required output without a value must throw.');
    }
    catch (ContextException) {
      // Expected.
    }

    $tool->setOutputValue('doubled', 10);
    $this->assertSame(10, $tool->getOutputData('doubled')->getValue());
    $this->assertSame(10, $tool->getOutputValue('doubled'));
    $this->assertSame(['doubled' => 10], $tool->getOutputValues());

    // The output accessor is inspection-only as well.
    $tool->getOutputData('doubled')->setValue(99);
    $this->assertSame(10, $tool->getOutputValue('doubled'));
  }

  /**
   * Tests the entity short-circuit in validateInputValue() is unchanged.
   *
   * Entity inputs are type-checked only - a value that is not a content
   * entity is one violation, an entity passes without running entity
   * validation - exactly as before typed-data storage (#3583018).
   *
   * @covers ::validateInputValue
   */
  public function testEntityValidationParity(): void {
    $this->installEntitySchema('user');
    $tool = $this->toolManager->createInstance('numeric_input_tool');
    $definition = new EntityInputDefinition(
      'entity:user',
      new TranslatableMarkup('Account'),
      new TranslatableMarkup('The account.'),
    );

    $violations = $tool->validateInputValue($definition, 'not-an-entity');
    $this->assertCount(1, $violations);
    $this->assertSame('The Account input must be an entity.', (string) $violations->get(0)->getMessage());

    $account = User::create(['name' => 'validator', 'mail' => 'validator@example.com']);
    $this->assertCount(0, $tool->validateInputValue($definition, $account));
  }

  /**
   * Tests an entity output holding the wrong entity type is a violation.
   *
   * The rebuilt effective definition keeps core EntityDataDefinition's
   * dynamic EntityType constraint, which the families' author-only
   * getConstraints() does not report. Only the declared-type checks run, so
   * the mismatch is the one violation reported.
   *
   * @covers \Drupal\tool\TypedOutputsTrait::validateOutputs
   */
  public function testEntityOutputTypeMismatch(): void {
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
    $tool = $this->toolManager->createInstance('numeric_input_tool');
    $definition = $tool->getPluginDefinition();
    $this->assertInstanceOf(ToolDefinition::class, $definition);
    $definition->addOutputDefinition('subject', new EntityOutputDefinition(
      'entity:entity_test',
      new TranslatableMarkup('Subject'),
      new TranslatableMarkup('The subject entity.'),
      FALSE,
    ));

    // The fixture's own required output is set, so only the subject is
    // left to report.
    $tool->setOutputValue('doubled', 2);
    $tool->setOutputValue('subject', User::create(['name' => 'wrong-type']));
    $violations = $tool->validateOutputs();
    // Only the declared-type checks run, so the type mismatch is the one
    // violation reported.
    $this->assertCount(1, $violations);
    $messages = [];
    foreach ($violations as $violation) {
      $messages[] = (string) $violation->getMessage();
    }
    $this->assertStringContainsString('entity_test', implode(' ', $messages));
  }

  /**
   * Tests entity outputs skip the entity type's own constraints.
   *
   * The user entity type carries the EntityChanged constraint, which
   * rejects an entity object older than what storage holds. Output
   * validation only runs the declared-type checks, so a stale but
   * otherwise valid entity passes.
   *
   * @covers \Drupal\tool\TypedOutputsTrait::validateOutputs
   */
  public function testEntityOutputSkipsEntityLevelConstraints(): void {
    $this->installEntitySchema('user');
    $tool = $this->toolManager->createInstance('numeric_input_tool');
    $definition = $tool->getPluginDefinition();
    $this->assertInstanceOf(ToolDefinition::class, $definition);
    $definition->addOutputDefinition('subject', new EntityOutputDefinition(
      'entity:user',
      new TranslatableMarkup('Subject'),
      new TranslatableMarkup('The subject user.'),
      FALSE,
    ));

    $account = User::create(['name' => 'stale']);
    $account->save();
    $stale = clone $account;
    $account->setChangedTime($account->getChangedTime() + 10);
    $account->save();

    // The fixture's own required output is set, so only the subject is
    // left to report.
    $tool->setOutputValue('doubled', 2);
    $tool->setOutputValue('subject', $stale);
    $violations = $tool->validateOutputs();
    $this->assertCount(0, $violations, (string) $violations);
  }

}
