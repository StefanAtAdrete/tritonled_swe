<?php

declare(strict_types=1);

namespace Drupal\tool\EventSubscriber;

use Drupal\tool\Event\ToolOutputTransformEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Coerces output values to the wire format their advertised schema describes.
 *
 * The schema for an output comes from the serializer, but the value never
 * goes through it: getFormattedResult() carries what the tool returned. For
 * four data types the two disagree, and an invoker that validates results
 * against the advertised schema, such as an MCP client, rejects the value:
 * - timestamp: core 11.1+ describes it as an RFC 3339 date-time string and
 *   the tool returns a Unix integer. Older cores describe it as an integer
 *   (LegacyTypedDataNormalizer), so the value is left alone there.
 * - decimal: described as a patterned string on every core, returned as a
 *   float or an int.
 * - datetime_iso8601 with the DateTime constraint: described as an RFC 3339
 *   date-time string, returned in the offset-less storage format.
 * - map: described as an object, and a PHP array whose keys run 0, 1, 2
 *   in order, including an empty one, encodes as a JSON array.
 *
 * Only getFormattedResult() sees the converted values. getResult() and
 * getOutputValue() keep the PHP values for callers that compute with them.
 *
 * Runs at priority 50, after invoker-specific subscribers (100) and before
 * the recursive subscriber (-100), which dispatches an event per item of a
 * list or map so nested members are converted through this same method.
 */
final class OutputTypeCoercionSubscriber implements EventSubscriberInterface {

  use DecimalStringTrait;

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
   */
  private const DATETIME_STORAGE_FORMAT = 'Y-m-d\TH:i:s';

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      ToolOutputTransformEvent::class => ['onOutputTransform', 50],
    ];
  }

  /**
   * Converts an output value to its advertised wire format.
   */
  public function onOutputTransform(ToolOutputTransformEvent $event): void {
    $value = $event->getValue();
    $definition = $event->getDefinition();

    // json_encode() writes a PHP array whose keys run 0, 1, 2 in order,
    // including an empty one, as a JSON array, where the schema says object;
    // it writes an \ArrayObject as an object whatever its keys (#3583073).
    // Every list-keyed map is wrapped, empty or not: such a map is
    // free-form in practice (declared properties have string keys), so it
    // has no property definitions for the recursive subscriber to walk. A
    // multiple map is a list, which a JSON array spells correctly.
    if (\is_array($value) && \array_is_list($value) && $definition->getDataType() === 'map' && !$definition->isMultiple()) {
      $event->setValue(new \ArrayObject($value));
      return;
    }
    // Lists and string-keyed maps are walked by the recursive subscriber,
    // which dispatches an event per item carrying the item definition.
    if (!\is_scalar($value)) {
      return;
    }

    switch ($definition->getDataType()) {
      case 'timestamp':
        if (\is_int($value) && self::timestampsAdvertiseDateTime()) {
          $event->setValue((new \DateTimeImmutable('@' . $value))->format(\DateTimeInterface::RFC3339));
        }
        return;

      case 'decimal':
        if (\is_int($value)) {
          $event->setValue((string) $value);
        }
        elseif (\is_float($value)) {
          $event->setValue($this->formatDecimalString($value));
        }
        return;

      case 'datetime_iso8601':
        if (\is_string($value) && \array_key_exists('DateTime', $definition->getConstraints())) {
          $parsed = \DateTimeImmutable::createFromFormat(
            '!' . self::DATETIME_STORAGE_FORMAT,
            $value,
            new \DateTimeZone(self::STORAGE_TIMEZONE),
          );
          // Only the exact storage spelling is converted; a value that
          // already carries an offset, or anything else, is left as is.
          if ($parsed !== FALSE && $parsed->format(self::DATETIME_STORAGE_FORMAT) === $value) {
            $event->setValue($parsed->format(\DateTimeInterface::RFC3339));
          }
        }
        return;
    }
  }

  /**
   * Whether the active core describes a timestamp as a date-time string.
   *
   * Core 11.1 added schema-aware normalizers, whose TimestampNormalizer
   * inherits the date-time schema from DateTimeNormalizer. Before that the
   * module's LegacyTypedDataNormalizer describes timestamps as integers, so
   * the integer value already conforms.
   */
  private static function timestampsAdvertiseDateTime(): bool {
    return \version_compare(\Drupal::VERSION, '11.1', '>=');
  }

}
