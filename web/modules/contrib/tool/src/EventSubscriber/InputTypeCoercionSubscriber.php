<?php

declare(strict_types=1);

namespace Drupal\tool\EventSubscriber;

use Drupal\tool\Event\ToolInputTransformEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\Constraints\Choice;

/**
 * Coerces input values to match their definition's expected type.
 *
 * Handles generic type normalization that any invoker might need:
 * - Treats an empty string as "no value" for optional non-string inputs.
 * - Decodes JSON strings passed where a map or list is expected.
 * - Wraps scalar values in an array for list/multiple definitions.
 * - Snaps case/whitespace near-misses onto declared enum choices.
 * - Coerces string representations of booleans to actual booleans.
 * - Coerces integer 0/1 and integral float 0.0/1.0 to booleans.
 * - Coerces booleans to 0/1 for integer targets.
 * - Coerces bare numbers to their string spelling for string targets.
 * - Re-emits parsable datetime strings in the format the target declares —
 *   the datetime or date storage format when a DateTime or DateOnly
 *   constraint says which, canonical ISO 8601 otherwise — and coerces
 *   timestamp inputs from numeric strings or date strings.
 * - Coerces integral floats to actual integers.
 * - Coerces numeric strings to actual integers/floats.
 *
 * The guiding principle is to coerce only when the intended value is
 * unambiguous; anything doubtful is left untouched so typed-data validation
 * can reject it and drive the invoker's correction loop.
 *
 * Runs at priority 50 — after invoker-specific subscribers (100) that may
 * transform values (e.g., handle resolution), but before the recursive
 * subscriber (-100) that walks nested structures, so decoded or wrapped
 * structures still get per-item coercion via child dispatch.
 */
final class InputTypeCoercionSubscriber implements EventSubscriberInterface {

  use DecimalStringTrait;

  /**
   * Data types for which an empty string cannot be a meaningful value.
   */
  private const NON_STRING_TYPES = [
    'boolean',
    'integer',
    'float',
    'decimal',
    'timestamp',
    'datetime_iso8601',
    'map',
  ];

  /**
   * The timezone datetime values are stored in.
   *
   * Mirrors the datetime module's storage timezone. It is repeated here
   * rather than imported so that this module carries no dependency on an
   * optional core module.
   */
  private const STORAGE_TIMEZONE = 'UTC';

  /**
   * The storage format for values carrying both a date and a time.
   *
   * Deliberately offset-less: the value is understood to be in
   * self::STORAGE_TIMEZONE.
   */
  private const DATETIME_STORAGE_FORMAT = 'Y-m-d\TH:i:s';

  /**
   * The storage format for date-only values.
   */
  private const DATE_STORAGE_FORMAT = 'Y-m-d';

  /**
   * Accepted input formats for a target declaring a date and a time.
   *
   * Every format carries a timezone, so a value references an absolute
   * moment and is never interpreted against a guessed zone. The first two
   * mirror the formats core's serialization layer accepts for this data
   * type; the third adds fractional seconds (one to six digits), which
   * JavaScript's toISOString() and many LLM clients emit and which the
   * advertised date-time schema format allows. The storage format carries
   * no fraction, so sub-second precision is truncated on conversion. The
   * last two accept the space-separated date/time spelling — still
   * unambiguous because the offset is present — with either offset style.
   */
  private const DATETIME_INPUT_FORMATS = [
    \DateTimeInterface::RFC3339,
    \DateTimeInterface::ISO8601,
    'Y-m-d\TH:i:s.uP',
    'Y-m-d H:i:sP',
    'Y-m-d H:i:sO',
  ];

  /**
   * Accepted input formats for a date-only target.
   */
  private const DATE_INPUT_FORMATS = [
    self::DATE_STORAGE_FORMAT,
  ];

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      ToolInputTransformEvent::class => ['onInputTransform', 50],
    ];
  }

  /**
   * Coerces input values based on the definition's data type.
   */
  public function onInputTransform(ToolInputTransformEvent $event): void {
    $definition = $event->getDefinition();
    $value = $event->getValue();
    if ($value === NULL) {
      return;
    }

    $data_type = $definition->getDataType();
    $is_list = $this->isListDefinition($definition);

    // An empty string sent to an optional input that cannot hold one means
    // "no value"; required inputs keep it so validation reports the miss.
    if ($value === '' && !$definition->isRequired()
      && ($is_list || \in_array($data_type, self::NON_STRING_TYPES, TRUE))) {
      $event->setValue(NULL);
      return;
    }

    // A JSON-encoded structure passed as a string where a map or list is
    // expected. Must run before list wrapping, otherwise the JSON string
    // would be wrapped as a single item. Only accepted when the decoded
    // shape matches the target; mismatches fall through untouched.
    if (\is_string($value) && ($is_list || $data_type === 'map')) {
      $decoded = $this->decodeJsonStructure($value, $is_list);
      if ($decoded !== NULL) {
        $event->setValue($decoded);
        return;
      }
    }

    // List wrapping: if a list/multiple definition receives a value that is
    // not already a list of items, wrap it so downstream code can iterate
    // uniformly. Falsy scalars (0, '0', FALSE, '') are valid single items and
    // must be wrapped too; only NULL (no value) is left alone. A single
    // property map ({"target_id": 1}) is also a single item: it is an array,
    // but not a list, and typed data rejects it outright when a list is
    // expected. An empty array is already a valid empty list, and
    // array_is_list() reports TRUE for it, so it is left alone.
    if ($is_list && (!\is_array($value) || !\array_is_list($value))) {
      $event->setValue([$value]);
      return;
    }

    // Enum near-miss: snap case/whitespace variants onto the canonical
    // choice ("Article" → "article"). Only a unique case-insensitive match
    // is snapped; anything fuzzier stays for validation.
    if (\is_string($value) && ($choices = $this->getChoices($definition))) {
      $snapped = $this->snapToChoice($value, $choices);
      if ($snapped !== NULL) {
        $event->setValue($snapped);
        return;
      }
    }

    if ($data_type === 'boolean') {
      // Integer 0/1 and integral float 0.0/1.0 → boolean; other numbers
      // stay for validation.
      if ($value === 0 || $value === 1 || $value === 0.0 || $value === 1.0) {
        $event->setValue((bool) $value);
      }
      // Boolean coercion: string representations → actual booleans.
      // Unrecognized strings are left untouched so typed-data validation can
      // reject them, rather than silently coercing them to FALSE.
      elseif (\is_string($value)) {
        $coerced = \filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($coerced !== NULL) {
          $event->setValue($coerced);
        }
      }
      return;
    }

    // Datetime strings are re-emitted in the format the target declares.
    // Purely numeric strings are skipped as ambiguous (PHP would parse "2026"
    // as a time of day, not a year).
    if ($data_type === 'datetime_iso8601' && \is_string($value) && !\is_numeric($value)) {
      $constraints = $definition->getConstraints();
      $date_only = \array_key_exists('DateOnly', $constraints);
      $date_time = \array_key_exists('DateTime', $constraints);

      if (!$date_only && !$date_time) {
        // Without a marker constraint the accepted format is unknown, so a
        // bare Y-m-d date is left untouched rather than gaining a time and a
        // timezone the caller never supplied.
        if (\preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
          return;
        }
        try {
          // No declared target: re-emit as canonical ISO 8601 so tools that
          // are not backed by a storage format still receive one shape.
          $event->setValue((new \DateTimeImmutable($value))->format('c'));
        }
        catch (\Exception) {
          // Unparsable values stay for validation.
        }
        return;
      }

      // A declared target accepts only unambiguous formats, matching how core
      // denormalizes this data type for its own API layer. Every accepted
      // datetime format carries a timezone, so no timezone is ever guessed;
      // anything else — including a bare date for a datetime target, a
      // relative string like "tomorrow", and the empty string, which PHP
      // would otherwise read as "now" — is left untouched for validation.
      $formats = $date_only
        ? self::DATE_INPUT_FORMATS
        : self::DATETIME_INPUT_FORMATS;
      $parsed = $this->parseDeclaredDateTime($value, $formats);
      if ($parsed === NULL) {
        return;
      }
      if ($date_only) {
        // A date carries no time to convert, and converting could roll it
        // onto a different calendar day than the caller named.
        $event->setValue($parsed->format(self::DATE_STORAGE_FORMAT));
        return;
      }
      $event->setValue(
        $parsed->setTimezone(new \DateTimeZone(self::STORAGE_TIMEZONE))
          ->format(self::DATETIME_STORAGE_FORMAT)
      );
      return;
    }

    if ($data_type === 'timestamp') {
      if (\is_string($value) && \is_numeric($value)) {
        $event->setValue((int) $value);
      }
      elseif (\is_float($value) && $this->isIntegral($value)) {
        $event->setValue((int) $value);
      }
      elseif (\is_string($value) && $value !== '') {
        try {
          $event->setValue((new \DateTimeImmutable($value))->getTimestamp());
        }
        catch (\Exception) {
          // Unparsable values stay for validation.
        }
      }
      return;
    }

    // Integral float for an integer input (JSON encoders commonly produce
    // 5.0); fractional floats stay for validation.
    if ($data_type === 'integer' && \is_float($value) && $this->isIntegral($value)) {
      $event->setValue((int) $value);
      return;
    }

    // A boolean for an integer input maps to 0/1, mirroring the accepted
    // integer spellings of a boolean. Without this the primitive constraint
    // tolerates the boolean and the tool receives TRUE where the schema
    // promised an integer.
    if ($data_type === 'integer' && \is_bool($value)) {
      $event->setValue($value ? 1 : 0);
      return;
    }

    // A bare number for a string input is unambiguous ("5" is the only
    // faithful spelling of 5); without this the tool receives an int or
    // float where the schema promised a string. Booleans stay for
    // validation: neither "1" nor "true" is a canonical string form.
    if ($data_type === 'string' && (\is_int($value) || \is_float($value))) {
      $event->setValue(\is_int($value) ? (string) $value : $this->formatDecimalString($value));
      return;
    }

    // Decimals travel as strings, but the schema's numeric look invites
    // bare JSON numbers; their string form is unambiguous. A plain cast is
    // not usable here: it may produce exponent notation ("1.0E-7"), which
    // decimal validation rejects.
    if ($data_type === 'decimal' && (\is_int($value) || \is_float($value))) {
      $event->setValue(\is_int($value) ? (string) $value : $this->formatDecimalString($value));
      return;
    }

    // Numeric coercion: numeric strings → actual integers/floats, so tools
    // receive typed values (e.g. int 5, not "5"). Non-numeric strings are
    // left untouched so typed-data validation can reject them.
    if (\is_string($value) && \is_numeric($value)) {
      if ($data_type === 'integer') {
        $event->setValue((int) $value);
      }
      elseif ($data_type === 'float') {
        $event->setValue((float) $value);
      }
    }
  }

  /**
   * Determines if a definition expects a list/array value.
   */
  private function isListDefinition(mixed $definition): bool {
    // ListInputDefinition and ListOutputDefinition (list data definitions)
    // hardcode the 'list' data type, so the data type check covers those
    // classes too.
    return $definition->isMultiple() || $definition->getDataType() === 'list';
  }

  /**
   * Decodes a JSON string when its shape matches the structured target.
   *
   * @param string $value
   *   The raw string value.
   * @param bool $for_list
   *   TRUE when the target is a list/multiple definition, FALSE for a map.
   *
   * @return array|null
   *   The decoded structure, or NULL when the string is not JSON or the
   *   decoded shape does not match the target.
   */
  private function decodeJsonStructure(string $value, bool $for_list): ?array {
    $trimmed = \trim($value);
    $first = $trimmed[0] ?? '';
    if ($first !== '{' && $first !== '[') {
      return NULL;
    }
    $decoded = \json_decode($trimmed, TRUE);
    if (!\is_array($decoded)) {
      return NULL;
    }
    // A list target needs a sequential array; a map target needs an
    // associative array (or an empty object, which decodes to []).
    $shape_matches = $for_list
      ? \array_is_list($decoded)
      : ($decoded === [] || !\array_is_list($decoded));
    return $shape_matches ? $decoded : NULL;
  }

  /**
   * Extracts declared enum choices from a definition's constraints.
   *
   * @param mixed $definition
   *   The input definition.
   *
   * @return array
   *   The choices, or an empty array if none are declared.
   */
  private function getChoices(mixed $definition): array {
    $constraints = $definition->getConstraints();
    foreach (['Choice', 'AllowedValues'] as $name) {
      if (!isset($constraints[$name])) {
        continue;
      }
      $constraint = $constraints[$name];
      if ($constraint instanceof Choice) {
        return $constraint->choices ?? [];
      }
      if (\is_array($constraint)) {
        if (isset($constraint['choices'])) {
          return $constraint['choices'];
        }
        if (isset($constraint['callback']) && \is_callable($constraint['callback'])) {
          return \call_user_func($constraint['callback']);
        }
        if (\array_is_list($constraint) && $constraint !== []) {
          return $constraint;
        }
      }
    }
    return [];
  }

  /**
   * Finds the unique case-insensitive, whitespace-trimmed choice match.
   *
   * @param string $value
   *   The submitted value.
   * @param array $choices
   *   The declared choices.
   *
   * @return string|null
   *   The canonical choice when exactly one matches and differs from the
   *   submitted value, NULL otherwise.
   */
  private function snapToChoice(string $value, array $choices): ?string {
    $needle = \mb_strtolower(\trim($value));
    $matches = [];
    foreach ($choices as $choice) {
      if (\is_string($choice) && \mb_strtolower(\trim($choice)) === $needle) {
        $matches[] = $choice;
      }
    }
    if (\count($matches) === 1 && $matches[0] !== $value) {
      return $matches[0];
    }
    return NULL;
  }

  /**
   * Parses a datetime string against an allowlist of unambiguous formats.
   *
   * Strict by design: an input that does not match exactly is reported as
   * unparsable rather than guessed at, so validation can name the problem.
   * PHP's constructor would happily read '' and '   ' as the current time and
   * 'tomorrow' as a real date, none of which the caller asked for.
   *
   * @param string $value
   *   The value to parse.
   * @param string[] $formats
   *   The accepted date formats.
   *
   * @return \DateTimeImmutable|null
   *   The parsed value, or NULL when it matches no accepted format.
   */
  private function parseDeclaredDateTime(string $value, array $formats): ?\DateTimeImmutable {
    foreach ($formats as $format) {
      // The leading '!' resets fields the format does not carry, so a bare
      // date becomes midnight rather than inheriting the current time.
      $parsed = \DateTimeImmutable::createFromFormat('!' . $format, $value);
      if ($parsed === FALSE) {
        continue;
      }
      // Zero errors and zero warnings, so that a value PHP merely tolerated
      // (for example an out-of-range day it rolled forward) is not accepted.
      $errors = \DateTimeImmutable::getLastErrors();
      if ($errors === FALSE
        || (empty($errors['errors']) && empty($errors['warnings']))) {
        return $parsed;
      }
    }
    return NULL;
  }

  /**
   * Determines whether a float holds an integral value within int range.
   */
  private function isIntegral(float $value): bool {
    return \fmod($value, 1.0) === 0.0
      && $value >= (float) PHP_INT_MIN
      && $value <= (float) PHP_INT_MAX;
  }

}
