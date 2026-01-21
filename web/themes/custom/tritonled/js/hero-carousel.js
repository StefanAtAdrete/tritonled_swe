/**
 * Hero Carousel functionality
 * Adds active class to first slide and creates indicators
 */
(function (Drupal) {
  'use strict';

  Drupal.behaviors.heroCarousel = {
    attach: function (context, settings) {
      const carousel = document.getElementById('heroCarousel');
      
      if (!carousel) {
        return;
      }

      // Only run once
      if (carousel.classList.contains('js-processed')) {
        return;
      }
      carousel.classList.add('js-processed');

      const items = carousel.querySelectorAll('.carousel-item');
      const indicators = carousel.querySelector('.carousel-indicators');

      if (!items.length || !indicators) {
        return;
      }

      // REMOVE all active classes first
      items.forEach((item) => {
        item.classList.remove('active');
      });

      // Configure all videos for autoplay
      carousel.querySelectorAll('video').forEach(function(video) {
        video.setAttribute('muted', '');
        video.setAttribute('autoplay', '');
        video.setAttribute('loop', '');
        video.setAttribute('playsinline', ''); // For iOS
        video.muted = true; // Ensure muted
      });

      // Add active class ONLY to first item and create indicators
      items.forEach((item, index) => {
        if (index === 0) {
          item.classList.add('active');
        }

        // Create indicator button
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.setAttribute('data-bs-target', '#heroCarousel');
        btn.setAttribute('data-bs-slide-to', index);
        btn.setAttribute('aria-label', 'Slide ' + (index + 1));
        
        if (index === 0) {
          btn.classList.add('active');
          btn.setAttribute('aria-current', 'true');
        }
        
        indicators.appendChild(btn);
      });

      // Manually initialize Bootstrap carousel
      if (typeof bootstrap !== 'undefined' && bootstrap.Carousel) {
        const bsCarousel = new bootstrap.Carousel(carousel, {
          interval: 5000,
          ride: 'carousel'
        });

        // Handle video playback
        carousel.addEventListener('slide.bs.carousel', function (event) {
          // Pause all videos
          carousel.querySelectorAll('video').forEach(function(video) {
            video.pause();
          });
        });

        carousel.addEventListener('slid.bs.carousel', function (event) {
          // Play video in active slide
          const activeSlide = carousel.querySelector('.carousel-item.active');
          const video = activeSlide.querySelector('video');
          
          if (video) {
            video.currentTime = 0;
            video.play().catch(function(error) {
              console.log('Video autoplay prevented:', error);
            });
          }
        });

        // Play first video immediately
        const firstVideo = carousel.querySelector('.carousel-item.active video');
        if (firstVideo) {
          firstVideo.play().catch(function(error) {
            console.log('Video autoplay prevented:', error);
          });
        }
      }
    }
  };

})(Drupal);
