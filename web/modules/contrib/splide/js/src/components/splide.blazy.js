/**
 * @file
 * Provides Splide extensions for Blazy.
 *
 * @todo remove if already taken care of by Blazy 4.x ObserverContext.
 */

(function ($, _ds) {

  'use strict';

  var Blazy = function (Splide, Components) {
    var root = Splide.root;
    var _firstView = true;

    return {
      mount: function () {
        var me = this;

        Splide.on('mounted.spb', function (e) {
          me.preload(false);
        });

        Splide.on('move.spb', function (e) {
          me.preload(true);
        });

        Splide.on('moved.spb', function (e) {
          _firstView = false;
        });

      },

      /**
       * Blazy is not loaded on perPage > 1 with type `loop`/ infinite, reload.
       *
       * @param {bool} ahead
       *   Whether to lazyload ahead (at move/ beforeChange event), or not.
       *
       * @todo revert if any issue in Blazy 4.x already taken care of by
       * ObserverContext.
       */
      preload: function (ahead) {
        if (_firstView) {
          return;
        }

        setTimeout(function () {
          // var blazy = '.b-lazy:not(.b-loaded)';
          // var visible = '.is-visible ' + blazy;
          // var el = $.find(root, blazy);

          // if ($.isElm(el)) {
          // var els = $.findAll(root, ahead ? blazy : visible);
          // if (els.length) {
          // _blazy.init.load(els);
          // }
          // }

          // Cleans up preloader if any named b-loader due to clones.
          var preloader = $.find(root, '.b-loaded ~ .b-loader');
          $.remove(preloader);
        }, 100);
      }
    };
  };

  _ds.extend({
    Blazy: Blazy
  });

})(dBlazy, dSplide);
