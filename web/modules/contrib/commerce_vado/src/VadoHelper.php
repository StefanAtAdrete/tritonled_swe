<?php

namespace Drupal\commerce_vado;

/**
 * The vado helper.
 */
class VadoHelper {

  /**
   * Processes vado group selections.
   *
   * The vado group selections may contain a mixture
   * of group item ids and arrays of group item ids.
   * This helper functions flattens the arrays and
   * removes any empty values.
   *
   * @param array $selections
   *   The vado group selections.
   *
   * @return array
   *   The processed vado group selections.
   */
  public static function processGroupSelections(array $selections) {
    // The selection values may contain nested array
    // if the group allows multiple selections.
    $selections = iterator_to_array(new \RecursiveIteratorIterator(
      new \RecursiveArrayIterator($selections)
    ), FALSE);

    // Remove empty values.
    return array_filter($selections);
  }

}
