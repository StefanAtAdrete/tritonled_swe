<?php

namespace Drupal\tool\Normalizer;

use Drupal\Core\TypedData\Plugin\DataType\Email;
use Drupal\Core\TypedData\Plugin\DataType\FloatData;
use Drupal\Core\TypedData\Plugin\DataType\IntegerData;
use Drupal\Core\TypedData\Plugin\DataType\Timestamp;
use Drupal\Core\TypedData\Type\BooleanInterface;
use Drupal\Core\TypedData\Type\DateTimeInterface;
use Drupal\Core\TypedData\Type\DecimalInterface;
use Drupal\Core\TypedData\Type\DurationInterface;
use Drupal\Core\TypedData\Type\StringInterface;
use Drupal\Core\TypedData\Type\UriInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\serialization\Normalizer\ComplexDataNormalizer;

/**
 * Normalizes Map data definitions to JSON Schema.
 */
class LegacyTypedDataNormalizer extends ComplexDataNormalizer {

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
  protected function doNormalize($object, $format = NULL, array $context = []): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizationSchema(mixed $object, array $context = []): array {
    $schema = match (TRUE) {
      $object instanceof BooleanInterface => [
        'type' => 'boolean',
      ],
      // Timestamp extends IntegerData and DateTimeIso8601 extends StringData,
      // so the datetime arms must precede the integer/string arms.
      $object instanceof Timestamp => [
        'type' => 'integer',
      ],
      $object instanceof DateTimeInterface => [
        'type' => 'string',
        'format' => 'date-time',
      ],
      $object instanceof DecimalInterface => [
        'type' => 'string',
        'pattern' => ContextDefinitionNormalizer::DECIMAL_PATTERN,
      ],
      $object instanceof DurationInterface => [
        'type' => 'string',
        'format' => 'duration',
      ],
      $object instanceof Email => [
        'type' => 'string',
        'format' => 'email',
      ],
      $object instanceof FloatData => [
        'type' => 'number',
      ],
      $object instanceof IntegerData => [
        'type' => 'integer',
      ],
      $object instanceof UriInterface => [
        'type' => 'string',
        'format' => 'uri',
      ],
      $object instanceof StringInterface => [
        'type' => 'string',
      ],
      TRUE => [],
    };

    // @todo Add enum support.
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    if ($format !== 'json_schema') {
      return [];
    }
    // On cores with native json_schema support (11.1+),
    // NormalizerBase::checkFormat() denies the format to normalizers without
    // explicit format support, so supportsNormalization() returns FALSE and
    // core's schema normalizers win. On older cores checkFormat() is
    // permissive and this normalizer is selected. The interface must be
    // declared concretely: a '*' wildcard never matches NormalizerBase's
    // instanceof-based supportsNormalization() filter, which left this
    // normalizer unselectable on the old cores it exists for.
    return [TypedDataInterface::class => TRUE];
  }

}
