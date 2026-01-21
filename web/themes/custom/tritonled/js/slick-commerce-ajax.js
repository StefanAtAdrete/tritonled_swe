/**
 * @file
 * Slick Commerce AJAX integration.
 * Handles proper Slick re-initialization when product variations change via AJAX.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Destroy Slick instances before AJAX replaces content.
   */
  $(document).on('commerce_product_variation_ajax_start', function (event, context) {
    // Find all initialized Slick carousels in the AJAX context
    $('.slick--initialized', context).each(function () {
      var $slider = $(this);
      
      // Only destroy if Slick is actually initialized
      if ($slider.hasClass('slick-initialized')) {
        $slider.slick('unslick');
      }
    });
  });

  /**
   * Alternative: Listen to standard Drupal AJAX events
   */
  $(document).ajaxComplete(function (event, xhr, settings) {
    // Only act on Commerce variation AJAX calls
    if (settings.url && settings.url.indexOf('/product/') !== -1) {
      // Small delay to ensure DOM is updated
      setTimeout(function () {
        // Trigger Drupal behaviors to re-initialize Slick
        Drupal.attachBehaviors(document);
      }, 100);
    }
  });

})(jQuery, Drupal);
