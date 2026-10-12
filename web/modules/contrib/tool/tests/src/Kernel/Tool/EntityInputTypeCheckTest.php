<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Tool;

use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\tool\Tool\ToolInterface;
use Drupal\tool\TypedData\EntityInputDefinition;
use Drupal\tool\TypedData\InputDefinition;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that an entity input checks its declared type and constraints.
 *
 * @group tool
 */
#[Group('tool')]
#[RunTestsInSeparateProcesses]
final class EntityInputTypeCheckTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'serialization',
    'tool',
    'tool_test',
    'node',
    'field',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();
    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();
  }

  /**
   * Content and config entities of the declared type are accepted.
   */
  public function testDeclaredTypeIsAccepted(): void {
    $tool = $this->tool();
    $user = new EntityInputDefinition('entity:user', 'Account', 'An account.');
    $role = new EntityInputDefinition('entity:user_role', 'Role', 'A role.');

    $this->assertCount(0, $tool->validateInputValue($user, User::create(['name' => 'sample'])));
    $this->assertCount(0, $tool->validateInputValue($role, Role::create(['id' => 'sample', 'label' => 'Sample'])), 'A config entity satisfies its own "entity:TYPE" input.');
  }

  /**
   * An entity of another type is rejected at validation, not at execution.
   */
  public function testOtherTypeIsRejected(): void {
    $tool = $this->tool();
    $user = new EntityInputDefinition('entity:user', 'Account', 'An account.');
    $role = new EntityInputDefinition('entity:user_role', 'Role', 'A role.');

    $violations = $tool->validateInputValue($user, Node::create(['type' => 'article', 'title' => 'Sample']));
    $this->assertCount(1, $violations);
    $this->assertStringContainsString('must be of type user', strip_tags((string) $violations->get(0)->getMessage()));

    $violations = $tool->validateInputValue($role, User::create(['name' => 'sample']));
    $this->assertCount(1, $violations);
    $this->assertStringContainsString('must be of type user_role', strip_tags((string) $violations->get(0)->getMessage()));

    $violations = $tool->validateInputValue($user, 'sample');
    $this->assertSame('The Account input must be an entity.', (string) $violations->get(0)->getMessage());
  }

  /**
   * A bundle in the data type is checked too.
   */
  public function testBundleIsChecked(): void {
    $tool = $this->tool();
    $article = new EntityInputDefinition('entity:node:article', 'Article', 'An article.');

    $this->assertCount(0, $tool->validateInputValue($article, Node::create(['type' => 'article', 'title' => 'Sample'])));
    $violations = $tool->validateInputValue($article, Node::create(['type' => 'page', 'title' => 'Sample']));
    $this->assertCount(1, $violations);
    $this->assertStringContainsString('must be of bundle article', strip_tags((string) $violations->get(0)->getMessage()));
  }

  /**
   * The constraints the author declared on an entity input run too.
   *
   * A Bundle constraint narrows a type-only input, and is reported once
   * although the effective definition carries it as well. A constraint
   * other than the type checks narrows even the bare "entity" type.
   */
  public function testDeclaredConstraintsAreChecked(): void {
    $tool = $this->tool();
    $article = new EntityInputDefinition('entity:node', 'Article', 'An article.', constraints: [
      'Bundle' => ['bundle' => ['article']],
    ]);

    $this->assertCount(0, $tool->validateInputValue($article, Node::create(['type' => 'article', 'title' => 'Sample'])));
    $violations = $tool->validateInputValue($article, Node::create(['type' => 'page', 'title' => 'Sample']));
    $this->assertCount(1, $violations);
    $this->assertStringContainsString('must be of bundle article', strip_tags((string) $violations->get(0)->getMessage()));

    $promotable = new InputDefinition('entity', 'Promotable', 'An entity with a promote field.', constraints: [
      'EntityHasField' => ['field_name' => 'promote'],
    ]);

    $this->assertCount(0, $tool->validateInputValue($promotable, Node::create(['type' => 'page', 'title' => 'Sample'])));
    $violations = $tool->validateInputValue($promotable, User::create(['name' => 'sample']));
    $this->assertCount(1, $violations);
    $this->assertStringContainsString('must have the promote field', strip_tags((string) $violations->get(0)->getMessage()));
    $violations = $tool->validateInputValue($promotable, Role::create(['id' => 'sample', 'label' => 'Sample']));
    $this->assertCount(1, $violations);
    $this->assertStringContainsString('does not support fields', (string) $violations->get(0)->getMessage());
  }

  /**
   * Count on a multiple entity input binds the list, never an entity in it.
   *
   * Each item is validated against the item definition, which must not
   * carry the list's Count: an entity is not countable. A wrong-typed entity
   * among the items still fails the type check, at its position.
   */
  public function testCountOnMultipleInputSkipsItems(): void {
    $tool = $this->tool();
    $articles = new EntityInputDefinition('entity:node', 'Articles', 'Some articles.', multiple: TRUE, constraints: [
      'Count' => ['min' => 1, 'max' => 5],
    ]);
    $first = Node::create(['type' => 'article', 'title' => 'First']);
    $second = Node::create(['type' => 'article', 'title' => 'Second']);

    $violations = $tool->validateInputValue($articles, [$first, $second]);
    $this->assertCount(0, $violations, (string) $violations);

    $violations = $tool->validateInputValue($articles, [$first, User::create(['name' => 'sample'])]);
    $this->assertCount(1, $violations, (string) $violations);
    $message = strip_tags((string) $violations->get(0)->getMessage());
    $this->assertStringStartsWith('(item 1) ', $message);
    $this->assertStringContainsString('must be of type node', $message);
  }

  /**
   * The entity type's own constraints do not run on an entity input.
   *
   * The user entity type carries the EntityChanged constraint, which
   * rejects an entity object older than what storage holds. Input
   * validation runs only the declared-type checks and the declared
   * constraints, so a stale but otherwise valid entity passes, as it does
   * for an entity output.
   *
   * @see TypedDataStorageTest::testEntityOutputSkipsEntityLevelConstraints()
   */
  public function testEntityLevelConstraintsAreSkipped(): void {
    $account = User::create(['name' => 'stale']);
    $account->save();
    $stale = clone $account;
    $account->setChangedTime($account->getChangedTime() + 10);
    $account->save();

    // EntityChanged alone rejects the stale object.
    $typed_data_manager = $this->container->get('typed_data_manager');
    $entity_changed = $typed_data_manager->getValidationConstraintManager()->create('EntityChanged', []);
    $this->assertCount(1, $typed_data_manager->getValidator()->validate($stale->getTypedData(), [$entity_changed]));

    $user = new EntityInputDefinition('entity:user', 'Account', 'An account.');
    $violations = $this->tool()->validateInputValue($user, $stale);
    $this->assertCount(0, $violations, (string) $violations);
  }

  /**
   * An entity wrapped as typed data does not slip past the declared type.
   *
   * The value is validated as typed data built from the input definition,
   * which does not unwrap a typed data value, so the wrapper is rejected as
   * a value that is not an entity.
   */
  public function testWrappedEntityIsChecked(): void {
    $tool = $this->tool();
    $user = new EntityInputDefinition('entity:user', 'Account', 'An account.');
    $node = EntityAdapter::createFromEntity(Node::create(['type' => 'article', 'title' => 'Sample']));

    $violations = $tool->validateInputValue($user, $node);
    $this->assertCount(1, $violations);
    $this->assertSame('The Account input must be an entity.', (string) $violations->get(0)->getMessage());
  }

  /**
   * The bare "entity" type accepts content and config entities.
   */
  public function testGenericEntityAcceptsAnyType(): void {
    $tool = $this->tool();
    $any = new InputDefinition('entity', 'Entity', 'Any entity.');

    $this->assertCount(0, $tool->validateInputValue($any, User::create(['name' => 'sample'])));
    $this->assertCount(0, $tool->validateInputValue($any, Role::create(['id' => 'sample', 'label' => 'Sample'])));
    $this->assertCount(0, $tool->validateInputValue($any, Node::create(['type' => 'page', 'title' => 'Sample'])));
  }

  /**
   * Any tool instance; validateInputValue() takes the definition to check.
   */
  private function tool(): ToolInterface {
    return $this->container->get('plugin.manager.tool')->createInstance('no_requirements_tool');
  }

}
