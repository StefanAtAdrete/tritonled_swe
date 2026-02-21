/**
 * @file
 * Global JavaScript behaviors for TritonLED Radix theme.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Global initialization.
   */
  Drupal.behaviors.tritonledRadixGlobal = {
    attach: function (context, settings) {
      // Global JavaScript initialization
      // Bootstrap 5 is already loaded by Radix
    }
  };

  /**
   * Convert Bootstrap 4 data attributes to Bootstrap 5.
   * views_bootstrap module generates BS4 attributes (data-ride, data-slide etc).
   * Bootstrap 5 requires data-bs-* prefix.
   */
  Drupal.behaviors.tritonledBootstrapCompat = {
    attach: function (context, settings) {
      once('bs4-to-bs5', '.carousel', context).forEach(function (el) {
        // Convert data attributes on carousel element
        ['ride', 'interval', 'pause', 'wrap', 'keyboard'].forEach(function (attr) {
          if (el.hasAttribute('data-' + attr)) {
            el.setAttribute('data-bs-' + attr, el.getAttribute('data-' + attr));
          }
        });
        // Convert data-slide on prev/next controls
        el.querySelectorAll('[data-slide]').forEach(function (btn) {
          btn.setAttribute('data-bs-slide', btn.getAttribute('data-slide'));
        });
        // Init Bootstrap 5 carousel
        new bootstrap.Carousel(el);
      });
    }
  };

  /**
   * Make hero carousel slides clickable.
   * Reads URL from hidden .hero-slide-url element and navigates on click.
   */
  Drupal.behaviors.tritonledHeroSlideLink = {
    attach: function (context, settings) {
      once('hero-slide-link', '.carousel-item', context).forEach(function (slide) {
        var urlEl = slide.querySelector('.hero-slide-url');
        if (!urlEl) return;
        var url = urlEl.textContent.trim();
        if (!url) return;
        slide.style.cursor = 'pointer';
        slide.addEventListener('click', function (e) {
          // Don't navigate if clicking carousel controls
          if (e.target.closest('.carousel-control-prev, .carousel-control-next, .carousel-indicators')) return;
          window.location.href = url;
        });
      });
    }
  };

})(Drupal, once);
