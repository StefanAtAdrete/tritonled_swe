<?php

namespace Drupal\lb_tabs\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;

/**
 * A very advanced custom layout.
 *
 * @Layout(
 *   id = "lb_tabs_accordion",
 *   label = @Translation("Accordion"),
 *   category = @Translation("Effects"),
 *   path = "layouts/accordion",
 *   template = "lb-tabs-accordion",
 * )
 */
class AccordionLayout extends LbTabsLayoutBase {

  /**
   * Sitebuilder expects labels above content in accordion.
   */
  protected function needsLabelBlockRegion() {
    return FALSE;
  }


}
