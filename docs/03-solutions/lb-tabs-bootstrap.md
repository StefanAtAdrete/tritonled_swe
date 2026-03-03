# lb_tabs — Bootstrap-styling & Layout Builder-integration

**Skapad**: 2026-03-03  
**Task**: TASK-012  

---

## Problem

`lb_tabs` använder jQuery UI Tabs som initieras via `tabs.js`. Detta krockar med Bootstrap 5 på två sätt:
1. jQuery UI lägger till egna klasser (`ui-tabs`, `ui-state-active` etc.) som skriver över Bootstrap-klasser
2. Template-override med Bootstrap-klasser räcker inte — jQuery UI JS initieras efter och skriver över

## Lösning

### 1. Template override
Kopiera `lb-tabs-tabs.html.twig` till `themes/custom/tritonled_radix/templates/layout/`.

Dela upp i två lägen via `inLayoutBuilder`:
- **Layout Builder-läge**: Rendera block staplade utan tabs — möjliggör konfiguration av varje block
- **Frontend**: Bootstrap tabs med `nav nav-tabs`, `nav-link`, `tab-content`, `tab-pane fade`

### 2. JS override
Skapa `js/lb-tabs-bootstrap.js` i temat som **overridar** `Drupal.behaviors.lb_tabs` (samma nyckel):

```javascript
Drupal.behaviors.lb_tabs = {
  attach: function (context, settings) {
    once('lb-tabs-bootstrap', '.lb-tabs-tabs', context).forEach(function (wrapper) {
      var $wrapper = $(wrapper);
      // Destroy jQuery UI om initierad
      if ($wrapper.hasClass('ui-tabs')) {
        try { $wrapper.tabs('destroy'); } catch(e) {}
      }
      // Bootstrap tabs
      $wrapper.find('[data-bs-toggle="tab"]').each(function () {
        this.addEventListener('click', function (e) {
          e.preventDefault();
          bootstrap.Tab.getOrCreateInstance(this).show();
        });
      });
      // Visa första tab
      var firstTab = $wrapper.find('[data-bs-toggle="tab"]').first()[0];
      if (firstTab) {
        bootstrap.Tab.getOrCreateInstance(firstTab).show();
      }
    });
  }
};
```

### 3. CSS override
Skapa `css/components/lb-tabs.css` för att neutralisera jQuery UI-stilen och applicera Bootstrap-utseende på `.ui-tabs-nav`.

### 4. Library + hook_page_attachments
Registrera biblioteket i `tritonled_radix.libraries.yml` och ladda det via `hook_page_attachments` på produktsidor:

```php
function tritonled_radix_page_attachments(array &$attachments) {
  $route = \Drupal::routeMatch()->getRouteName();
  if ($route === 'entity.commerce_product.canonical') {
    $attachments['#attached']['library'][] = 'tritonled_radix/lb-tabs-bootstrap';
  }
}
```

## Viktiga insikter

- ❌ Template-override ensam räcker INTE — jQuery UI JS körs efteråt
- ✅ Override `Drupal.behaviors.lb_tabs` (samma nyckel) — ersätter modulens behavior helt
- ✅ `inLayoutBuilder` används för att visa staplade block i LB-läget
- ✅ I LB-läget: block visas staplade med tab-etikett som rubrik → alla block konfigurerbara
- ✅ På frontend: Bootstrap tabs med fade-animering
- ⚠️ `attach_library` i template fungerar inte alltid — använd `hook_page_attachments` istället

## Filer

```
themes/custom/tritonled_radix/
├── templates/layout/lb-tabs-tabs.html.twig   ← Template override
├── js/lb-tabs-bootstrap.js                    ← jQuery UI → Bootstrap tabs
├── css/components/lb-tabs.css                 ← CSS override
└── tritonled_radix.libraries.yml              ← lb-tabs-bootstrap library
tritonled_radix.theme                          ← hook_page_attachments
```
