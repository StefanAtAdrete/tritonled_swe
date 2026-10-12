<?php

declare(strict_types=1);

namespace Drupal\Tests\tool\Traits;

/**
 * Generates values conforming to a tool's advertised JSON Schema.
 *
 * Supports the round-trip acceptance property: for every tool, a value that
 * validates against the advertised input schema must be accepted by the
 * tool. Invokers such as the MCP bridge validate arguments against the
 * schema strictly before the tool sees them, so any input whose advertised
 * schema describes something the tool rejects is unusable over that wire.
 *
 * Other modules can reuse this trait against their own tools; override
 * patternSample() (or supply per-input overrides in the consuming test) for
 * patterns this trait does not know how to satisfy.
 */
trait SchemaSampleGeneratorTrait {

  /**
   * Generates a value conforming to a JSON Schema fragment.
   *
   * @param array $schema
   *   The schema fragment as emitted by the tool definition serializer.
   * @param string $path
   *   A human-readable location for failure messages.
   *
   * @return mixed
   *   A value that validates against the schema.
   *
   * @throws \LogicException
   *   When no sample can be derived; the caller should supply an override.
   */
  protected function sampleFromSchema(array $schema, string $path): mixed {
    if (array_key_exists('const', $schema)) {
      return $schema['const'];
    }
    if (!empty($schema['enum'])) {
      return reset($schema['enum']);
    }

    // The serializer emits nullable scalars either as oneOf branches (11.1+
    // cores) with constraint keywords beside the oneOf, or as a type array.
    if (isset($schema['oneOf']) && is_array($schema['oneOf'])) {
      foreach ($schema['oneOf'] as $branch) {
        if (is_array($branch) && ($branch['type'] ?? NULL) !== 'null') {
          $keywords = $schema;
          unset($keywords['oneOf']);
          return $this->sampleFromSchema($branch + $keywords, $path);
        }
      }
      return NULL;
    }

    $type = $schema['type'] ?? NULL;
    if (is_array($type)) {
      $types = array_values(array_diff($type, ['null']));
      $type = $types[0] ?? 'null';
    }

    return match ($type) {
      // No declared type constrains nothing, so any value conforms.
      NULL => 'sample',
      'null' => NULL,
      'boolean' => TRUE,
      'integer' => $this->numericSample($schema, TRUE),
      'number' => $this->numericSample($schema, FALSE),
      'string' => $this->stringSample($schema, $path),
      'array' => $this->arraySample($schema, $path),
      'object' => $this->objectSample($schema, $path),
      default => throw new \LogicException(sprintf(
        'No sample rule for schema type "%s" at %s.',
        $type,
        $path,
      )),
    };
  }

  /**
   * Generates a numeric sample within the schema's bounds.
   *
   * @param array $schema
   *   The schema fragment.
   * @param bool $integer
   *   TRUE for an integer sample, FALSE for a float.
   *
   * @return int|float
   *   The sample number.
   */
  protected function numericSample(array $schema, bool $integer): int|float {
    $value = $schema['minimum']
      ?? (isset($schema['exclusiveMinimum']) ? $schema['exclusiveMinimum'] + 1 : 1);
    if (isset($schema['maximum']) && $value > $schema['maximum']) {
      $value = $schema['maximum'];
    }
    if (isset($schema['exclusiveMaximum']) && $value >= $schema['exclusiveMaximum']) {
      $value = $schema['exclusiveMaximum'] - 1;
    }
    return $integer ? (int) $value : (float) $value;
  }

  /**
   * Generates a string sample honoring format, pattern, and length bounds.
   *
   * @param array $schema
   *   The schema fragment.
   * @param string $path
   *   A human-readable location for failure messages.
   *
   * @return string
   *   The sample string.
   */
  protected function stringSample(array $schema, string $path): string {
    if (isset($schema['pattern'])) {
      return $this->patternSample($schema['pattern'], $path);
    }
    $sample = match ($schema['format'] ?? NULL) {
      'date-time' => '2026-01-15T10:30:00+00:00',
      'date' => '2026-01-15',
      'email' => 'person@example.com',
      'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
      'uri' => 'https://example.com/sample',
      'duration' => 'PT30M',
      default => 'sample',
    };
    if (isset($schema['minLength']) && strlen($sample) < $schema['minLength']) {
      $sample = str_pad($sample, $schema['minLength'], 'a');
    }
    if (isset($schema['maxLength']) && strlen($sample) > $schema['maxLength']) {
      $sample = substr($sample, 0, $schema['maxLength']);
    }
    return $sample;
  }

  /**
   * Returns a string matching a known schema pattern.
   *
   * @param string $pattern
   *   The ECMA-262 pattern from the schema.
   * @param string $path
   *   A human-readable location for failure messages.
   *
   * @return string
   *   A string matching the pattern.
   *
   * @throws \LogicException
   *   When the pattern is not in the known table.
   */
  protected function patternSample(string $pattern, string $path): string {
    $known = [
      // The serializer's decimal-as-string pattern.
      '^-?\d+(\.\d+)?$' => '12.5',
      // The serializer's CountryCode pattern.
      '^[A-Z]{2}$' => 'US',
      // Patterns used by this module's test tools.
      '^[a-z]{3}-\d{2}$' => 'abc-12',
    ];
    if (isset($known[$pattern])) {
      return $known[$pattern];
    }
    throw new \LogicException(sprintf(
      'No sample known for pattern "%s" at %s; supply an override.',
      $pattern,
      $path,
    ));
  }

  /**
   * Generates an array sample from the items schema.
   *
   * @param array $schema
   *   The schema fragment.
   * @param string $path
   *   A human-readable location for failure messages.
   *
   * @return array
   *   The sample list.
   */
  protected function arraySample(array $schema, string $path): array {
    $count = max($schema['minItems'] ?? 1, 1);
    $item_schema = isset($schema['items']) && is_array($schema['items'])
      ? $schema['items']
      : [];
    $items = [];
    for ($delta = 0; $delta < $count; $delta++) {
      $items[] = $this->sampleFromSchema($item_schema, $path . '[' . $delta . ']');
    }
    return $items;
  }

  /**
   * Generates an object sample containing the schema's required properties.
   *
   * Optional properties are omitted: their per-shape coverage belongs to
   * the coercion matrix, and some (entity references) need invoker-specific
   * values this generator cannot produce. A minProperties bound is met by
   * adding optional properties in declared order.
   *
   * @param array $schema
   *   The schema fragment.
   * @param string $path
   *   A human-readable location for failure messages.
   *
   * @return array
   *   The sample map.
   */
  protected function objectSample(array $schema, string $path): array {
    $properties = $schema['properties'] ?? [];
    if ($properties instanceof \ArrayObject) {
      $properties = $properties->getArrayCopy();
    }
    if (!$properties) {
      // A free-form map accepts any members.
      return ['sample_key' => 'sample'];
    }
    $value = [];
    foreach ($schema['required'] ?? [] as $property_name) {
      if (isset($properties[$property_name])) {
        $value[$property_name] = $this->sampleFromSchema(
          (array) $properties[$property_name],
          $path . '.' . $property_name,
        );
      }
    }
    foreach ($properties as $property_name => $property_schema) {
      if (count($value) >= ($schema['minProperties'] ?? 0)) {
        break;
      }
      $value[$property_name] ??= $this->sampleFromSchema(
        (array) $property_schema,
        $path . '.' . $property_name,
      );
    }
    return $value;
  }

  /**
   * Re-shapes a conforming value the way an LLM plausibly would.
   *
   * Booleans and numbers become their string spellings and structures become
   * JSON strings — shapes outside the schema's letter that the coercion
   * pipeline commits to resolving. Strings and unknown objects (e.g. entity
   * overrides) pass through unchanged.
   *
   * @param mixed $value
   *   The conforming value.
   *
   * @return mixed
   *   The re-shaped value.
   */
  protected function llmVariant(mixed $value): mixed {
    if (is_bool($value)) {
      return $value ? 'true' : 'false';
    }
    if (is_int($value) || is_float($value)) {
      return (string) $value;
    }
    if (is_array($value)) {
      $has_object = FALSE;
      array_walk_recursive($value, function ($item) use (&$has_object): void {
        $has_object = $has_object || is_object($item);
      });
      return $has_object ? $value : json_encode($value);
    }
    return $value;
  }

}
