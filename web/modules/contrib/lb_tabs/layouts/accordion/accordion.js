(function ($, Drupal, drupalSettings){
  Drupal.behaviors.lb_tabs_accordion = {
    attach: function (context, settings) {
      for (var id in settings.lb_tabs_accordion) {
        var options = settings.lb_tabs_accordion[id];
        options['heightStyle'] = 'content';
        $("#" + id, context).accordion(options);
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
