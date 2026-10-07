(function (Drupal, PhotoSwipeDynamicCaption) {
  /**
   * Adds caption plugin to the photoswipe.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.photoswipeCaption = {
    /**
     * Adds caption plugin to the photoswipe.
     */
    attach(context, settings) {
      const captionOptions =
        settings?.photoswipe?.options?.captionOptions || {};
      const allowHtmlCaptions =
        settings?.photoswipe?.options?.allowHtmlCaptions || false;

      // Attaches caption plugin.
      once('photoswipeCaption', '.photoswipe-gallery', context).forEach(
        (gallery) => {
          gallery.addEventListener('photoswipeLightboxBuild', (e) => {
            const lightbox = e.detail.lightbox;

            new PhotoSwipeDynamicCaption(lightbox, {
              captionContent: (slide) => {
                const title = slide.data.element.getAttribute('data-overlay-title') || '';
                // Sanitize with DOMPurify when opted in and available;
                // otherwise escape as plain text.
                if (allowHtmlCaptions && typeof DOMPurify !== 'undefined') {
                  return DOMPurify.sanitize(title);
                }
                return Drupal.checkPlain(title);
              },
              ...captionOptions,
            });
          });
        },
      );
    },
  };
  // eslint-disable-next-line
})(Drupal, PhotoSwipeDynamicCaption);
