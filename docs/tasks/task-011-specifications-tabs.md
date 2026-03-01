# Task 011: Specifications tabs på produktsidan

**Created**: 2026-02-28
**Status**: In Progress
**Priority**: Normal

---

## 1. MÅL

Visa Electrical, Mechanical och Certifications som Bootstrap tabs
på produktsidan. Tabs är en fast del av produktlayouten — inte
konfigurerbar av redaktören.

## 2. LÖSNING

Befintlig template `commerce-product--default.html.twig` har redan
en `TODO`-kommentar för detta. Fyll i Bootstrap tabs-struktur med
pseudo-fälten från `commerce_variation_blocks`.

Ingen ny fil, ingen ny modul — bara HTML i befintlig template.

## 3. FILER

- `web/themes/custom/tritonled_radix/templates/commerce/commerce-product--default.html.twig`

## 4. IMPLEMENTATION

Ersätt `{# TODO: Specifications tabs... #}` med Bootstrap tabs:

```twig
{# Specifications Tabs #}
<div class="row mt-5">
  <div class="col-12">
    <ul class="nav nav-tabs" id="specTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="electrical-tab" data-bs-toggle="tab"
          data-bs-target="#electrical-pane" type="button" role="tab">
          {{ 'Electrical'|t }}
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="mechanical-tab" data-bs-toggle="tab"
          data-bs-target="#mechanical-pane" type="button" role="tab">
          {{ 'Mechanical'|t }}
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="certifications-tab" data-bs-toggle="tab"
          data-bs-target="#certifications-pane" type="button" role="tab">
          {{ 'Certifications'|t }}
        </button>
      </li>
    </ul>
    <div class="tab-content border border-top-0 p-4" id="specTabsContent">
      <div class="tab-pane fade show active" id="electrical-pane" role="tabpanel">
        {{ product.variation_block__electrical }}
      </div>
      <div class="tab-pane fade" id="mechanical-pane" role="tabpanel">
        {{ product.variation_block__mechanical }}
      </div>
      <div class="tab-pane fade" id="certifications-pane" role="tabpanel">
        {{ product.variation_block__certifications }}
      </div>
    </div>
  </div>
</div>
```

## 5. TESTKRITERIER

- [ ] Tre tabs visas: Electrical, Mechanical, Certifications
- [ ] Klick på tab byter innehåll
- [ ] Innehållet uppdateras vid variantbyte (AJAX)
- [ ] Fungerar på mobil
- [ ] Inga JS-fel i console
