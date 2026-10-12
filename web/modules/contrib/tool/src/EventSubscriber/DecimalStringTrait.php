<?php

declare(strict_types=1);

namespace Drupal\tool\EventSubscriber;

/**
 * Spells a float as the decimal string the advertised schema describes.
 */
trait DecimalStringTrait {

  /**
   * Formats a float as a fixed-point decimal string.
   *
   * Rounds to 15 significant digits, the number of decimal digits a double
   * always carries exactly, and trims insignificant trailing zeros. Below
   * 1e15, a number written with up to 15 significant digits comes back as
   * written ("1234.567", not "1234.56700000000001"), and binary artifacts
   * beyond that collapse ("0.3", not "0.30000000000000004"). No exponent
   * notation is emitted; at 1e15 and above every integer digit of the
   * double is spelled, artifacts included, as the decimal pattern requires
   * whole integer digits.
   *
   * @param float $value
   *   The float value.
   *
   * @return string
   *   The fixed-point decimal string.
   */
  private function formatDecimalString(float $value): string {
    if ($value == 0.0) {
      return '0';
    }
    // The decimal places that leave 15 significant digits: 14 after the
    // leading digit, less the exponent (the count of digits before the
    // decimal point, minus the leading one).
    $decimals = \max(0, 14 - (int) \floor(\log10(\abs($value))));
    $formatted = \number_format($value, $decimals, '.', '');
    if (\str_contains($formatted, '.')) {
      $formatted = \rtrim(\rtrim($formatted, '0'), '.');
    }
    return $formatted;
  }

}
