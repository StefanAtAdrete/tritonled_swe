### <a name="optimization"></a>Strategic Optimization Checklist

Proper configuration ensures the module works for you, not against you.
Use this checklist to audit your implementation:

#### 1. Essential UI Refinements

- **Optimized Mode:**

  Enable the **Optimized** checkbox in the Splide optionset to strip unnecessary
  bytes.

- **Production Clean-up:**

  Always **uninstall Splide UI** in production; configuration belongs in code
  or exported features.

#### 2. Asset & Resource Management

- **Lazyload HTML:**

  For third-party embeds (Instagram/Pinterest, etc), enable **Lazyload HTML** in
  the Blazy UI to prevent blocking the main thread.

- **Global Performance:**

  Ensure Drupal’s core **CSS/JS aggregation** and caching are active at
  `/admin/config/development/performance`.

#### 3. Media & Image Engineering

- **Prevent Layout Shift (CLS):**

  Use image styles with a **"crop"** effect whenever possible.
  Select the relevant **Aspect ratio** option in the formatter UI and enable
  **Modern CSS aspect-ratio** (available since Blazy 3.0.17).

- **Loading Priority:**

  Use the **Preload** and **Loading Priority** options for "above-the-fold"
  assets to optimize LCP (Largest Contentful Paint) elements.

- **Responsive Standards:**

  Prioritize Core **Responsive Image** if storage permits. Otherwise, utilize
  formats like **WebP** or **AVIF** with a versatile design.

#### 4. Logic & Interaction Settings

- **Disable Autoplay/Infinite:**

  Unless strictly required, turn these off to prevent early downloads and
  expensive DOM reflows. Only reasonable for text marquees or trivial
  slideshows where performance is not a concern.
  **Autoplay** triggers downloads that can defeat lazy-loading, and **Infinite**
  loops often cause continuous, expensive DOM reflows including duplicate HTTP
  requests due to cloned slides.

- **Grid Strategy:**

  Use **HTML Formatter Grids** instead of JavaScript-based
  **Optionset Grids**. Server-side cached HTML is significantly more performant
  than generating complex DOM trees on-the-fly via JavaScript.

- **Scalability for Galleries:**

  For massive sets, use **Blazy Grid + Lightbox** (Colorbox, PhotoSwipe, etc.).
  This is objectively faster than a Splide-only implementation for static
  viewing, at least until we can make ajaxified Splide Views (3-4-hour backers
  are welcome at Splide Views to move this feature out of premium versions).

#### 5. Additional Optimization Settings & Automated Intelligence
Visit `/admin/config/media/blazy/ui` for further tuning. While Blazy supports
backward compatibility (BC) by default, you should optimize for modern
environments by leveraging both UI options and the module's internal logic:

- **Native Lazyload:**

  Favor native browser lazy-loading (and disable unnecessary polyfills) to
  reduce main-thread execution when targetting modern sites.

- **Lean Markup:**

  Enable **Remove field/view wrapper CSS classes** and ensure
  **"Use theme field"** remains unchecked to reduce DOM depth and "Divitis."

- **Noscript Compatibility:**

  While `<noscript>` tags provide a fallback for users without JavaScript,
  they add extra weight to the HTML. If your target audience is modern
  performance-critical, consider keeping this fallback disabled to shave off
  every possible byte.

- **Admin UIs:**

  Visit `/admin/config/media/blazy` and `/admin/config/media/splide/ui`,
  including Media formatters for more optimization options.
