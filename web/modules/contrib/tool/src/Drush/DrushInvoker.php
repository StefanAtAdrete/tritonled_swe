<?php

declare(strict_types=1);

namespace Drupal\tool\Drush;

use Drupal\tool\Tool\Invoker;

/**
 * Identifies the Drush commands as a tool invoker.
 *
 * A Drush process is one-shot: nothing carries state between two commands,
 * so the invoker declares no capabilities. Entity handles for the CLI are
 * tracked in https://git.drupalcode.org/project/tool/-/work_items/3582977.
 *
 * @internal
 */
final class DrushInvoker {

  public const ID = 'drush';

  /**
   * Builds the invoker the Drush commands pass to createInstance().
   */
  public static function create(): Invoker {
    return new Invoker(self::ID);
  }

}
