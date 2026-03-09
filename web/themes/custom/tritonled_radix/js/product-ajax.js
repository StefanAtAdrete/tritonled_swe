/**
 * @file
 * Re-initializes Blazy/Splide after Commerce variation AJAX replacement.
 *
 * Commerce replaces field_variation_media DOM-noden via ReplaceCommand.
 * Blazy/Splide initieras vid sidladdning och reagerar inte på DOM-ändringar.
 * Vi lyssnar på ajaxComplete och re-mountar Blazy på den nya noden.
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.tritonledProductAjax = {
    attach: function (context, settings) {
      // Använd once() för att bara registrera event-lyssnaren en gång.
      once('tritonled-product-ajax', 'body', context).forEach(function (body) {
        body.addEventListener('ajaxComplete', function () {
          var selector = '[class*="variation-field--variation_field_variation_media"]';
          var el = document.querySelector(selector);
          if (!el) {
            return;
          }

          // Ta bort Splide/Blazy init-klasser så Blazy kan re-initieras.
          el.classList.remove('is-initialized', 'is-mounted', 'is-active', 'is-blazy');

          // Re-initialisera Blazy på den nya noden.
          if (Drupal.blazy && typeof Drupal.blazy.init === 'function') {
            Drupal.blazy.init(el);
          }
        });
      });
    }
  };

}(Drupal, once));
