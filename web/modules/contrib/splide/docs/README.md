
## <a name="top"> </a>CONTENTS OF THIS FILE
## @todo: update from Slick docs, not all below apply to Splide.

 * [Introduction](#introduction)
 * [Requirements](#requirements)
 * [Plugins](#plugins)
 * [Recommended modules](#recommended-modules)
 * [Features](#features)
 * [Installation](#installation)
 * [Configuration](#configuration)
 * [Splide Formatters](#formatters)
 * [Splide Paragraphs](#paragraphs)
 * [Splide Views](#views)
 * [Strategic Optimization Checklist](#optimization)
 * [Troubleshooting](#troubleshooting)
 * [FAQ](#faq)
 * [Contribution](#contribution)
 * [Maintainers](#maintainers)

***
## <a name="introduction"></a>INTRODUCTION

Visit **/admin/help/splide_ui** once Splide UI installed to read it in comfort.

Splide is a vanilla JavaScript slideshow/carousel solution leveraging
[Splide](https://github.com/Splidejs/splide).

Please start with the very basic working **Splide X** only if
trouble to build splides. Spending 5 minutes or so will save you hours in
building more complex slideshows.

Splide is inspired by Slick as a module or library, so it has _almost_ the same
structure and power as Slick, only different terminologies. While it has a few
improvements, aside from being vanilla JavaScript, that is without JQuery, a few
issues might or might not be resolved, or inherited.

### Two obvious added values from Slick:
* Splide can have navigation even for Vanilla versions.
* Vanilla JavaScript with _modular_ plugins.

***
## <a name="first"> </a>FIRST THINGS FIRST!
Read more at:
* [Github](https://git.drupalcode.org/project/blazy/-/blob/3.0.x/docs/README.md#first-things-first)
* [Blazy UI](/admin/help/blazy_ui#first)

***
## <a name="requirements"> </a>REQUIREMENTS
1. Splide library at minimum `v4`, tested `4.1.4`, since `2.x`:
   * Download Splide archive from [Splide releases](https://github.com/Splidejs/splide/releases)
   * Extract and rename it to `splide`, so the assets are at:
     + **/libraries/splide/dist/css/splide-core.css**
     + **/libraries/splide/dist/css/splide.min.css** (optional)
     + **/libraries/splide/dist/js/splide.min.js**
     + Or any path supported by core library finder as per Drupal 8.9+. If using
       composer, the library directory is `splidejs--splide`. They are fine.
       The folder `splidejs--splide` takes precedence over `splide`. Be sure
       versions are expected/ updated, including Splide extensions.

2. [Blazy 2.x|3.x](https://drupal.org/project/blazy), to reduce DRY stuffs, and
   as a bonus, advanced lazyloading such as delay lazyloading for below-fold
   sliders,  iframe, (fullscreen) CSS background lazyloading, breakpoint
   dependent multi-serving images, lazyload ahead for smoother UX.
   Check out Blazy installation guides!

***
## <a name="plugins"> </a>PLUGINS
Not all plugins are supported or implemented, below are:

1. [AutoScroll](https://github.com/Splidejs/splide-extension-auto-scroll):
   + Splide v4: v0.5.3. Splide v3: v0.3.7.
   + `/libraries/splidejs--splide-extension-auto-scroll` (via packagist)
   + `/libraries/splide-extension-auto-scroll` (via github)
   + Or any supported path by priority, and they must have
     `/dist/js/splide-extension-auto-scroll.min.js`.

2. [Intersection](https://github.com/Splidejs/splide-extension-intersection):
   + Splide v4: v0.2.0. Splide v3: v0.1.6.
   + `/libraries/splidejs--splide-extension-intersection` (via packagist)
   + `/libraries/splide-extension-intersection` (via github)
   + Or any supported path by priority, and they must have
     `/dist/js/splide-extension-intersection.min.js`.

Packagist always takes precedence over Github.
Unless explicitly supported, the reasons for not being supported are:

* `Grid` has already existing non-js implementation via `Blazy Grid` including
  `Grid Foundation`, `CSS3 Columns Masonry`, `Native Grid` (both layouts:
  two-dimensional ala `GridStack` and one-dimensional ala `Masonry`).
* `Video` has already existing battle-tested GDPR-friendly media player via
  `Image to iframe` under `Media switch` option.
* Other plugins, nobody need them, yet. Patches are welcome.


***
## <a name="installation"> </a>INSTALLATION
Be sure to read the entire docs and form descriptions before working with
Splide to avoid headaches for just ~15-minute read.

1. **MANUAL:**

   Install the module as usual, more info can be found on:

   [Installing Drupal 8 Modules](https://drupal.org/node/1897420)

2. **COMPOSER:**

   ```
   $ composer require npm-asset/splidejs--splide:^4 \
   drupal/splide
   ```
   See [Blazy composer](/admin/help/blazy_ui#composer) for details.
   Bleeding edge releases might not be available weeks after releases at
   Packagist via `npm-asset`, yet. Please go github.
   [Read more](https://github.com/hiqdev/asset-packagist/issues/139).


***
## <a name="configuration"> </a>CONFIGURATION
Visit the following to configure Splide:

1. `/admin/config/media/splide`

   Enable Splide UI sub-module first, otherwise regular 404/403.

2. Visit any entity types:
   + `/admin/structure/types`
   + `/admin/structure/block/block-content/types`
   + `/admin/structure/media`
   + `/admin/structure/paragraphs_type`
   + etc.

   Use Splide as a formatter under **Manage display** for multi-value fields:
   Image, Media, Paragraphs, Entity reference, or even Text.
   Check out [SPLIDE FORMATTERS](#formatters) section for details.

3. `/admin/structure/views`

   Use Splide as standalone blocks, or pages.
   Check **Use field template** under **Style** if standalone, else unchecked.

4. Specific for `Pagination text` option under `Overridable optionset` >
   `Pagination`. this particular option will:
   - be available if you have the same text field (says `Title`) on **ALL**
     Media entities, or any fieldable entities, excluding Vanilla.
     If you add more media types, be sure to re-add it, else the option is gone.
   - be NOT available for Vanilla, plain Image, Text, etc.


***
## <a name="recommended-modules"> </a>RECOMMENDED MODULES
Splide supports enhancements and more complex layouts.

### OPTIONAL
* Responsive Image.
* Media contribs.
* [Colorbox](https://drupal.org/project/colorbox), to have grids/slides that
   open up image/ video in overlay.
* Any blazy-supported lightboxes, idem ditto.
* [Paragraphs](https://drupal.org/project/paragraphs), to get more complex
  slides at field level.


### SUB-MODULES
The Splide module has several sub-modules:
* Splide UI, included, to manage optionsets, can be uninstalled at production.

* Splide X, included
  to get up and running Splide quickly.

* [Splidebox](https://drupal.org/project/splidebox)
  to have Splide within lightbox.

***
## <a name="features"></a>FEATURES
* **Native JavaScript**:

  * Fully responsive. Scales with its container.
  * Lightweight, 26kB (11kB gzipped).
  * Accessibility friendly.
  * Internet Explorer 10.
  * Mouse drag and touch swipe.
  * Plugins make easy overrides or extensions for advanced works.
* **Deep Integration**:

  Seamlessly works with Core Media, Views, Paragraphs, and Media contrib
  modules. Supports Image, Responsive image, (local|remote|iframe) videos, SVG,
  DIV (CSS backgrounds), either inline, fields, views, or within lightboxes.
* **LCP & CLS Management**:

  Engineered for a **"CLS-zero" strategy**, our framework integrates
  **sophisticated preloading** alongside native `fetchpriority` and `decoding`
  to systematically eliminate LCP discovery delays. We provide rigorous
  optimization for every asset—from **standard images**, **CSS backgrounds**
  and **responsive picture elements** to **optimized video posters**. While we
  leverage modern CSS `aspect-ratio` for layout stability, we maintain a refined
  **padding-bottom fallback** to ensure backward compatibility (BC) without
  sacrificing precision.
* **Intelligent Lazy-loading**:

  Sophisticated preloading via the Blazy engine for images, CSS backgrounds,
  iframes, SVG, HTML5 video, audio, and HTML media type.
  * Multi-serving lazyloaded images, including multi-breakpoint CSS backgrounds.
* **Privacy & GDPR Compliance**:

  Utilizes a **Two-Click Media Loader** via the "Image to Iframe" option.
  No third-party tracking scripts are initialized until the user actively
  engages with the play button—satisfying strict **GDPR and ePrivacy**
  requirements.

* **Developer Friendly**:

  Features a "Vanilla" mode and a
  [robust API](https://git.drupalcode.org/project/splide/blob/3.0.x/splide.api.php)
  for custom/theme implementations.
* **Modular Skins & Versatile Designs**:

  Fullscreen, Split, multi-row, or Grid layouts built with pure CSS and zero
  JavaScript beyond the initializer.
* **Nested sliders/overlays**:

  Multiple carousels within a single Slick via Slick Paragraphs and Slick Views.
* **Randomization**:

  A strategic solution for refreshing cached content (ads, e-commerce) to ensure
  a dynamic user experience across pages without compromising performance.
* **Robust content supports:**

  HTML, responsive image/ picture, responsive iframe, SVG, video, audio and
  third party contents.
* **Inline & lightbox mixed-media:**

  A single **Media switcher** option for various interactions: image to content,
  iframe, and (quasi-)lightboxes: Slick lightbox, Colorbox, PhotoSwipe, Flybox,
  Magnific Popup, Zooming, etc.
* **Editor Friendly:**

  `Splide Filter` using simple shortcodes, see [Filter tips](/filter/tips).
* **Navigation/ Pager Options**:

  Arrows, Dots (circle, static grid, or hoverable), Tabs, and Image Thumbnails.
  Almost similar to Slick, what Slick has, Splide does, too, but even more.
  + **Robus navigations**, even for Vanilla slides.
  + **Arrows**
  + **Dots**, circle dots, dots as static grid thumbnails, and dots with
    hoverable thumbnails.
  + [**Text tabs**](https://www.drupal.org/project/issues/search?issue_tags=slick%20tabs), just provide Thumbnail caption, and leave Thumbnail
    style/image empty to achieve:

    * [Vertical tabs](https://www.drupal.org/files/issues/Bildschirmfoto%202016-03-16%20um%2012.09.36.png)
    * [Inline tabs](https://www.drupal.org/files/issues/thumbnail-caption-or-any-text-as-navigation.png)
  + [**Image thumbnails/tabs**](https://www.drupal.org/project/issues/search?issue_tags=slick%20asnavfor)


## ROADMAP
* [x] Vanilla with thumbnail navigation for both Splide Views and formatters.
  - [x] Views, fixed with Rendered entity + Fields (not Content) under Format
  - [x] Media
  - [x] Entity reference
  - [X] Paragraphs
* [?] Bug fixes, code cleanup, optimization, and full release.
