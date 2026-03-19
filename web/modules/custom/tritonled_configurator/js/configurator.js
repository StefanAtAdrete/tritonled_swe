/**
 * @file
 * TritonLED Product Configurator — SESSION 4
 *
 * Reads schema from drupalSettings, renders dropdowns with dependsOn logic,
 * builds SKU live, and POSTs to Commerce Cart API on submit.
 *
 * SESSION 5 adds: Bootstrap styling.
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

      // --- Render ---

      function render() {
        container.innerHTML = '';

        // Steps wrapper.
        var stepsWrapper = document.createElement('div');
        stepsWrapper.className = 'configurator-steps';
        container.appendChild(stepsWrapper);

        steps.forEach(function (step) {
          var wrapper = document.createElement('div');
          wrapper.className = 'configurator-step';
          wrapper.dataset.stepId = step.id;

          var label = document.createElement('label');
          label.textContent = getLabelForStep(step.id);
          label.htmlFor = 'configurator-' + step.id;
          wrapper.appendChild(label);

          var select = document.createElement('select');
          select.id = 'configurator-' + step.id;
          select.name = step.id;
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
            updateVisibility();
            updateSku();
            updateButton();
          });

          wrapper.appendChild(select);
          stepsWrapper.appendChild(wrapper);
        });

        // SKU bar.
        var skuBar = document.createElement('div');
        skuBar.className = 'configurator-sku-bar';

        var skuLabel = document.createElement('span');
        skuLabel.className = 'configurator-sku-label';
        skuLabel.textContent = 'SKU: ';

        var skuValue = document.createElement('code');
        skuValue.id = 'configurator-sku-value';
        skuValue.textContent = schema.skuPrefix + '…';

        skuBar.appendChild(skuLabel);
        skuBar.appendChild(skuValue);
        container.appendChild(skuBar);

        // Submit button.
        var btn = document.createElement('button');
        btn.id = 'configurator-submit';
        btn.type = 'button';
        btn.textContent = 'Lägg i offert';
        btn.disabled = true;
        btn.addEventListener('click', submitToCart);
        container.appendChild(btn);

        // Feedback area.
        var feedback = document.createElement('div');
        feedback.id = 'configurator-feedback';
        feedback.setAttribute('aria-live', 'polite');
        container.appendChild(feedback);

        updateVisibility();
        updateSku();
        updateButton();
      }

      // --- SKU builder ---

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

      // --- Validation ---

      function allStepsSelected() {
        return steps.every(function (step) {
          var wrapper = container.querySelector('.configurator-step[data-step-id="' + step.id + '"]');
          // Skip hidden steps.
          if (wrapper && wrapper.style.display === 'none') return true;
          return !!selections[step.id];
        });
      }

      function updateButton() {
        var btn = document.getElementById('configurator-submit');
        if (btn) btn.disabled = !allStepsSelected();
      }

      // --- Cart API ---

      function submitToCart() {
        var feedback = document.getElementById('configurator-feedback');
        var btn = document.getElementById('configurator-submit');

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

        var basePath = drupalSettings.path.baseUrl + drupalSettings.path.pathPrefix;

        // Fetch CSRF token first, then POST to cart.
        // session/token uses baseUrl only (no language prefix).
        fetch(drupalSettings.path.baseUrl + 'session/token')
          .then(function (r) { return r.text(); })
          .then(function (token) { return doCartPost(token, sku, data); })
          .catch(function (err) {
            setFeedback(feedback, 'error', 'Fel: ' + err.message);
            btn.disabled = false;
            btn.textContent = 'Lägg i offert';
          });
      }

      function doCartPost(csrfToken, sku, data) {
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
            setFeedback(feedback, 'success', 'Produkten är tillagd i offerten! SKU: ' + sku);
            btn.textContent = 'Tillagd ✓';
            // Trigger cart block refresh if available.
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
        el.className = 'configurator-feedback configurator-feedback--' + type;
      }

      // --- Visibility ---

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

          var stepWrapper = select.closest('.configurator-step');
          if (stepWrapper) {
            stepWrapper.style.display = hasVisible ? '' : 'none';
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
          length: 'Längd',
          driver: 'Driver',
          endcap: 'Anslutning',
          cri: 'CRI',
          chips: 'Chips',
          kelvin: 'Färgtemperatur',
          watt: 'Effekt',
          optic: 'Optik',
          color: 'Färg',
          ipClass: 'IP-klass',
        };
        return labels[stepId] || stepId;
      }

      render();
    },
  };

})(Drupal, drupalSettings);
