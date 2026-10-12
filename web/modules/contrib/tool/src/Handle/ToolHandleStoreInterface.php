<?php

declare(strict_types=1);

namespace Drupal\tool\Handle;

/**
 * Stores values and provides handles for referencing them across tool calls.
 */
interface ToolHandleStoreInterface {

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
  public function store(mixed $value, ?string $handle = NULL): string;

  /**
   * Resolves a handle back to its stored value.
   *
   * @param string $handle
   *   The handle string.
   *
   * @return mixed
   *   The stored value.
   */
  public function resolve(string $handle): mixed;

  /**
   * Checks whether a handle exists in the store.
   *
   * @param string $handle
   *   The handle string.
   *
   * @return bool
   *   TRUE if the handle exists, FALSE otherwise.
   */
  public function has(string $handle): bool;

  /**
   * Deletes a stored handle.
   *
   * @param string $handle
   *   The handle string.
   */
  public function delete(string $handle): void;

  /**
   * Checks whether a string has this store's handle prefix.
   *
   * @param mixed $value
   *   The value to check.
   *
   * @return bool
   *   TRUE if the value is a prefixed handle string.
   */
  public function hasHandlePrefix(mixed $value): bool;

  /**
   * Checks whether a value is a valid handle.
   *
   * @param mixed $value
   *   The value to check.
   *
   * @return bool
   *   TRUE if the value is a valid handle string.
   */
  public function isHandle(mixed $value): bool;

}
