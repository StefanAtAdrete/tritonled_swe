(function ($, Drupal, drupalSettings){
  Drupal.behaviors.lb_tabs = {
    attach: function (context, settings) {
      for (var id in settings.lb_tabs_tabs) {
        var options = settings.lb_tabs_tabs[id];
        $("#" + id, context).tabs(options);
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
