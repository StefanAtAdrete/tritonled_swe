/**
 * @file
 * Triggers Blazy lazy-load after Commerce variation AJAX replaces the media field.
 *
 * Commerce product uses its own AJAX system (not Drupal's ajaxComplete).
 * It replaces the variation media DOM node via ReplaceCommand on every
 * attribute change. The server returns the node with Blazy lazy-load
 * (data-src attributes) unresolved — Blazy needs to process them.
 *
 * We do NOT strip Splide/Blazy init classes — Splide is already initialized
 * server-side and the markup is correct. We only need Blazy to load the
 * lazy images in the new node.
 *
 * Why MutationObserver and not ajaxComplete:
 * Commerce does not use Drupal's AJAX system for variation changes —
 * it has its own form-based AJAX. ajaxComplete never fires on variation change.
 *
 * See: /docs/03-solutions/product-ajax-library-attach.md
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.tritonledVariationMedia = {
    attach: function (context, settings) {
      var mediaSelector = '[class*="variation_field_variation_media"]';

      once('tritonled-variation-media', mediaSelector, context).forEach(function (el) {
        var parent = el.parentElement;
        if (!parent) {
          return;
        }

        var observer = new MutationObserver(function (mutations) {
          mutations.forEach(function (mutation) {
            mutation.addedNodes.forEach(function (node) {
              if (node.nodeType !== Node.ELEMENT_NODE) {
                return;
              }
              if (!node.className || !node.className.includes('variation_field_variation_media')) {
                return;
              }

              // Trigger Blazy to load lazy images in the new node.
              if (Drupal.blazy && typeof Drupal.blazy.load === 'function') {
                Drupal.blazy.load(node);
              }
            });
          });
        });

        observer.observe(parent, { childList: true, subtree: false });
      });
    }
  };

}(Drupal, once));
