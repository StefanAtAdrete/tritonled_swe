<?php

declare(strict_types=1);

namespace Drupal\tool\Normalizer;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Plugin\Context\ContextDefinitionInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataTrait;
use Drupal\serialization\Normalizer\ComplexDataNormalizer;
use Drupal\tool\Event\ToolInputDefinitionNormalizeEvent;
use Drupal\tool\Event\ToolOutputDefinitionNormalizeEvent;
use Drupal\tool\Tool\ToolDefinitionNormalizeDirection;
use Drupal\tool\TypedData\EffectiveDataDefinition;
use Drupal\tool\TypedData\InputDefinitionInterface;
use Drupal\tool\TypedData\ListDefinitionBase;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\tool\TypedData\OutputDefinitionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Normalizes ContextDefinition objects.
 *
 * This normalizer extends ComplexDataNormalizer to provide specialized
 * normalization for ContextDefinition objects, which are commonly used
 * in plugin systems and typed data definitions.
 *
 * @property-read \Symfony\Component\Serializer\Normalizer\NormalizerInterface $serializer
 */
class ContextDefinitionNormalizer extends ComplexDataNormalizer {
  use TypedDataTrait;

  /**
   * JSON Schema pattern for decimal values transported as strings.
   *
   * Decimals are strings on the wire to preserve arbitrary precision, so the
   * schema constrains the string shape instead of using a numeric type.
   */
  public const DECIMAL_PATTERN = '^-?\d+(\.\d+)?$';

  /**
   * Maximum entries for enums derived from site state.
   *
   * PluginExists and EntityBundleExists expand to whatever the site defines,
   * which can run to hundreds of IDs; past this limit the enum is omitted so
   * the schema does not bloat every prompt. Author-declared lists (Choice,
   * AllowedValues) are deliberate and are never capped.
   */
  public const DERIVED_ENUM_LIMIT = 50;

  /**
   * Constructs a ContextDefinitionNormalizer.
   *
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(
    protected readonly EventDispatcherInterface $eventDispatcher,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []): array {
    if ($format === 'json_schema') {
      return $this->getNormalizationSchema($object, $context);
    }
    return $this->doNormalize($object, $format, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function doNormalize($object, $format = NULL, array $context = []): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getTypedData(DataDefinitionInterface $definition): mixed {
    return $this->getTypedDataManager()->create($definition);
  }

  /**
   * Asserts a definition normalized in the output direction is an output.
   *
   * ToolDefinition only stores OutputDefinitionInterface outputs and the
   * map and list output classes only accept OutputDefinitionInterface
   * children, so this fails only for a caller that bypasses both.
   *
   * @param \Drupal\tool\TypedData\InputDefinitionInterface|\Drupal\tool\TypedData\OutputDefinitionInterface|\Drupal\Core\Plugin\Context\ContextDefinitionInterface $definition
   *   The definition being normalized.
   * @param string $name
   *   The definition name, for the error message.
   *
   * @return \Drupal\tool\TypedData\OutputDefinitionInterface
   *   The same definition.
   */
  protected function requireOutputDefinition(InputDefinitionInterface|OutputDefinitionInterface|ContextDefinitionInterface $definition, string $name): OutputDefinitionInterface {
    if (!$definition instanceof OutputDefinitionInterface) {
      throw new \InvalidArgumentException(sprintf("Output '%s' must be a %s, %s given.", $name, OutputDefinitionInterface::class, $definition::class));
    }
    return $definition;
  }

  /**
   * Get combined description from label and description.
   */
  protected function getDescription(InputDefinitionInterface|OutputDefinitionInterface|ContextDefinitionInterface $definition) : ?string {
    if ($description = (string) $definition->getLabel()) {
      if ((string) $definition->getDescription()) {
        $description .= ': ' . (string) $definition->getDescription();
      }
    }
    else {
      $description = (string) $definition->getDescription();
    }
    return $description ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizationSchema(mixed $object, array $context = []): array {
    assert($object instanceof InputDefinitionInterface || $object instanceof OutputDefinitionInterface || $object instanceof ContextDefinitionInterface);
    assert($this->serializer instanceof NormalizerInterface);

    // If invoker context is provided, dispatch event to allow definition
    // transformation before normalization. This lets subscribers swap
    // definitions at any depth (e.g., entity:user → string/email for AI).
    if (!empty($context['tool_invoker']) && !empty($context['tool_id']) && !empty($context['tool_definition_name']) && !empty($context['tool_direction'])) {
      $direction = $context['tool_direction'] instanceof ToolDefinitionNormalizeDirection
        ? $context['tool_direction']
        : ToolDefinitionNormalizeDirection::from($context['tool_direction']);
      $event = match ($direction) {
        ToolDefinitionNormalizeDirection::Input => new ToolInputDefinitionNormalizeEvent($context['tool_definition_name'], $object, $context['tool_id'], $context['tool_invoker']),
        ToolDefinitionNormalizeDirection::Output => new ToolOutputDefinitionNormalizeEvent($context['tool_definition_name'], $this->requireOutputDefinition($object, $context['tool_definition_name']), $context['tool_id'], $context['tool_invoker']),
      };
      $this->eventDispatcher->dispatch($event);
      $object = $event->getDefinition();
    }

    // If multiple or a list, handle item definition. Checked against the
    // definition itself rather than through getDataDefinition(): the
    // definition families are typed data (#3583017) and the bridge rebuild
    // is being retired (#3583018). A core context definition from the ai
    // override path answers through isMultiple() and its data type.
    if ($object->isMultiple() || $object instanceof ListDataDefinitionInterface || $object->getDataType() === 'list') {
      $schema = [
        'type' => 'array',
      ];
      if ($object->isMultiple()) {
        // If multiple, clone and set multiple to false for item schema.
        // Mark the item required so its schema does not gain a null branch:
        // an item slot in a list cannot be "omitted" — it either holds a
        // value or should not exist.
        $item_definition = clone $object;
        $item_definition->setMultiple(FALSE);
        $item_definition->setRequired(TRUE);
        $item_definition->setLabel('');
        $item_definition->setDescription('');
        $schema['items'] = $this->serializer->normalize($item_definition, 'json_schema', $context);
        // A multiple's constraints bind its items; the list-level share is
        // what fromMultiple() would give the converted list, so both spell
        // the same schema.
        [$constraints] = ListDefinitionBase::splitMultipleConstraints($object->getConstraints());
        if ($constraints) {
          $schema = $this->applyListConstraintSchema($constraints, $schema);
        }
      }
      elseif ($object instanceof ListDataDefinition) {
        $schema['description'] = $this->getDescription($object);
        if ($object->getItemDefinition()) {
          $schema['items'] = $this->serializer->normalize($object->getItemDefinition(), 'json_schema', $context);
        }
      }
      if (!$object->isMultiple() && ($constraints = $object->getConstraints())) {
        $schema = $this->applyListConstraintSchema($constraints, $schema);
      }
    }
    elseif ($object instanceof MapDataDefinition || $object->getDataType() === 'map') {
      // A definition declared with the map data type on a plain definition
      // class is not a MapDataDefinition, but it is a map all the same, the
      // way the list data type is a list above. Before core 11.1 the
      // typed-data branch below would advertise it as a string: the legacy
      // normalizer has no map schema (#3583071).
      $schema = ['type' => 'object'];
      $property_definitions = $object instanceof MapDataDefinition ? $object->getPropertyDefinitions() : [];
      foreach ($property_definitions as $property_name => $property) {
        $child_context = $context;
        $child_context['tool_definition_name'] = (string) $property_name;
        $property_schema = $this->serializer->normalize($property, 'json_schema', $child_context);
        if (empty($property_schema['description']) && $description = $this->getDescription($property)) {
          $property_schema['description'] = (string) $description;
        }
        $schema['properties'][$property_name] = $property_schema;
        if ($property->isRequired()) {
          $schema['required'] ??= [];
          $schema['required'][] = (string) $property_name;
        }
      }
      // Without properties, an empty PHP array would encode as [] rather
      // than the {} JSON Schema expects for object members.
      $schema['properties'] ??= new \ArrayObject();
      if ($constraints = $object->getConstraints()) {
        $schema = $this->applyMapConstraintSchema($constraints, $schema);
      }
    }
    elseif ($schema = $this->serializer->normalize($this->getTypedData($this->getDataDefinition($object)), 'json_schema', $context)) {
      // Both the legacy path and core's normalizers describe decimals with
      // the non-standard `format: number`; rebuild as a patterned string so
      // consumers get an enforceable contract that preserves precision.
      if ($this->getDataDefinition($object)->getDataType() === 'decimal') {
        $schema = [
          'type' => 'string',
          'pattern' => self::DECIMAL_PATTERN,
        ];
        $type_note = 'A decimal number formatted as a string.';
      }
      // On the legacy path timestamps stay integers; core (11.1+) already
      // describes them as RFC 3339 date-time strings.
      elseif ($this->getDataDefinition($object)->getDataType() === 'timestamp' && ($schema['type'] ?? NULL) === 'integer') {
        $type_note = 'A Unix timestamp in seconds.';
      }
      // Core's DateTimeIso8601Normalizer schema says `date` (its date-only
      // field case), but bare typed data normalizes to RFC 3339 date-time
      // strings, which is what tool inputs and outputs carry.
      elseif ($this->getDataDefinition($object)->getDataType() === 'datetime_iso8601') {
        // Only emit a format when the definition explicitly declares which
        // datetime flavor it is via a marker constraint. This allows
        // FieldSetValue (or any tool) to signal date-only vs full datetime
        // based on the field's settings, without the normalizer guessing.
        $constraints = $object->getConstraints();
        if (array_key_exists('DateOnly', $constraints)) {
          $schema['format'] = 'date';
        }
        elseif (array_key_exists('DateTime', $constraints)) {
          $schema['format'] = 'date-time';
        }
      }
      if ($default_value = $object->getDefaultValue()) {
        $schema['default'] = $default_value;
      }
      if ($constraints = $object->getConstraints()) {
        $schema = $this->applyConstraintSchema($constraints, $schema);
      }
    }
    elseif ($this->getDataDefinition($object)->getDataType() === 'any') {
      // An untyped input accepts any JSON value, so its schema constrains
      // nothing. Core's schema-aware normalizers (11.1+) already emit an
      // untyped schema; this branch covers the legacy path, whose string
      // fallback would otherwise advertise a false upper bound — refining
      // 'any' to a concrete type is only sound when the advertisement
      // genuinely accepts everything.
      $schema = [];
    }
    else {
      $schema = [
        'type' => 'string',
      ];
    }

    // Examples are annotations like default, read off the post-transform
    // definition; a swapped-in definition without examples sheds them. They
    // always describe a single value: for a multiple input the cloned item
    // definition carries them onto `items` and the array level stays clean.
    if (!$object->isMultiple() && ($object instanceof InputDefinitionInterface || $object instanceof OutputDefinitionInterface) && ($examples = $object->getExamples())) {
      $schema['examples'] = array_values($examples);
    }

    if (empty($schema['description']) && $description = $this->getDescription($object)) {
      $schema['description'] = (string) $description;
    }
    // The label is kept merged into the description because most LLM clients
    // only surface `description`; `title` duplicates it for consumers that
    // render titles (schema forms, docs). A clean title/description split is
    // a prompt-visible change deferred to a follow-up issue.
    if (empty($schema['title']) && $label = (string) $object->getLabel()) {
      $schema['title'] = $label;
    }
    if (isset($type_note)) {
      $schema['description'] = isset($schema['description'])
        ? $schema['description'] . ' ' . $type_note
        : $type_note;
    }
    if (!$object->isRequired()) {
      // An optional input may usually also be null, but NotNull (and
      // NotBlank without allowNull, which also rejects null) means the input
      // may be omitted yet must not be null — advertising null would invite
      // guaranteed validation failures. A multiple's constraints bind its
      // items, so only its list-level share may speak for the array
      // (#3583069).
      $constraints = $object->isMultiple()
        ? ListDefinitionBase::splitMultipleConstraints($object->getConstraints())[0]
        : $object->getConstraints();
      $not_nullable = array_key_exists('NotNull', $constraints)
        || (array_key_exists('NotBlank', $constraints) && !$this->getConstraintOption($constraints['NotBlank'], 'allowNull'));
      if ($not_nullable) {
        $schema = $this->removeNullBranch($schema);
      }
      else {
        if (isset($schema['type']) && is_string($schema['type'])) {
          $schema['type'] = [$schema['type'], 'null'];
        }
        $schema = $this->allowNullValue($schema);
      }
    }
    return $schema;
  }

  /**
   * Lets the value keywords of a nullable schema accept null.
   *
   * `enum` and `const` bind the whole value, beside `type` and `oneOf`: a
   * null the type allows still fails an `enum` that does not list it, so the
   * schema would accept no null at all. Tool API skips validating a NULL
   * optional value, so every constraint behind these keywords accepts it
   * (#3583072).
   *
   * @param array $schema
   *   The JSON Schema of an optional value that may be null.
   *
   * @return array
   *   The schema with null listed in its `enum`, and a `const` widened to an
   *   `enum` of the constant and null.
   */
  protected function allowNullValue(array $schema): array {
    if (array_key_exists('const', $schema) && $schema['const'] !== NULL) {
      $schema['enum'] = [$schema['const'], NULL];
      unset($schema['const']);
    }
    elseif (isset($schema['enum']) && is_array($schema['enum']) && !in_array(NULL, $schema['enum'], TRUE)) {
      $schema['enum'][] = NULL;
    }
    return $schema;
  }

  /**
   * Maps validation constraints of a single value onto a JSON Schema.
   *
   * @param array $constraints
   *   The constraints of the definition, keyed by constraint name.
   * @param array $schema
   *   The JSON Schema built so far.
   *
   * @return array
   *   The JSON Schema with constraint-derived keywords applied.
   */
  protected function applyConstraintSchema(array $constraints, array $schema): array {
    if (isset($constraints['FixedValue'])) {
      // Extract value from constraint object or array structure.
      if (is_object($constraints['FixedValue']) && property_exists($constraints['FixedValue'], 'value')) {
        $schema['const'] = $constraints['FixedValue']->value;
      }
      elseif (is_array($constraints['FixedValue']) && isset($constraints['FixedValue']['value'])) {
        $schema['const'] = $constraints['FixedValue']['value'];
      }
      else {
        $schema['const'] = $constraints['FixedValue'];
      }
    }
    if (isset($constraints['Choice'])) {
      if (isset($constraints['Choice']['choices'])) {
        $schema['enum'] = $constraints['Choice']['choices'];
      }
      elseif (isset($constraints['Choice']['callback'])) {
        $schema['enum'] = call_user_func($constraints['Choice']['callback']);
      }
      elseif (is_array($constraints['Choice']) && array_is_list($constraints['Choice'])) {
        $schema['enum'] = $constraints['Choice'];
      }
    }
    if (isset($constraints['AllowedValues'])) {
      if ($constraints['AllowedValues'] instanceof Choice) {
        /** @var \Symfony\Component\Validator\Constraints\Choice $choice */
        $choice = $constraints['AllowedValues'];
        $schema['enum'] = $choice->choices;
      }
      elseif (isset($constraints['AllowedValues']['choices'])) {
        $schema['enum'] = $constraints['AllowedValues']['choices'];
      }
      elseif (isset($constraints['AllowedValues']['callback'])) {
        $schema['enum'] = call_user_func($constraints['AllowedValues']['callback']);
      }
      elseif (is_array($constraints['AllowedValues']) && !empty($constraints['AllowedValues'])) {
        $schema['enum'] = $constraints['AllowedValues'];
      }
    }
    if (isset($constraints['PluginExists'])) {
      // Get a list of plugin IDs from the specified plugin manager.
      // @phpstan-ignore globalDrupalDependencyInjection.useDependencyInjection
      $plugin_manager = \Drupal::service($constraints['PluginExists']['manager']); // phpcs:ignore
      $plugin_definitions = $plugin_manager->getDefinitions();
      $fallbacks = [];
      if (isset($constraints['PluginExists']['interface'])) {
        foreach ($plugin_definitions as $plugin_id => $plugin_definition) {
          if (!is_a(DefaultFactory::getPluginClass($plugin_id, $plugin_definition), $constraints['PluginExists']['interface'], TRUE)) {
            unset($plugin_definitions[$plugin_id]);
          }
          if ($plugin_manager instanceof FallbackPluginManagerInterface) {
            $key = $plugin_manager->getFallbackPluginId($plugin_id);
            if ($key) {
              $fallbacks[$key] = $key;
            }
          }
        }
      }
      // Remove fallback IDs from the list.
      if ($fallbacks) {
        $plugin_definitions = array_diff_key($plugin_definitions, $fallbacks);
      }
      $plugin_ids = array_keys($plugin_definitions);
      if (count($plugin_ids) <= self::DERIVED_ENUM_LIMIT) {
        $schema['enum'] = $plugin_ids;
      }
    }

    if (isset($constraints['Length'])) {
      if (isset($constraints['Length']['min'])) {
        $schema['minLength'] = $constraints['Length']['min'];
      }
      if (isset($constraints['Length']['max'])) {
        $schema['maxLength'] = $constraints['Length']['max'];
      }
    }
    if (isset($constraints['Range'])) {
      if (isset($constraints['Range']['min'])) {
        $schema['minimum'] = $constraints['Range']['min'];
      }
      if (isset($constraints['Range']['max'])) {
        $schema['maximum'] = $constraints['Range']['max'];
      }
    }
    if (isset($constraints['Regex'])) {
      $pattern = $this->getConstraintOption($constraints['Regex'], 'pattern');
      // A `match: false` constraint rejects matching values, which JSON
      // Schema `pattern` cannot express; omit it rather than invert meaning.
      $match = $this->getConstraintOption($constraints['Regex'], 'match') ?? TRUE;
      if (is_string($pattern) && $match !== FALSE) {
        $ecma_pattern = $this->convertPcreToEcma($pattern);
        if ($ecma_pattern !== NULL) {
          $schema['pattern'] = $ecma_pattern;
        }
      }
    }
    $comparisons = [
      'GreaterThan' => 'exclusiveMinimum',
      'GreaterThanOrEqual' => 'minimum',
      'LessThan' => 'exclusiveMaximum',
      'LessThanOrEqual' => 'maximum',
    ];
    foreach ($comparisons as $constraint_name => $keyword) {
      if (isset($constraints[$constraint_name])) {
        $value = $this->getConstraintOption($constraints[$constraint_name], 'value');
        if (is_int($value) || is_float($value)) {
          $schema[$keyword] = $value;
        }
      }
    }
    // Options-less constraints are commonly declared with a NULL or empty
    // array value, so presence is checked with array_key_exists.
    if (array_key_exists('Email', $constraints)) {
      $schema['format'] = 'email';
    }
    if (array_key_exists('Uuid', $constraints)) {
      $schema['format'] = 'uuid';
    }
    if (array_key_exists('NotBlank', $constraints) && $this->schemaHasType($schema, 'string')) {
      $schema['minLength'] ??= 1;
    }
    if (array_key_exists('PositiveOrZero', $constraints)) {
      $schema['minimum'] ??= 0;
    }
    if (isset($constraints['IdenticalTo'])) {
      $value = $this->getConstraintOption($constraints['IdenticalTo'], 'value');
      if ($value !== NULL) {
        $schema['const'] = $value;
      }
    }
    if (array_key_exists('CountryCode', $constraints)) {
      // The validator resolves to a ~250-entry Choice; the pattern conveys
      // the shape without bloating every prompt with the full ISO list.
      $schema['pattern'] = '^[A-Z]{2}$';
    }
    if (isset($constraints['EntityBundleExists'])) {
      $entity_type_id = is_string($constraints['EntityBundleExists'])
        ? $constraints['EntityBundleExists']
        : $this->getConstraintOption($constraints['EntityBundleExists'], 'entityTypeId');
      // Dynamic references (e.g. %parent) cannot be resolved here.
      if (is_string($entity_type_id) && !str_contains($entity_type_id, '%')) {
        // @phpstan-ignore globalDrupalDependencyInjection.useDependencyInjection
        $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type_id); // phpcs:ignore
        if ($bundles && count($bundles) <= self::DERIVED_ENUM_LIMIT) {
          $schema['enum'] = array_keys($bundles);
        }
      }
    }
    return $schema;
  }

  /**
   * Checks whether a schema allows the given type on either scalar path.
   *
   * @param array $schema
   *   The JSON Schema.
   * @param string $type
   *   The JSON Schema type to look for.
   *
   * @return bool
   *   TRUE if the schema's type (or a oneOf branch) matches.
   */
  protected function schemaHasType(array $schema, string $type): bool {
    if (isset($schema['type'])) {
      return in_array($type, (array) $schema['type'], TRUE);
    }
    foreach ($schema['oneOf'] ?? [] as $branch) {
      if (is_array($branch) && in_array($type, (array) ($branch['type'] ?? []), TRUE)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Removes the null branch from a schema on either scalar path.
   *
   * @param array $schema
   *   The JSON Schema, either core's oneOf shape or a type list.
   *
   * @return array
   *   The schema without a null alternative.
   */
  protected function removeNullBranch(array $schema): array {
    if (isset($schema['oneOf']) && is_array($schema['oneOf'])) {
      $branches = array_values(array_filter(
        $schema['oneOf'],
        static fn ($branch) => ($branch['type'] ?? NULL) !== 'null',
      ));
      if (count($branches) === 1) {
        unset($schema['oneOf']);
        $schema = $branches[0] + $schema;
      }
      else {
        $schema['oneOf'] = $branches;
      }
    }
    if (isset($schema['type']) && is_array($schema['type'])) {
      $types = array_values(array_diff($schema['type'], ['null']));
      $schema['type'] = count($types) === 1 ? reset($types) : $types;
    }
    return $schema;
  }

  /**
   * Maps validation constraints of a list onto an array JSON Schema.
   *
   * @param array $constraints
   *   The constraints of the definition, keyed by constraint name.
   * @param array $schema
   *   The array JSON Schema built so far.
   *
   * @return array
   *   The JSON Schema with constraint-derived keywords applied.
   */
  protected function applyListConstraintSchema(array $constraints, array $schema): array {
    if (isset($constraints['Count'])) {
      // An `exactly` count is expressed by identical min and max bounds.
      $exactly = $this->getConstraintOption($constraints['Count'], 'exactly');
      $min = $this->getConstraintOption($constraints['Count'], 'min') ?? $exactly;
      $max = $this->getConstraintOption($constraints['Count'], 'max') ?? $exactly;
      if (is_int($min)) {
        $schema['minItems'] = $min;
      }
      if (is_int($max)) {
        $schema['maxItems'] = $max;
      }
    }
    // NotBlank on a list rejects [], with or without allowNull, which only
    // exempts NULL (#3583069).
    if (array_key_exists('NotBlank', $constraints)) {
      $schema['minItems'] ??= 1;
    }
    return $schema;
  }

  /**
   * Maps validation constraints of a map onto an object JSON Schema.
   *
   * @param array $constraints
   *   The constraints of the definition, keyed by constraint name.
   * @param array $schema
   *   The object JSON Schema built so far.
   *
   * @return array
   *   The JSON Schema with constraint-derived keywords applied.
   */
  protected function applyMapConstraintSchema(array $constraints, array $schema): array {
    if (isset($constraints['Count'])) {
      // Count on a map counts its properties. An `exactly` count is
      // expressed by identical min and max bounds.
      $exactly = $this->getConstraintOption($constraints['Count'], 'exactly');
      $min = $this->getConstraintOption($constraints['Count'], 'min') ?? $exactly;
      $max = $this->getConstraintOption($constraints['Count'], 'max') ?? $exactly;
      if (is_int($min)) {
        $schema['minProperties'] = $min;
      }
      if (is_int($max)) {
        $schema['maxProperties'] = $max;
      }
    }
    // NotBlank on a map rejects [], with or without allowNull, which only
    // exempts NULL (#3583069).
    if (array_key_exists('NotBlank', $constraints)) {
      $schema['minProperties'] ??= 1;
    }
    return $schema;
  }

  /**
   * Reads a single option from a constraint given as an array or object.
   *
   * @param mixed $constraint
   *   The constraint options array or Symfony constraint object.
   * @param string $option
   *   The option name.
   *
   * @return mixed
   *   The option value, or NULL if not present.
   */
  protected function getConstraintOption(mixed $constraint, string $option): mixed {
    if (is_array($constraint) && isset($constraint[$option])) {
      return $constraint[$option];
    }
    if (is_object($constraint) && property_exists($constraint, $option)) {
      return $constraint->{$option};
    }
    return NULL;
  }

  /**
   * Converts a PCRE pattern to an ECMA-262 pattern for JSON Schema.
   *
   * Strips the delimiters and any modifiers whose removal can only make the
   * schema stricter than the server-side validator (`i`, `m`, `s`), so every
   * schema-valid value stays validator-valid. Modifiers that change how the
   * pattern text itself is parsed (such as `x` or `u`) cannot be stripped
   * safely, so those patterns are not converted. The pattern body is emitted
   * as-is and is not checked for PCRE-only syntax.
   *
   * @param string $pattern
   *   The PCRE pattern including delimiters, e.g. '/^[a-z]+$/i'.
   *
   * @return string|null
   *   The ECMA-262 pattern, or NULL if the pattern cannot be converted.
   */
  protected function convertPcreToEcma(string $pattern): ?string {
    $pattern = trim($pattern);
    if (strlen($pattern) < 3) {
      return NULL;
    }
    $delimiter = $pattern[0];
    if (ctype_alnum($delimiter) || $delimiter === '\\') {
      return NULL;
    }
    $closers = ['(' => ')', '{' => '}', '[' => ']', '<' => '>'];
    $closer = $closers[$delimiter] ?? $delimiter;
    $end = strrpos($pattern, $closer);
    if ($end === FALSE || $end < 2) {
      return NULL;
    }
    $modifiers = substr($pattern, $end + 1);
    if ($modifiers !== '' && !preg_match('/^[ims]+$/', $modifiers)) {
      return NULL;
    }
    return substr($pattern, 1, $end - 1);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFormat($format = NULL) {
    if ($format === 'json_schema') {
      return TRUE;
    }
    return parent::checkFormat($format);
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    if ($format !== 'json_schema') {
      return [];
    }
    return [
      InputDefinitionInterface::class => TRUE,
      OutputDefinitionInterface::class => TRUE,
      // Core context definitions stay supported: the ai module's override
      // path (setContextDefinitionOverride()) still delivers them.
      ContextDefinitionInterface::class => TRUE,
    ];
  }

  /**
   * Resolves the typed-data definition a schema is derived from.
   *
   * A definition family object rebuilds through EffectiveDataDefinition -
   * the declared class need not match the data type's typed-data class (a
   * plain 'map'-typed definition is not a MapDataDefinition) - and a core
   * context definition from the ai override path rebuilds through its own
   * getDataDefinition().
   *
   * @param \Drupal\tool\TypedData\InputDefinitionInterface|\Drupal\tool\TypedData\OutputDefinitionInterface|\Drupal\Core\Plugin\Context\ContextDefinitionInterface $object
   *   The definition being normalized.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   *   The typed-data definition.
   */
  protected function getDataDefinition(InputDefinitionInterface|OutputDefinitionInterface|ContextDefinitionInterface $object): DataDefinitionInterface {
    return EffectiveDataDefinition::createFrom($object);
  }

}
