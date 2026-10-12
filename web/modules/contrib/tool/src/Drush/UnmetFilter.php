<?php

declare(strict_types=1);

namespace Drupal\tool\Drush;

/**
 * Values of the --unmet option on tool:list and tool:search.
 *
 * @internal
 */
enum UnmetFilter: string {

  case Show = 'show';
  case Hide = 'hide';
  case Only = 'only';

  /**
   * Whether a tool with the given status stays in the listing.
   */
  public function keeps(RequirementStatus $status): bool {
    return match ($this) {
      self::Show => TRUE,
      self::Hide => $status->met,
      self::Only => !$status->met,
    };
  }

  /**
   * Lists the accepted option values for error messages.
   */
  public static function describeValues(): string {
    return implode(', ', array_column(self::cases(), 'value'));
  }

}
