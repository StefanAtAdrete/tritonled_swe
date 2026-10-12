<?php

namespace Drupal\tool\Plugin\DataType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\tool\TypedData\Type\TextInterface;

/**
 * The text data type.
 *
 * The generic ID is deliberate (#3582963): "text" is a natural, core-aligned
 * name for long-form string data, and other modules registering it (e.g. the
 * typed_data contrib module) are expected to mean the same thing by it.
 */
#[DataType(
  id: "text",
  label: new TranslatableMarkup("Text")
)]
class TextData extends StringData implements TextInterface {}
