# TASK-032 — Product Card (display-component)

## FDT-klass: display-component + data-model
## Status: Steg 5 — VERIFY ✅ KLAR (pending commit)

---

## Steg 0 — SPEC ✅ KLAR

**Mockup:** `/docs/tasks/assets/task-032-mockup.html`

**UX-mål:** Lugnt, lättläst produktkort för elektriker.
**Kortets struktur:**
- Produktbild med miljöbadge i övre högra hörnet
- Titel (länkad till produktsidan)
- Feature-punkter via `field_short_description` (3 punkter)
- Teknisk fot: Watt · Lumen

---

## Steg 1 — DATA ✅ KLAR

- `field_short_description` på alla 10 bundles ✅
- `field_installation_environment` (taxonomy) på alla 10 bundles ✅
- `field_watt_range` + `field_lumen_range` på alla 10 bundles ✅
- Taxonomi `installation_environment`: Allround, Utomhus, Lager & industri, Ex-miljö, Kontor, Gata & Area, Installation ✅
- Engelska översättningar på alla taxonomitermer ✅

---

## Steg 2 — CONTENT ✅ KLAR

Alla 26 produkter (15–40) har:
- `field_installation_environment` ifyllt ✅
- `field_watt_range` + `field_lumen_range` ifyllt ✅
- `field_short_description` (SV + EN) ifyllt ✅

Data-metod: JSON:API PATCH via browser (Claude in Chrome) — se FDT-skill för workflow.

---

## Steg 3 — DISPLAY ✅ KLAR

- View mode `commerce_product.card` + 10 entity view displays ✅
- Template `commerce-product--card.html.twig` ✅
- Bootstrap card-markup, dynamisk badge-färg, teknisk fot ✅

---

## Steg 4 — LAYOUT ✅ KLAR

- Views-block `tritonled_product_cards` placerat på startsidan ✅

---

## Steg 5 — VERIFY ✅ KLAR

**Verifierat mot mockup:** `/docs/tasks/assets/task-032-mockup.html`

- ✅ Badge med rätt färg på BÅDA språken (SV + EN)
- ✅ Features (3 tekniska punkter) på alla produkter
- ✅ Teknisk fot (Watt · Lumen) på alla produkter
- ✅ Bildaspekt rätt
- ✅ Kortborder/shadow
- ✅ Titel länkad

**Pending:** Checkpoint-commit

---

## Steg 6 — API ⏸ Parkerad

Avgörs i separat session/task.

---

## Tekniska lärdomar (denna task)

### Twig
- `{{ product.field_xxx }}` används i Commerce templates — INTE `{{ content.field_xxx }}`
- `string_long` kräver formatter `basic_string`, inte `string`
- `|render|striptags|trim` HTML-encodar `&` till `&amp;` → fix: `|replace({'&amp;': '&'})`
- `product.getUntranslated()` fungerar EJ i Twig sandbox — använd PHP/preprocess istället
- `.entity.name.value` på entity reference fungerar EJ reliably i Twig sandbox
- **Tech debt:** Badge-färgvillkor bör flyttas till preprocess hook i `.theme`-filen

### Flerspråkighet
- `field_installation_environment` är translatable → måste sättas på RÄTT translation
- SV-produkter (MAX/OPTI/SROW): PATCH via `/jsonapi/` (default/SV)
- EN-baserade produkter (surge_protection): PATCH via `/en/jsonapi/`
- Taxonomy-termer behöver EN-översättning för att badge-text ska stämma på EN-sidan
- Badge-villkor måste inkludera BÅDE SV och EN term-namn

### JSON:API bulk-update
- Aktivera writes via UI (inte `drush config:set`) → `drush cr` syncar ej alltid
- Relationship PATCH: använd `relationships`-nyckeln, inte `attributes`
- Stäng always `read_only: true` direkt efter bulk-operation
- Se FDT-skill för komplett workflow

### Config
- Views + Rendered Entity kräver språkfilter för att undvika dubbletter
- `variation__layout_builder` och `variation_price` måste döljas explicit i entity view display
