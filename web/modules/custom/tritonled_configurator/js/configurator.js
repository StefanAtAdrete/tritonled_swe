/**
 * @file
 * TritonLED Product Configurator — SESSION 5
 *
 * SESSION 5 adds:
 * - Bootstrap 5 classes in render()
 * - imageMap support + image switching on endcap/color change
 * - Quantity field
 * - Auto-select first valid combination on load
 * - Hide Commerce's own attribute dropdowns
 */

(function (Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.tritonConfigurator = {
    attach: function (context, settings) {
      var config = settings.tritonConfigurator;
      if (!config || !config.schema) {
        return;
      }

      var container = context.querySelector('[data-triton-configurator]');
      if (!container || container.dataset.configuratorAttached) {
        return;
      }
      container.dataset.configuratorAttached = 'true';

      var schema = config.schema;
      var steps = schema.steps;
      var variationId = config.variationId;
      var selections = {};

      // Hide Commerce's own attribute dropdowns (they conflict with our UI).
      var commerceForm = document.querySelector('.commerce-order-item-add-to-cart-form');
      if (commerceForm) {
        commerceForm.style.display = 'none';
      }

      // ------------------------------------------------------------------ //
      // Render
      // ------------------------------------------------------------------ //

      function render() {
        container.innerHTML = '';

        // Steps wrapper — Bootstrap row with 3 columns.
        var stepsWrapper = document.createElement('div');
        stepsWrapper.className = 'row g-3 mb-3 configurator-steps';
        container.appendChild(stepsWrapper);

        steps.forEach(function (step) {
          var col = document.createElement('div');
          col.className = 'col-12 col-sm-6 col-md-4 configurator-step';
          col.dataset.stepId = step.id;

          var label = document.createElement('label');
          label.className = 'form-label fw-semibold small text-uppercase';
          label.textContent = getLabelForStep(step.id);
          label.htmlFor = 'configurator-' + step.id;
          col.appendChild(label);

          var select = document.createElement('select');
          select.id = 'configurator-' + step.id;
          select.name = step.id;
          select.className = 'form-select form-select-sm';
          select.dataset.stepId = step.id;

          var placeholder = document.createElement('option');
          placeholder.value = '';
          placeholder.textContent = '— Välj —';
          placeholder.disabled = true;
          placeholder.selected = true;
          select.appendChild(placeholder);

          step.options.forEach(function (option) {
            var opt = document.createElement('option');
            opt.value = option.code;
            opt.textContent = option.label;
            select.appendChild(opt);
          });

          if (selections[step.id]) {
            select.value = selections[step.id];
          }

          select.addEventListener('change', function () {
            selections[step.id] = this.value;
            clearSelectionsAfter(step.id);
            // Re-run auto-select to fill dependent steps after this one.
            // autoSelectFirst skips steps that already have a value, so the
            // user's current selection is preserved.
            autoSelectFirst();
            // autoSelectFirst calls updateVisibility/updateSku/updateButton
            // internally — also fire image update for this step.
            maybeUpdateImage();
          });

          col.appendChild(select);
          stepsWrapper.appendChild(col);
        });

        // SKU bar.
        var skuBar = document.createElement('div');
        skuBar.className = 'configurator-sku-bar d-flex align-items-center gap-2 mb-3 p-2 bg-light rounded border';

        var skuLabel = document.createElement('span');
        skuLabel.className = 'configurator-sku-label text-muted small';
        skuLabel.textContent = 'SKU:';

        var skuValue = document.createElement('code');
        skuValue.id = 'configurator-sku-value';
        skuValue.className = 'fw-bold text-dark';
        skuValue.textContent = schema.skuPrefix + '…';

        skuBar.appendChild(skuLabel);
        skuBar.appendChild(skuValue);
        container.appendChild(skuBar);

        // Quantity + submit row.
        var actionRow = document.createElement('div');
        actionRow.className = 'd-flex align-items-center gap-2 mb-3';

        var qtyLabel = document.createElement('label');
        qtyLabel.htmlFor = 'configurator-qty';
        qtyLabel.className = 'form-label mb-0 small fw-semibold';
        qtyLabel.textContent = 'Antal:';

        var qty = document.createElement('input');
        qty.type = 'number';
        qty.id = 'configurator-qty';
        qty.name = 'quantity';
        qty.className = 'form-control form-control-sm';
        qty.style.width = '70px';
        qty.min = '1';
        qty.value = '1';

        var btn = document.createElement('button');
        btn.id = 'configurator-submit';
        btn.type = 'button';
        btn.className = 'btn btn-primary';
        btn.textContent = 'Lägg i offert';
        btn.disabled = true;
        btn.addEventListener('click', submitToCart);

        actionRow.appendChild(qtyLabel);
        actionRow.appendChild(qty);
        actionRow.appendChild(btn);
        container.appendChild(actionRow);

        // Feedback area.
        var feedback = document.createElement('div');
        feedback.id = 'configurator-feedback';
        feedback.setAttribute('aria-live', 'polite');
        container.appendChild(feedback);

        updateVisibility();
        updateSku();
        updateButton();
        autoSelectFirst();
      }

      // ------------------------------------------------------------------ //
      // Auto-select first valid combination
      // ------------------------------------------------------------------ //

      function autoSelectFirst() {
        // Loop until no new selections are made (handles dependsOn chains).
        var changed = true;
        var maxPasses = steps.length;
        while (changed && maxPasses-- > 0) {
          changed = false;
          steps.forEach(function (step) {
            if (selections[step.id]) return; // Already selected.
            var select = container.querySelector('select[data-step-id="' + step.id + '"]');
            if (!select) return;

            for (var i = 0; i < step.options.length; i++) {
              var option = step.options[i];
              if (isOptionAvailable(option)) {
                selections[step.id] = option.code;
                select.value = option.code;
                changed = true;
                break;
              }
            }
          });
          // Update visibility after each pass so dependsOn unlocks next steps.
          updateVisibility();
        }
        updateSku();
        updateButton();
        // Update image based on current visual selections.
        maybeUpdateImage();
      }

      // ------------------------------------------------------------------ //
      // Image switching — SESSION 5b
      // Matches active visual step selections against imagePictures entries,
      // picks the best match (most conditions met), falls back to default.
      // Replaces the img element inside field--name-field-configurator-media.
      // ------------------------------------------------------------------ //

      function maybeUpdateImage() {
        var pictures = config.imagePictures;
        if (!pictures || !pictures.length) return;

        // Collect active visual selections.
        var visualSelections = {};
        steps.forEach(function(step) {
          if (step.visual && selections[step.id]) {
            visualSelections[step.id] = selections[step.id];
          }
        });

        // Score each picture entry — count matching conditions.
        var best = null;
        var bestScore = -1;

        pictures.forEach(function(entry) {
          var conditions = entry.conditions;
          var keys = Object.keys(conditions);

          // Default entry (empty conditions) scores 0 — always a candidate.
          if (keys.length === 0) {
            if (bestScore < 0) {
              best = entry;
              bestScore = 0;
            }
            return;
          }

          // Count matches.
          var score = 0;
          var allMatch = true;
          keys.forEach(function(k) {
            if (visualSelections[k] === conditions[k]) {
              score++;
            } else {
              allMatch = false;
            }
          });

          if (allMatch && score > bestScore) {
            best = entry;
            bestScore = score;
          }
        });

        if (!best) return;

        // Find the image container — field_configurator_media img.
        var imgEl = document.querySelector(
          '.field--name-field-configurator-media img, ' +
          '.field--name-field-product-media img'
        );
        if (!imgEl) return;

        // Parse the new img from the picture HTML string.
        var tmp = document.createElement('div');
        tmp.innerHTML = best.html;
        var newImg = tmp.querySelector('img');
        if (!newImg) return;

        // Replace src + srcset on existing element (preserves CSS/classes).
        imgEl.src = newImg.getAttribute('src') || '';
        imgEl.srcset = newImg.getAttribute('srcset') || '';
        if (newImg.getAttribute('sizes')) {
          imgEl.sizes = newImg.getAttribute('sizes');
        }
        imgEl.loading = 'eager'; // Already in viewport — load immediately.
      }

      // ------------------------------------------------------------------ //
      // SKU builder
      // ------------------------------------------------------------------ //

      function buildSku() {
        var middle = '';
        var end = '';
        steps.forEach(function (step) {
          var val = selections[step.id];
          if (!val) return;
          if (step.skuPart === 'middle') {
            middle += val;
          } else if (step.skuPart === 'end') {
            end += val;
          }
        });
        if (!middle && !end) return schema.skuPrefix + '…';
        return schema.skuPrefix + middle + end;
      }

      function updateSku() {
        var el = document.getElementById('configurator-sku-value');
        if (el) el.textContent = buildSku();
      }

      // ------------------------------------------------------------------ //
      // Validation
      // ------------------------------------------------------------------ //

      function allStepsSelected() {
        return steps.every(function (step) {
          var col = container.querySelector('.configurator-step[data-step-id="' + step.id + '"]');
          if (col && col.style.display === 'none') return true;
          return !!selections[step.id];
        });
      }

      function updateButton() {
        var btn = document.getElementById('configurator-submit');
        if (btn) btn.disabled = !allStepsSelected();
      }

      // ------------------------------------------------------------------ //
      // Cart API
      // ------------------------------------------------------------------ //

      function submitToCart() {
        var feedback = document.getElementById('configurator-feedback');
        var btn = document.getElementById('configurator-submit');
        var qty = parseInt(document.getElementById('configurator-qty').value, 10) || 1;

        if (!allStepsSelected()) {
          setFeedback(feedback, 'error', 'Välj ett alternativ för alla steg.');
          return;
        }
        if (!variationId) {
          setFeedback(feedback, 'error', 'Konfigurationsfel: saknar variation ID.');
          return;
        }

        var sku = buildSku();
        var data = JSON.stringify(selections);

        btn.disabled = true;
        btn.textContent = 'Lägger till…';
        feedback.textContent = '';

        fetch(drupalSettings.path.baseUrl + 'session/token')
          .then(function (r) { return r.text(); })
          .then(function (token) { return doCartPost(token, sku, data, qty); })
          .catch(function (err) {
            setFeedback(feedback, 'error', 'Fel: ' + err.message);
            btn.disabled = false;
            btn.textContent = 'Lägg i offert';
          });
      }

      function doCartPost(csrfToken, sku, data, qty) {
        var feedback = document.getElementById('configurator-feedback');
        var btn = document.getElementById('configurator-submit');

        fetch(drupalSettings.path.baseUrl + 'triton/configurator/add-to-cart', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken,
          },
          body: JSON.stringify({
            variationId: variationId,
            sku: sku,
            quantity: qty,
            selections: JSON.parse(data),
          }),
          credentials: 'same-origin',
        })
          .then(function (response) {
            if (!response.ok) {
              return response.json().then(function (err) {
                throw new Error(err.message || 'Serverfel ' + response.status);
              });
            }
            return response.json();
          })
          .then(function () {
            setFeedback(feedback, 'success', '✓ Tillagd i offerten! SKU: ' + sku);
            btn.textContent = 'Tillagd ✓';
            btn.classList.replace('btn-primary', 'btn-success');
            if (Drupal.ajax) {
              Drupal.announce(Drupal.t('Product added to cart.'));
            }
          })
          .catch(function (err) {
            setFeedback(feedback, 'error', 'Fel: ' + err.message);
            btn.disabled = false;
            btn.textContent = 'Lägg i offert';
          });
      }

      function setFeedback(el, type, message) {
        el.textContent = message;
        el.className = 'mt-2 small ' + (type === 'success' ? 'text-success' : 'text-danger');
      }

      // ------------------------------------------------------------------ //
      // Visibility / dependsOn
      // ------------------------------------------------------------------ //

      function updateVisibility() {
        steps.forEach(function (step) {
          var select = container.querySelector('select[data-step-id="' + step.id + '"]');
          if (!select) return;

          var hasVisible = false;
          step.options.forEach(function (option) {
            var optEl = select.querySelector('option[value="' + option.code + '"]');
            if (!optEl) return;
            var visible = isOptionAvailable(option);
            optEl.hidden = !visible;
            optEl.disabled = !visible;
            if (visible) hasVisible = true;
            if (!visible && select.value === option.code) {
              select.value = '';
              selections[step.id] = '';
            }
          });

          var col = select.closest('.configurator-step');
          if (col) {
            col.style.display = hasVisible ? '' : 'none';
          }
        });
      }

      function isOptionAvailable(option) {
        if (option.dependsOn && option.dependsOn.length > 0) {
          var allMet = option.dependsOn.every(function (c) {
            var val = selections[c.stepId];
            return val && c.codes.indexOf(val) !== -1;
          });
          if (!allMet) return false;
        }
        if (option.dependsOnAny && option.dependsOnAny.length > 0) {
          var anyMet = option.dependsOnAny.some(function (group) {
            return group.every(function (c) {
              var val = selections[c.stepId];
              return val && c.codes.indexOf(val) !== -1;
            });
          });
          if (!anyMet) return false;
        }
        return true;
      }

      function clearSelectionsAfter(stepId) {
        var found = false;
        steps.forEach(function (step) {
          if (found) selections[step.id] = '';
          if (step.id === stepId) found = true;
        });
      }

      function getLabelForStep(stepId) {
        var labels = {
          length:  'Längd',
          driver:  'Driver',
          endcap:  'Anslutning',
          cri:     'CRI',
          chips:   'Chips',
          kelvin:  'Färgtemperatur',
          watt:    'Effekt',
          optic:   'Optik',
          color:   'Färg',
          ipClass: 'IP-klass',
        };
        return labels[stepId] || stepId;
      }

      render();
    },
  };

})(Drupal, drupalSettings);
