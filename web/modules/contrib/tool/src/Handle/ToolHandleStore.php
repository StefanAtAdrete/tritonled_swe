<?php

declare(strict_types=1);

namespace Drupal\tool\Handle;

use Drupal\Component\Uuid\Uuid;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;

/**
 * Stores values and provides handles for referencing them across tool calls.
 *
 * This service allows tools to pass non-NULL complex values (entities, arrays,
 * objects) between tool calls without serializing them to the invoker. The
 * invoker receives a handle string for use in subsequent tool calls.
 */
class ToolHandleStore implements ToolHandleStoreInterface {

  /**
   * The tempstore collection name.
   */
  const COLLECTION = 'tool_handles';

  /**
   * The prefix for handle strings.
   */
  const HANDLE_PREFIX = 'handle:';

  /**
   * Request-local object-to-handle associations.
   *
   * @var \WeakMap<object, string>
   */
  protected \WeakMap $objectHandles;

  /**
   * Constructs a ToolHandleStore.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   The private temp store factory.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   The UUID generator.
   */
  public function __construct(
    protected readonly PrivateTempStoreFactory $tempStoreFactory,
    protected readonly UuidInterface $uuid,
  ) {
    $this->objectHandles = new \WeakMap();
  }

  /**
   * Stores a value and returns a handle to reference it.
   *
   * If an existing handle is provided, the value at that handle is updated.
   *
   * @param mixed $value
   *   The non-NULL value to store.
   * @param string|null $handle
   *   (optional) An existing handle to update. If NULL, a new handle is
   *   generated.
   *
   * @return string
   *   The handle that can be used to resolve the value later.
   */
  public function store(mixed $value, ?string $handle = NULL): string {
    if ($value === NULL) {
      throw new \InvalidArgumentException('NULL values cannot be stored as handles.');
    }

    if ($handle === NULL && is_object($value)) {
      $handle = $this->objectHandles[$value] ?? NULL;
    }

    if ($handle === NULL) {
      $handle = self::HANDLE_PREFIX . $this->uuid->generate();
    }
    elseif (!$this->isHandle($handle)) {
      throw new InvalidHandleException(sprintf('The handle "%s" is not a valid handle.', $handle));
    }

    $this->getStore()->set($this->handleToKey($handle), $value);
    if (is_object($value)) {
      $this->objectHandles[$value] = $handle;
    }
    return $handle;
  }

  /**
   * Resolves a handle back to its stored value.
   *
   * @param string $handle
   *   The handle string.
   *
   * @return mixed
   *   The stored value.
   *
   * @throws \Drupal\tool\Handle\HandleNotFoundException
   *   Thrown if the handle does not exist or has expired.
   */
  public function resolve(string $handle): mixed {
    $value = $this->getStore()->get($this->handleToKey($handle));
    if ($value === NULL) {
      throw new HandleNotFoundException(sprintf('The handle "%s" does not exist or has expired.', $handle));
    }
    if (is_object($value)) {
      $this->objectHandles[$value] = $handle;
    }
    return $value;
  }

  /**
   * Checks whether a handle exists in the store.
   *
   * @param string $handle
   *   The handle string.
   *
   * @return bool
   *   TRUE if the handle exists, FALSE otherwise.
   */
  public function has(string $handle): bool {
    return $this->getStore()->get($this->handleToKey($handle)) !== NULL;
  }

  /**
   * Deletes a stored handle.
   *
   * @param string $handle
   *   The handle string.
   */
  public function delete(string $handle): void {
    $this->getStore()->delete($this->handleToKey($handle));
    $this->removeObjectHandleAssociations($handle);
  }

  /**
   * {@inheritdoc}
   */
  public function hasHandlePrefix(mixed $value): bool {
    return is_string($value) && str_starts_with($value, self::HANDLE_PREFIX);
  }

  /**
   * {@inheritdoc}
   */
  public function isHandle(mixed $value): bool {
    return $this->getHandleId($value) !== NULL;
  }

  /**
   * Gets the private temp store.
   *
   * @return \Drupal\Core\TempStore\PrivateTempStore
   *   The temp store instance.
   */
  protected function getStore(): PrivateTempStore {
    return $this->tempStoreFactory->get(self::COLLECTION);
  }

  /**
   * Extracts and validates the ID portion of a handle.
   *
   * @param mixed $value
   *   The value to inspect.
   *
   * @return string|null
   *   The handle ID, or NULL if the value is not a valid handle.
   */
  protected function getHandleId(mixed $value): ?string {
    if (!$this->hasHandlePrefix($value)) {
      return NULL;
    }

    $handle_id = substr($value, strlen(self::HANDLE_PREFIX));
    return Uuid::isValid($handle_id) ? $handle_id : NULL;
  }

  /**
   * Converts a handle to a storage key.
   *
   * @param string $handle
   *   The handle string.
   *
   * @return string
   *   The storage key.
   */
  protected function handleToKey(string $handle): string {
    $handle_id = $this->getHandleId($handle);
    if ($handle_id === NULL) {
      throw new InvalidHandleException(sprintf('The handle "%s" is not a valid handle.', $handle));
    }
    return $handle_id;
  }

  /**
   * Removes request-local object associations for a deleted handle.
   *
   * @param string $handle
   *   The deleted handle.
   */
  protected function removeObjectHandleAssociations(string $handle): void {
    $objects = [];
    foreach ($this->objectHandles as $object => $stored_handle) {
      if ($stored_handle === $handle) {
        $objects[] = $object;
      }
    }
    foreach ($objects as $object) {
      unset($this->objectHandles[$object]);
    }
  }

}
