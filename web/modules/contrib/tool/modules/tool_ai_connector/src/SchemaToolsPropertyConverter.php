<?php

declare(strict_types=1);

namespace Drupal\tool_ai_connector;

use Drupal\ai\OperationType\Chat\Tools\ToolsPropertyInput;

/**
 * Converts JSON schema properties to AI module tool properties.
 */
final class SchemaToolsPropertyConverter {

  /**
   * Converts a JSON schema property to the AI module's property object.
   *
   * Property names are escaped at every level because the AI module rejects
   * non-alphanumeric property names outright. The AI module unescapes
   * top-level argument names when values come back; nested keys are
   * unescaped by ToolPluginBase before values reach the Tool plugin.
   *
   * @param string $name
   *   The schema property name.
   * @param array $schema
   *   The schema for this property.
   * @param bool $required
   *   Whether this property is required by its parent object.
   *
   * @return \Drupal\ai\OperationType\Chat\Tools\ToolsPropertyInput
   *   The AI module property input.
   */
  public function convert(string $name, array $schema, bool $required = FALSE): ToolsPropertyInput {
    // The tool serializer emits empty schema objects as \ArrayObject so JSON
    // encoding yields {} rather than []; ToolsPropertyInput only accepts
    // arrays, so cast them back at every depth.
    $schema = $this->arrayObjectsToArrays($schema);
    $property_schema = $this->prepareAiPropertySchema($schema);
    $child_required = [];
    if (isset($schema['required']) && is_array($schema['required'])) {
      $child_required = $schema['required'];
    }
    unset($property_schema['required']);

    if (isset($property_schema['properties']) && is_array($property_schema['properties'])) {
      $children = [];
      foreach ($property_schema['properties'] as $child_name => $child_schema) {
        if (is_array($child_schema)) {
          $children[] = $this->convert($child_name, $child_schema, in_array($child_name, $child_required, TRUE));
        }
      }
      $property_schema['properties'] = $children;
    }

    $property = new ToolsPropertyInput($this->escapePropertyName($name), $property_schema);
    $property->setRequired($required);
    if ($child_required !== []) {
      $property->setCustomValue('required', $child_required);
    }

    return $property;
  }

  /**
   * Prepares JSON Schema for the AI module's property object.
   *
   * @param array $schema
   *   The JSON schema property.
   *
   * @return array
   *   The property schema with a type value AI can render.
   */
  protected function prepareAiPropertySchema(array $schema): array {
    // Optionality is conveyed by absence from 'required', and the type is
    // flattened to its non-null member below, so a null the enum lists for
    // an optional value (#3583072) goes too: an enum member that does not
    // match the type is rejected by providers that check enum types.
    if (isset($schema['enum']) && is_array($schema['enum']) && in_array(NULL, $schema['enum'], TRUE)) {
      $schema['enum'] = array_values(array_filter($schema['enum'], static fn ($value) => $value !== NULL));
      if ($schema['enum'] === []) {
        unset($schema['enum']);
      }
    }

    // Flatten nullable type unions. ToolsPropertyInput cannot express a type
    // array or composition in a JSON Schema draft 2020-12-valid way (it
    // renders them as an object under 'type', which strict providers like
    // Anthropic reject), and optionality is already conveyed by the property
    // being absent from 'required'.
    if (isset($schema['type']) && is_array($schema['type']) && array_is_list($schema['type'])) {
      $types = array_values(array_diff($schema['type'], ['null']));
      $schema['type'] = $types[0] ?? 'string';
    }

    if (isset($schema['type'])) {
      return $schema;
    }

    // A oneOf/anyOf union of bare type schemas (e.g. core's nullable
    // normalization: oneOf [{type: X}, {type: null}]) flattens to the
    // non-null type for the same reasons as above.
    foreach (['oneOf', 'anyOf'] as $composition) {
      if (isset($schema[$composition]) && is_array($schema[$composition])) {
        $types = [];
        foreach ($schema[$composition] as $sub_schema) {
          if (!is_array($sub_schema) || !isset($sub_schema['type']) || !is_string($sub_schema['type']) || count($sub_schema) !== 1) {
            $types = NULL;
            break;
          }
          $types[] = $sub_schema['type'];
        }
        if ($types !== NULL) {
          $non_null = array_values(array_diff($types, ['null']));
          if ($non_null !== []) {
            $schema['type'] = $non_null[0];
            unset($schema[$composition]);
            return $schema;
          }
        }
      }
    }

    foreach (['anyOf', 'oneOf', 'allOf', 'not'] as $composition) {
      if (isset($schema[$composition]) && is_array($schema[$composition])) {
        $schema['type'] = [$composition => $schema[$composition]];
        unset($schema[$composition]);
        return $schema;
      }
    }

    if (isset($schema['properties'])) {
      $schema['type'] = 'object';
    }
    elseif (isset($schema['items'])) {
      $schema['type'] = 'array';
    }
    else {
      $schema['type'] = 'string';
    }

    return $schema;
  }

  /**
   * Recursively converts \ArrayObject values in a schema to plain arrays.
   *
   * A PHP array cannot carry the {} vs [] distinction, so an empty
   * properties member re-encodes as [] — invalid JSON Schema draft 2020-12,
   * rejected by strict providers (e.g. Anthropic) — wherever the schema
   * reaches the payload without further conversion (e.g. inside items). An
   * empty properties member constrains nothing, so it is dropped instead.
   *
   * @param array $schema
   *   The JSON schema, possibly containing \ArrayObject values.
   *
   * @return array
   *   The schema with every \ArrayObject replaced by its array copy and
   *   empty properties members removed.
   */
  protected function arrayObjectsToArrays(array $schema): array {
    foreach ($schema as $key => $value) {
      if ($value instanceof \ArrayObject) {
        $value = $value->getArrayCopy();
      }
      if (is_array($value)) {
        $value = $this->arrayObjectsToArrays($value);
      }
      if ($key === 'properties' && $value === []) {
        unset($schema[$key]);
        continue;
      }
      $schema[$key] = $value;
    }
    return $schema;
  }

  /**
   * Escapes property names for the AI module's tool schema object.
   *
   * @param string $name
   *   The property name.
   *
   * @return string
   *   The escaped property name.
   */
  protected function escapePropertyName(string $name): string {
    return str_replace(':', '__colon__', $name);
  }

}
