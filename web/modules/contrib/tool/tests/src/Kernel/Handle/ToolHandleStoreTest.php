<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Kernel\Handle;

use Drupal\KernelTests\KernelTestBase;
use Drupal\tool\Handle\HandleNotFoundException;
use Drupal\tool\Handle\InvalidHandleException;
use Drupal\tool\Handle\ToolHandleStore;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the ToolHandleStore service.
 *
 * @group tool
 * @coversDefaultClass \Drupal\tool\Handle\ToolHandleStore
 */
#[RunTestsInSeparateProcesses]
class ToolHandleStoreTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'tool',
    'user',
  ];

  /**
   * The handle store service.
   *
   * @var \Drupal\tool\Handle\ToolHandleStore
   */
  protected ToolHandleStore $handleStore;

  /**
   * A valid, non-generated handle that is not stored during these tests.
   */
  protected const UNKNOWN_HANDLE = 'handle:00000000-0000-4000-8000-000000000000';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->handleStore = $this->container->get('tool.handle_store');
  }

  /**
   * Tests storing and resolving a value.
   *
   * @covers ::store
   * @covers ::resolve
   */
  public function testStoreAndResolve(): void {
    $value = ['foo' => 'bar', 'nested' => [1, 2, 3]];
    $handle = $this->handleStore->store($value);

    $this->assertNotEmpty($handle);
    $this->assertTrue($this->handleStore->isHandle($handle));

    $resolved = $this->handleStore->resolve($handle);
    $this->assertEquals($value, $resolved);
  }

  /**
   * Tests that handles have the correct prefix.
   *
   * @covers ::store
   */
  public function testHandlePrefix(): void {
    $handle = $this->handleStore->store('test');
    $this->assertStringStartsWith('handle:', $handle);
  }

  /**
   * Tests updating a value at an existing handle.
   *
   * @covers ::store
   * @covers ::resolve
   */
  public function testUpdateExistingHandle(): void {
    $handle = $this->handleStore->store('original');
    $this->assertEquals('original', $this->handleStore->resolve($handle));

    // Update with the same handle.
    $returned_handle = $this->handleStore->store('updated', $handle);
    $this->assertEquals($handle, $returned_handle);
    $this->assertEquals('updated', $this->handleStore->resolve($handle));
  }

  /**
   * Tests the same object keeps its handle during the request.
   *
   * @covers ::store
   * @covers ::resolve
   */
  public function testStoreReusesHandleForSameObject(): void {
    $object = new \stdClass();
    $object->name = 'original';

    $handle = $this->handleStore->store($object);

    $object->name = 'updated';
    $returned_handle = $this->handleStore->store($object);

    $this->assertEquals($handle, $returned_handle);
    $this->assertEquals('updated', $this->handleStore->resolve($handle)->name);
  }

  /**
   * Tests resolved objects keep their original handle.
   *
   * @covers ::store
   * @covers ::resolve
   */
  public function testResolveAssociatesObjectWithHandle(): void {
    $object = new \stdClass();
    $object->name = 'stored';
    $handle = $this->handleStore->store($object);

    $resolved = $this->handleStore->resolve($handle);
    $resolved->name = 'updated';

    $returned_handle = $this->handleStore->store($resolved);

    $this->assertEquals($handle, $returned_handle);
    $this->assertEquals('updated', $this->handleStore->resolve($handle)->name);
  }

  /**
   * Tests deleted handles are no longer reused for associated objects.
   *
   * @covers ::store
   * @covers ::delete
   */
  public function testDeleteRemovesObjectHandleAssociation(): void {
    $object = new \stdClass();
    $handle = $this->handleStore->store($object);

    $this->handleStore->delete($handle);
    $new_handle = $this->handleStore->store($object);

    $this->assertNotEquals($handle, $new_handle);
  }

  /**
   * Tests has() method.
   *
   * @covers ::has
   */
  public function testHas(): void {
    $handle = $this->handleStore->store('value');
    $this->assertTrue($this->handleStore->has($handle));
    $this->assertFalse($this->handleStore->has(static::UNKNOWN_HANDLE));
  }

  /**
   * Tests delete() method.
   *
   * @covers ::delete
   */
  public function testDelete(): void {
    $handle = $this->handleStore->store('value');
    $this->assertTrue($this->handleStore->has($handle));

    $this->handleStore->delete($handle);
    $this->assertFalse($this->handleStore->has($handle));
    $this->expectException(HandleNotFoundException::class);
    $this->handleStore->resolve($handle);
  }

  /**
   * Tests isHandle() method.
   *
   * @covers ::isHandle
   * @covers ::hasHandlePrefix
   */
  public function testIsHandle(): void {
    $handle = $this->handleStore->store('value');

    $this->assertTrue($this->handleStore->hasHandlePrefix('handle:anything'));
    $this->assertTrue($this->handleStore->isHandle($handle));
    $this->assertFalse($this->handleStore->isHandle('handle:abc-123'));
    $this->assertFalse($this->handleStore->isHandle('handle:anything'));
    $this->assertFalse($this->handleStore->isHandle('not-a-handle'));
    $this->assertFalse($this->handleStore->isHandle(''));
    $this->assertFalse($this->handleStore->isHandle(123));
    $this->assertFalse($this->handleStore->isHandle(NULL));
  }

  /**
   * Tests storing different value types.
   *
   * @covers ::store
   * @covers ::resolve
   */
  public function testStoreVariousTypes(): void {
    // String.
    $handle1 = $this->handleStore->store('hello');
    $this->assertEquals('hello', $this->handleStore->resolve($handle1));

    // Array.
    $handle2 = $this->handleStore->store(['a', 'b']);
    $this->assertEquals(['a', 'b'], $this->handleStore->resolve($handle2));

    // Integer.
    $handle3 = $this->handleStore->store(42);
    $this->assertEquals(42, $this->handleStore->resolve($handle3));

    // Object.
    $obj = new \stdClass();
    $obj->name = 'test';
    $handle4 = $this->handleStore->store($obj);
    $this->assertEquals('test', $this->handleStore->resolve($handle4)->name);
  }

  /**
   * Tests that each store call generates a unique handle.
   *
   * @covers ::store
   */
  public function testUniqueHandles(): void {
    $handle1 = $this->handleStore->store('a');
    $handle2 = $this->handleStore->store('b');
    $handle3 = $this->handleStore->store('c');

    $this->assertNotEquals($handle1, $handle2);
    $this->assertNotEquals($handle2, $handle3);
    $this->assertNotEquals($handle1, $handle3);
  }

  /**
   * Tests storing NULL is prohibited.
   *
   * @covers ::store
   */
  public function testStoreRejectsNull(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('NULL values cannot be stored');
    $this->handleStore->store(NULL);
  }

  /**
   * Tests caller-supplied handles must be valid handles.
   *
   * @covers ::store
   */
  public function testStoreRejectsInvalidCallerSuppliedHandle(): void {
    $this->expectException(InvalidHandleException::class);
    $this->handleStore->store('value', 'anything');
  }

  /**
   * Tests handles with malformed IDs are rejected.
   *
   * @covers ::store
   * @covers ::isHandle
   */
  public function testStoreRejectsMalformedCallerSuppliedHandle(): void {
    $this->expectException(InvalidHandleException::class);
    $this->handleStore->store('value', 'handle:not-a-uuid');
  }

  /**
   * Tests only the leading handle prefix is treated as the storage prefix.
   *
   * @covers ::store
   * @covers ::handleToKey
   */
  public function testStoreRejectsHandleContainingAdditionalPrefix(): void {
    $this->expectException(InvalidHandleException::class);
    $this->handleStore->store('value', 'handle:00000000-0000-4000-8000-000000000000handle:again');
  }

  /**
   * Tests resolving a nonexistent handle throws.
   *
   * @covers ::resolve
   */
  public function testResolveNonexistent(): void {
    $this->expectException(HandleNotFoundException::class);
    $this->handleStore->resolve(static::UNKNOWN_HANDLE);
  }

  /**
   * Tests malformed handles are rejected by public APIs.
   *
   * @covers ::resolve
   * @covers ::handleToKey
   */
  public function testResolveMalformedHandle(): void {
    $this->expectException(InvalidHandleException::class);
    $this->handleStore->resolve('handle:does-not-exist');
  }

}
