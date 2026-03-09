/**
 * @file
 * Tvingar Blazy att ladda om bilder efter Commerce variation AJAX.
 *
 * Problem: Commerce's insert-kommando ersätter gallery-noden vid variantbyte.
 * Den nya noden innehåller rätt bild i data-src, men Blazy's b-loaded/is-b-loaded
 * klasser följer med från föregående variant — Blazy tror bilden redan är laddad
 * och skriver aldrig över src med den nya bilden.
 *
 * Lösning: MutationObserver på gallery-nodens img-element. När src ändras
 * (av Drupal's insert) men bilden är fel — rensa b-loaded och trigga blazy.load().
 *
 * Varför subtree observer på gallery-noden:
 * Drupal's insert-kommando ersätter innerHTML på commerce-variation-block--gallery--*
 * vilket triggar childList-mutations inuti noden. Vi observerar gallery-noden
 * med subtree: true för att fånga när img-elementet byts ut.
 *
 * Debuggat 2026-03-08:
 * - insert-kommando träffar .commerce-variation-block--gallery--5 ✓
 * - DOM uppdateras med ny nod (sameNode: false) ✓
 * - img.className = "b-lazy b-loaded" — Blazy laddar inte om ✓
 * - data-src = null efter insert — src innehåller gammal bild ✓
 *
 * Se: /docs/03-solutions/variation-gallery-ajax.md
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.tritonledVariationGalleryReinit = {
    attach: function (context, settings) {
      var gallerySelector = '[class*="commerce-variation-block--gallery--"]';

      // Använd once() på document för att registrera en global observer
      // som överlever DOM-byten av gallery-noden.
      once('tritonled-gallery-reinit', 'body', context).forEach(function (body) {

        // Observera document.body för att fånga när gallery-noden ersätts.
        // Drupal's insert byter ut hela noden — vi behöver fånga den nya.
        var bodyObserver = new MutationObserver(function (mutations) {
          mutations.forEach(function (mutation) {
            mutation.addedNodes.forEach(function (node) {
              if (node.nodeType !== Node.ELEMENT_NODE) {
                return;
              }

              // Hitta gallery-noden — antingen noden själv eller ett barn.
              var gallery = null;
              if (node.className && node.className.includes('commerce-variation-block--gallery--')) {
                gallery = node;
              } else {
                gallery = node.querySelector ? node.querySelector(gallerySelector) : null;
              }

              if (!gallery) {
                return;
              }

              // Rensa Blazy's loaded-state på alla bilder i den nya gallery-noden
              // så att Blazy laddar om dem med korrekt src.
              var imgs = gallery.querySelectorAll('img.b-lazy, img.b-loaded');
              imgs.forEach(function (img) {
                img.classList.remove('b-loaded', 'b-lazy');
                var parent = img.closest('.media--blazy, .b-lazy-inline');
                if (parent) {
                  parent.classList.remove('is-b-loaded');
                }
              });

              // Trigga Blazy att ladda bilderna i den nya noden.
              if (Drupal.blazy && typeof Drupal.blazy.load === 'function') {
                Drupal.blazy.load(gallery);
              } else if (Drupal.blazy && typeof Drupal.blazy.init === 'function') {
                Drupal.blazy.init(gallery);
              }
            });
          });
        });

        bodyObserver.observe(body, { childList: true, subtree: true });
      });
    }
  };

}(Drupal, once));
