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
 *
 * TASK-024 adds:
 * - updateSpecs() — live specs block update on every selection change
 * - parseWattLabel() — parses "22W 3771lm 400mA 171lm/W" into components
 * - Print button click handler attached via JS (onclick stripped by Drupal XSS)
 * - syncPrintImage() — mirrors configurator image src into specs-print-img
 *
 * TASK-021 adds:
 * - markActiveSibling() — highlights current product in sibling badges block
 *
 * TASK-022 adds:
 * - Drupal.t() on all hardcoded UI strings for SV/EN translation
 *
 * TASK-023 adds:
 * - Bootstrap custom dropdowns replace native <select> for mobile compatibility
 * - Popper.js (via Bootstrap JS) handles positioning — always opens downward
 *
 * TASK-025 adds:
 * - watt and optic steps get col-12 col-sm-6 (no col-md-4) for wider layout
 * - btn-sm on all dropdown toggle buttons for smaller text
 *
 * TASK-026 adds:
 * - document.title set to SKU before window.print(), restored via afterprint event
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

      // Mark active sibling badge — TASK-021.
      markActiveSibling();

      // ------------------------------------------------------------------ //
      // Render
      // ------------------------------------------------------------------ //

      function render() {
        container.innerHTML = '';

        var stepsWrapper = document.createElement('div');
        stepsWrapper.className = 'row g-3 mb-3 configurator-steps';
        container.appendChild(stepsWrapper);

        steps.forEach(function (step) {
          var col = document.createElement('div');
          // watt and optic need more space — never narrower than half width.
          var wideSteps = ['watt', 'optic'];
          col.className = wideSteps.indexOf(step.id) !== -1
            ? 'col-12 col-sm-6 configurator-step'
            : 'col-12 col-sm-6 col-md-4 configurator-step';
          col.dataset.stepId = step.id;

          var label = document.createElement('label');
          label.className = 'form-label fw-semibold small text-uppercase';
          label.textContent = getLabelForStep(step.id);
          col.appendChild(label);

          // Bootstrap dropdown wrapper.
          var dropdownWrapper = document.createElement('div');
          dropdownWrapper.className = 'dropdown w-100';

          var toggle = document.createElement('button');
          toggle.type = 'button';
          toggle.className = 'btn btn-sm btn-outline-secondary dropdown-toggle w-100 text-start';
          toggle.setAttribute('data-bs-toggle', 'dropdown');
          toggle.setAttribute('aria-expanded', 'false');
          toggle.dataset.stepId = step.id;
          toggle.dataset.value = '';
          toggle.textContent = Drupal.t('— Select —');

          var menu = document.createElement('ul');
          menu.className = 'dropdown-menu w-100';
          menu.dataset.stepId = step.id;

          step.options.forEach(function (option) {
            var li = document.createElement('li');
            var a = document.createElement('a');
            a.className = 'dropdown-item';
            a.href = '#';
            a.dataset.value = option.code;
            a.dataset.stepId = step.id;
            a.textContent = option.label;

            a.addEventListener('click', function (e) {
              e.preventDefault();
              if (a.classList.contains('disabled') || a.hidden) return;

              selections[step.id] = option.code;
              toggle.textContent = option.label;
              toggle.dataset.value = option.code;

              // Mark active item.
              menu.querySelectorAll('.dropdown-item').forEach(function (el) {
                el.classList.remove('active');
              });
              a.classList.add('active');

              clearSelectionsAfter(step.id);
              autoSelectFirst();
              maybeUpdateImage();
            });

            li.appendChild(a);
            menu.appendChild(li);
          });

          dropdownWrapper.appendChild(toggle);
          dropdownWrapper.appendChild(menu);
          col.appendChild(dropdownWrapper);
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
        qtyLabel.textContent = Drupal.t('Quantity:');

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
        btn.textContent = Drupal.t('Add to quote');
        btn.disabled = true;
        btn.addEventListener('click', submitToCart);

        actionRow.appendChild(qtyLabel);
        actionRow.appendChild(qty);
        actionRow.appendChild(btn);
        container.appendChild(actionRow);

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
      // Active sibling marking — TASK-021
      // ------------------------------------------------------------------ //

      function markActiveSibling() {
        var currentPath = window.location.pathname;
        document.querySelectorAll('.syskon-block a').forEach(function (a) {
          var href = a.getAttribute('href');
          var hrefPath = href.replace(/^\/(en|sv)\//, '/');
          var comparePath = currentPath.replace(/^\/(en|sv)\//, '/');

          if (hrefPath === comparePath) {
            a.classList.remove('btn-outline-secondary');
            a.classList.add('btn-primary');
            a.setAttribute('aria-current', 'page');
          }
        });
      }

      // ------------------------------------------------------------------ //
      // Auto-select first valid combination
      // ------------------------------------------------------------------ //

      function autoSelectFirst() {
        var changed = true;
        var maxPasses = steps.length;
        while (changed && maxPasses-- > 0) {
          changed = false;
          steps.forEach(function (step) {
            if (selections[step.id]) return;

            for (var i = 0; i < step.options.length; i++) {
              var option = step.options[i];
              if (isOptionAvailable(option)) {
                selections[step.id] = option.code;

                // Update toggle button text.
                var toggle = container.querySelector('button[data-step-id="' + step.id + '"]');
                if (toggle) {
                  toggle.textContent = option.label;
                  toggle.dataset.value = option.code;
                }

                // Mark active item in menu.
                var menu = container.querySelector('ul[data-step-id="' + step.id + '"]');
                if (menu) {
                  menu.querySelectorAll('.dropdown-item').forEach(function (a) {
                    a.classList.toggle('active', a.dataset.value === option.code);
                  });
                }

                changed = true;
                break;
              }
            }
          });
          updateVisibility();
        }
        updateSku();
        updateButton();
        updateSpecs();
        maybeUpdateImage();
      }

      // ------------------------------------------------------------------ //
      // Image switching
      // ------------------------------------------------------------------ //

      function maybeUpdateImage() {
        var pictures = config.imagePictures;
        if (!pictures || !pictures.length) return;

        var visualSelections = {};
        steps.forEach(function (step) {
          if (step.visual && selections[step.id]) {
            visualSelections[step.id] = selections[step.id];
          }
        });

        var best = null;
        var bestScore = -1;

        pictures.forEach(function (entry) {
          var conditions = entry.conditions;
          var keys = Object.keys(conditions);

          if (keys.length === 0) {
            if (bestScore < 0) {
              best = entry;
              bestScore = 0;
            }
            return;
          }

          var score = 0;
          var allMatch = true;
          keys.forEach(function (k) {
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

        var imgEl = document.querySelector('.triton-configurator-image img');
        if (!imgEl) return;

        var tmp = document.createElement('div');
        tmp.innerHTML = best.html;
        var newImg = tmp.querySelector('img');
        if (!newImg) return;

        imgEl.src = newImg.getAttribute('src') || '';
        imgEl.srcset = newImg.getAttribute('srcset') || '';
        if (newImg.getAttribute('sizes')) {
          imgEl.sizes = newImg.getAttribute('sizes');
        }
        imgEl.loading = 'eager';

        syncPrintImage(imgEl.src);
      }

      // ------------------------------------------------------------------ //
      // Sync print image — TASK-024
      // ------------------------------------------------------------------ //

      function syncPrintImage(src) {
        var printImg = document.getElementById('specs-print-img');
        if (!printImg) return;
        if (!src) {
          var configImg = document.querySelector('.triton-configurator-image img');
          src = configImg ? configImg.src : '';
        }
        printImg.src = src;
      }

      // ------------------------------------------------------------------ //
      // Specs block update — TASK-024
      // ------------------------------------------------------------------ //

      function updateSpecs() {
        var specsEl = document.getElementById('configurator-specs');
        if (!specsEl) return;

        var printBtn = document.getElementById('configurator-print-btn');
        if (printBtn && !printBtn.dataset.printAttached) {
          printBtn.dataset.printAttached = 'true';
          printBtn.addEventListener('click', function () {
            // 1. Clone static specs into configurator-specs before footer.
            var staticEl = document.getElementById('static-specs');
            var cloneId = 'static-specs-print-clone';
            var existing = document.getElementById(cloneId);
            if (existing) existing.remove();
            if (staticEl) {
              var clone = staticEl.cloneNode(true);
              clone.id = cloneId;
              var footer = specsEl.querySelector('.specs-print-footer');
              if (footer) {
                specsEl.insertBefore(clone, footer);
              } else {
                specsEl.appendChild(clone);
              }
            }

            // 2. Move #configurator-specs to body root (first child) so it renders on page 1.
            var originalParent = specsEl.parentNode;
            var originalNextSibling = specsEl.nextSibling;
            document.body.insertBefore(specsEl, document.body.firstChild);

            // 3. Set document title to SKU for PDF filename.
            var sku = buildSku();
            var originalTitle = document.title;
            document.title = sku;

            // 4. Restore after print.
            window.addEventListener('afterprint', function restoreAll() {
              document.title = originalTitle;
              // Restore specsEl to original position.
              if (originalNextSibling) {
                originalParent.insertBefore(specsEl, originalNextSibling);
              } else {
                originalParent.appendChild(specsEl);
              }
              var c = document.getElementById(cloneId);
              if (c) c.remove();
              window.removeEventListener('afterprint', restoreAll);
            });

            window.print();
          });
        }

        function setSpec(id, value) {
          var cell = specsEl.querySelector('[data-spec="' + id + '"]');
          var row = specsEl.querySelector('[data-spec-row="' + id + '"]');
          if (cell) cell.textContent = value || '—';
          if (row) row.setAttribute('data-spec-hidden', value ? 'false' : 'true');
        }

        var nameEl = specsEl.querySelector('[data-spec="product-name"]');
        if (nameEl) nameEl.textContent = schema.productName || '—';

        var skuEl = specsEl.querySelector('[data-spec="sku"]');
        if (skuEl) skuEl.textContent = buildSku();

        var generatedEl = specsEl.querySelector('[data-spec="print-generated"]');
        if (generatedEl) {
          var now = new Date();
          var dateStr = now.toLocaleDateString('sv-SE', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
          generatedEl.textContent = Drupal.t('Generated on @date. This document is for information only.', { '@date': dateStr });
        }

        syncPrintImage();

        var stepIds = ['length', 'driver', 'endcap', 'cri', 'sensor', 'kelvin', 'optic', 'color', 'chips', 'ip_class'];
        stepIds.forEach(function (stepId) {
          var step = steps.find(function (s) { return s.id === stepId; });
          if (!step) { setSpec(stepId, null); return; }
          var code = selections[stepId];
          if (!code) { setSpec(stepId, null); return; }
          var option = step.options.find(function (o) { return o.code === code; });
          setSpec(stepId, option ? option.label : code);
        });

        var wattStep = steps.find(function (s) { return s.id === 'watt'; });
        var wattCode = selections['watt'];
        if (wattStep && wattCode) {
          var wattOption = wattStep.options.find(function (o) { return o.code === wattCode; });
          if (wattOption) {
            var parsed = parseWattLabel(wattOption.label);
            setSpec('watt', parsed.watt ? parsed.watt + ' W' : wattOption.label);
            setSpec('lumen', parsed.lumen ? parsed.lumen + ' lm' : null);
            setSpec('efficacy', parsed.efficacy ? parsed.efficacy + ' lm/W' : null);
          }
        } else {
          setSpec('watt', null);
          setSpec('lumen', null);
          setSpec('efficacy', null);
        }
      }

      function parseWattLabel(label) {
        var result = {};
        var m;
        m = label.match(/^(\d+)W/);
        if (m) result.watt = m[1];
        m = label.match(/(\d+)lm\b/);
        if (m) result.lumen = m[1];
        m = label.match(/(\d+)mA/);
        if (m) result.current = m[1];
        m = label.match(/(\d+)lm\/W/);
        if (m) result.efficacy = m[1];
        return result;
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
          if (step.skuPart === 'middle') middle += val;
          else if (step.skuPart === 'end') end += val;
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
          setFeedback(feedback, 'error', Drupal.t('Please select an option for all steps.'));
          return;
        }
        if (!variationId) {
          setFeedback(feedback, 'error', Drupal.t('Configuration error: missing variation ID.'));
          return;
        }

        var sku = buildSku();
        var data = JSON.stringify(selections);

        btn.disabled = true;
        btn.textContent = Drupal.t('Adding…');
        feedback.textContent = '';

        fetch(drupalSettings.path.baseUrl + 'session/token')
          .then(function (r) { return r.text(); })
          .then(function (token) { return doCartPost(token, sku, data, qty); })
          .catch(function (err) {
            setFeedback(feedback, 'error', Drupal.t('Error: @msg', { '@msg': err.message }));
            btn.disabled = false;
            btn.textContent = Drupal.t('Add to quote');
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
                throw new Error(err.message || 'Server error ' + response.status);
              });
            }
            return response.json();
          })
          .then(function () {
            setFeedback(feedback, 'success', Drupal.t('✓ Added to quote! SKU: @sku', { '@sku': sku }));
            btn.textContent = Drupal.t('Added ✓');
            btn.classList.replace('btn-primary', 'btn-success');
            if (Drupal.ajax) {
              Drupal.announce(Drupal.t('Product added to cart.'));
            }
          })
          .catch(function (err) {
            setFeedback(feedback, 'error', Drupal.t('Error: @msg', { '@msg': err.message }));
            btn.disabled = false;
            btn.textContent = Drupal.t('Add to quote');
          });
      }

      function setFeedback(el, type, message) {
        el.textContent = message;
        el.className = 'mt-2 small ' + (type === 'success' ? 'text-success' : 'text-danger');
      }

      // ------------------------------------------------------------------ //
      // Visibility / dependsOn — TASK-023: uppdaterad för Bootstrap dropdown
      // ------------------------------------------------------------------ //

      function updateVisibility() {
        steps.forEach(function (step) {
          var menu = container.querySelector('ul[data-step-id="' + step.id + '"]');
          var toggle = container.querySelector('button[data-step-id="' + step.id + '"]');
          if (!menu || !toggle) return;

          var hasVisible = false;

          step.options.forEach(function (option) {
            var a = menu.querySelector('a[data-value="' + option.code + '"]');
            if (!a) return;
            var visible = isOptionAvailable(option);
            a.hidden = !visible;
            a.classList.toggle('disabled', !visible);
            if (visible) hasVisible = true;

            // Reset selection if currently selected option becomes unavailable.
            if (!visible && selections[step.id] === option.code) {
              selections[step.id] = '';
              toggle.textContent = Drupal.t('— Select —');
              toggle.dataset.value = '';
            }
          });

          var col = toggle.closest('.configurator-step');
          if (col) col.style.display = hasVisible ? '' : 'none';
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
          if (found) {
            selections[step.id] = '';
            var toggle = container.querySelector('button[data-step-id="' + step.id + '"]');
            if (toggle) {
              toggle.textContent = Drupal.t('— Select —');
              toggle.dataset.value = '';
            }
            var menu = container.querySelector('ul[data-step-id="' + step.id + '"]');
            if (menu) {
              menu.querySelectorAll('.dropdown-item').forEach(function (a) {
                a.classList.remove('active');
              });
            }
          }
          if (step.id === stepId) found = true;
        });
      }

      function getLabelForStep(stepId) {
        var labels = {
          length:  Drupal.t('Length'),
          driver:  Drupal.t('Driver'),
          endcap:  Drupal.t('Connection'),
          cri:     Drupal.t('CRI'),
          chips:   Drupal.t('Chips'),
          kelvin:  Drupal.t('Color temperature'),
          watt:    Drupal.t('Power'),
          optic:   Drupal.t('Optics'),
          color:   Drupal.t('Color'),
          sensor:  Drupal.t('Sensor / Battery'),
          ipClass: Drupal.t('IP class'),
        };
        return labels[stepId] || stepId;
      }

      render();
    },
  };

})(Drupal, drupalSettings);
