(function ($, Drupal) {
  'use strict';

  /**
   * Override lb_tabs behavior to use Bootstrap tabs instead of jQuery UI.
   */
  Drupal.behaviors.lb_tabs = {
    attach: function (context, settings) {
      once('lb-tabs-bootstrap', '.lb-tabs-tabs', context).forEach(function (wrapper) {
        var $wrapper = $(wrapper);

        // Destroy jQuery UI if already initialized.
        if ($wrapper.hasClass('ui-tabs')) {
          try { $wrapper.tabs('destroy'); } catch(e) {}
        }

        // Initialize Bootstrap tabs.
        $wrapper.find('[data-bs-toggle="tab"]').each(function () {
          this.addEventListener('click', function (e) {
            e.preventDefault();
            bootstrap.Tab.getOrCreateInstance(this).show();
          });
        });

        // Show first tab.
        var firstTab = $wrapper.find('[data-bs-toggle="tab"]').first()[0];
        if (firstTab) {
          bootstrap.Tab.getOrCreateInstance(firstTab).show();
        }
      });
    }
  };

})(jQuery, Drupal);
