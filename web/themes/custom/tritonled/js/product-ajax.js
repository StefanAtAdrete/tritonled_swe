/**
 * Commerce Product - Layout Rebuild using Default Rendering
 * Works with Drupal's default commerce-product template
 */

(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.productLayoutRebuild = {
    attach: function (context, settings) {
      
      const elements = once('product-rebuild', 'article.commerce-product', context);
      
      elements.forEach(function(article) {
        console.log('🏗️ Rebuilding product layout from default rendering...');
        
        const $article = $(article);
        
        // Wait a bit for all content to load
        setTimeout(function() {
          rebuildLayout($article);
        }, 300);
        
        // Rebuild on AJAX
        $(document).on('ajaxSuccess', function(event, xhr, settings) {
          if (settings.url && settings.url.indexOf('commerce') !== -1) {
            console.log('🔄 AJAX - updating fields');
            setTimeout(function() {
              updateVariationFields($article);
            }, 200);
          }
        });
      });
      
      function rebuildLayout($article) {
        console.log('📦 Starting rebuild...');
        
        // Hide default rendering
        $article.addClass('layout-rebuilding');
        
        // Get title
        const title = $article.find('.field--name-title .field__item').text().trim() || 
                     'Orbit LED Panel';
        
        // Build new layout structure
        const $layout = $('<div class="product-custom-layout"></div>');
        
        // Hero section
        const $hero = $(`
          <div class="product-hero row g-4 mb-5">
            <div class="col-12 col-md-6">
              <div class="product-media" id="media-container"></div>
            </div>
            <div class="col-12 col-md-6">
              <h1 class="mb-4">${title}</h1>
              <div class="icon-specs row g-3 mb-4">
                <div class="col-6 col-sm-3">
                  <div class="spec-item text-center p-3 bg-light rounded border">
                    <div class="spec-icon mb-2">💡</div>
                    <div class="spec-label small text-muted">Flux</div>
                    <div class="spec-value fw-bold" data-field="field-lumen">-</div>
                  </div>
                </div>
                <div class="col-6 col-sm-3">
                  <div class="spec-item text-center p-3 bg-light rounded border">
                    <div class="spec-icon mb-2">⚡</div>
                    <div class="spec-label small text-muted">Efficiency</div>
                    <div class="spec-value fw-bold" data-field="field-lm-w">-</div>
                  </div>
                </div>
                <div class="col-6 col-sm-3">
                  <div class="spec-item text-center p-3 bg-light rounded border">
                    <div class="spec-icon mb-2">🌡️</div>
                    <div class="spec-label small text-muted">CCT</div>
                    <div class="spec-value fw-bold" data-field="field-cct">-</div>
                  </div>
                </div>
                <div class="col-6 col-sm-3">
                  <div class="spec-item text-center p-3 bg-light rounded border">
                    <div class="spec-icon mb-2">💧</div>
                    <div class="spec-label small text-muted">IP</div>
                    <div class="spec-value fw-bold" data-field="field-ip-rating">-</div>
                  </div>
                </div>
              </div>
              <div id="variations-container" class="mb-4"></div>
              <div id="description-container"></div>
            </div>
          </div>
        `);
        
        $layout.append($hero);
        
        // Tabs section
        const $tabs = $(`
          <div class="product-details row g-4">
            <div class="col-12 col-lg-9">
              <ul class="nav nav-tabs mb-3">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#specs">Specifications</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#dims">Dimensions</button></li>
              </ul>
              <div class="tab-content border rounded p-4 bg-light">
                <div class="tab-pane fade show active" id="specs">
                  <h3 class="h5 mb-3">Technical Specifications</h3>
                  <table class="table table-sm">
                    <tr><td width="40%">Power (W)</td><td data-field="field-watt">-</td></tr>
                    <tr><td>Flux (lm)</td><td data-field="field-lumen">-</td></tr>
                    <tr><td>CCT (K)</td><td data-field="field-cct">-</td></tr>
                    <tr><td>CRI</td><td data-field="field-cri">-</td></tr>
                    <tr><td>Efficacy (lm/W)</td><td data-field="field-lm-w">-</td></tr>
                    <tr><td>Voltage</td><td data-field="field-voltage">-</td></tr>
                  </table>
                </div>
                <div class="tab-pane fade" id="dims">
                  <h3 class="h5 mb-3">Dimensions</h3>
                  <table class="table table-sm">
                    <tr><td width="40%">Length (mm)</td><td data-field="field-length">-</td></tr>
                    <tr><td>Width (mm)</td><td data-field="field-width">-</td></tr>
                    <tr><td>Height (mm)</td><td data-field="field-height">-</td></tr>
                  </table>
                </div>
              </div>
            </div>
            <div class="col-12 col-lg-3">
              <div class="card">
                <div class="card-header"><h3 class="h6 mb-0">Product Info</h3></div>
                <div class="card-body">
                  <p class="small mb-2"><strong>IP:</strong> <span data-field="field-ip-rating">-</span></p>
                  <p class="small mb-0"><strong>Warranty:</strong> <span data-field="field-warranty">-</span> years</p>
                </div>
              </div>
            </div>
          </div>
        `);
        
        $layout.append($tabs);
        
        // Add layout to article
        $article.append($layout);
        console.log('✅ Layout structure created');
        
        // Move content
        moveFields($article);
        
        // Update fields
        updateVariationFields($article);
        
        // Mark as done
        $article.addClass('layout-rebuilt');
      }
      
      function moveFields($article) {
        // Move media
        const $media = $article.find('.field--name-field-product-media').first().detach();
        if ($media.length) {
          $('#media-container').append($media);
          console.log('✅ Media moved');
        }
        
        // Move variations
        const $vars = $article.find('.field--name-variations').first().detach();
        if ($vars.length) {
          $('#variations-container').append($vars);
          console.log('✅ Variations moved');
        }
        
        // Move description
        const $desc = $article.find('.field--name-field-product-description, .field--name-body').first().detach();
        if ($desc.length) {
          $('#description-container').append($desc);
        }
      }
      
      function updateVariationFields($article) {
        const fields = {
          'field-watt': null,
          'field-lumen': null,
          'field-cct': null,
          'field-cri': null,
          'field-lm-w': null,
          'field-length': null,
          'field-width': null,
          'field-height': null,
          'field-ip-rating': null,
          'field-warranty': null,
          'field-voltage': null
        };
        
        Object.keys(fields).forEach(function(fname) {
          const $field = $article.find('.field--name-' + fname);
          if ($field.length) {
            const val = $field.find('.field__item').first().text().trim();
            if (val) {
              $('[data-field="' + fname + '"]').html(val);
              console.log(fname + ': ' + val);
            }
          }
        });
        
        console.log('✅ Fields populated');
      }
      
    }
  };

})(jQuery, Drupal, once);
