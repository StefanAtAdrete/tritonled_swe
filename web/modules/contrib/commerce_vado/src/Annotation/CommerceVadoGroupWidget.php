<?php

namespace Drupal\commerce_vado\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the vado group widget plugin annotation object.
 *
 * Plugin namespace: Plugin\Commerce\VadoGroupWidget.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class CommerceVadoGroupWidget extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The vado group widget label.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * Whether the vado group widget has the option to allow multiple selections.
   *
   * @var bool
   */
  public $allows_multiple = FALSE;

}
