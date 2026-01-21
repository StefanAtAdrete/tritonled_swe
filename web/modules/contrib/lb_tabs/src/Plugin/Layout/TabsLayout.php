<?php

namespace Drupal\lb_tabs\Plugin\Layout;

/**
 * A very advanced custom layout.
 *
 * @Layout(
 *   id = "lb_tabs_tabs",
 *   label = @Translation("Tabs"),
 *   category = @Translation("Effects"),
 *   path = "layouts/tabs",
 *   template = "lb-tabs-tabs",
 * )
 */
class TabsLayout extends LbTabsLayoutBase {

  /**
   * Sitebuilder expects labels above all in tabs.
   */
  protected function needsLabelBlockRegion() {
    return TRUE;
  }


}
