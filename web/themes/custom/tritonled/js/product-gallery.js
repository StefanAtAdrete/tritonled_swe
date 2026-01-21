/**
 * Product Gallery JavaScript - VERSION 2024-12-28 07:50
 * CUSTOM ENDPOINT VERSION
 */

(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.productGallery = {
    attach: function (context, settings) {
      const $carousel = $('.carousel', context);
      if (!$carousel.length) return;
      const carousel = bootstrap.Carousel.getOrCreateInstance($carousel[0]);
      once('carousel-controls', '.carousel-control-prev, .carousel-control-next', context).forEach(function(btn) {
        const $btn = $(btn);
        $btn.on('click', function(e) {
          e.preventDefault();
          $btn.hasClass('carousel-control-prev') ? carousel.prev() : carousel.next();
        });
      });
      const $thumbnails = $('.thumbnails img', context);
      once('gallery-thumb', $thumbnails.toArray(), context).forEach(function(thumb, index) {
        $(thumb).on('click', function(e) {
          e.preventDefault();
          carousel.to(index);
        });
      });
      $carousel.on('slide.bs.carousel', function (e) {
        $thumbnails.removeClass('active').eq(e.to).addClass('active');
      });
      if ($thumbnails.length > 0) $thumbnails.eq(0).addClass('active');
    }
  };

  Drupal.behaviors.productGalleryAjax = {
    attach: function (context, settings) {
      // Skip once() - we need this to run on every attach
      if (context !== document) return; // Only run on full document
      
      $(document).off('ajaxComplete.galleryReload').on('ajaxComplete.galleryReload', function(event, xhr, ajaxSettings) {
          if (ajaxSettings.url && ajaxSettings.url.includes('ajax_form=1')) {
            const urlParams = new URLSearchParams(ajaxSettings.url.split('?')[1]);
            const variationId = urlParams.get('v');
            if (variationId) {
              console.log('*** NEW VERSION *** Loading variation', variationId);
              $.ajax({
                url: '/product-gallery/' + variationId,
                dataType: 'json',
                success: function(data) {
                  console.log('*** ENDPOINT RESPONSE ***', data);
                  if (data.carousel && data.thumbnails) {
                    $('.view-display-id-block_1').html(data.carousel);
                    $('.view-display-id-attachment_1').html(data.thumbnails);
                    console.log('*** GALLERY UPDATED ***');
                    Drupal.attachBehaviors(document.querySelector('.view-display-id-block_1'));
                    Drupal.attachBehaviors(document.querySelector('.view-display-id-attachment_1'));
                  }
                },
                error: function(xhr, status, error) {
                  console.error('*** ENDPOINT ERROR ***', error, xhr.responseText);
                }
              });
            }
          }
        });
    }
  };

})(jQuery, Drupal, once);
