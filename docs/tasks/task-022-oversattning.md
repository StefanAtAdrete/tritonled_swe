# Task 022: Översättning SV/EN — specs-block, views-block, innehåll

**Created**: 2026-03-25
**Status**: ✅ Completed
**Last Updated**: 2026-03-30
**Related Tasks**: TASK-021, TASK-024

---

## 1. DEFINE

### Mål
Säkerställa att sajten ser likadan ut och är korrekt översatt på både svenska och engelska.

### Identifierade problem (2026-03-25)

| Element | Problem | Lösning |
|---|---|---|
| `ConfiguratorSpecsBlock` — rubriker | "Tekniska specifikationer", "Längd", "Driver" etc. visas alltid på svenska | Drupal `t()` + `.po`-fil eller Interface Translation |
| `ConfiguratorSpecsBlock` — kontaktuppgifter | Hårdkodad svenska i PHP | Antingen OK (samma info båda språk) eller konfigurerbart |
| Views syskon-block — etikett | "OPTI-serien:", "MAX-serien:" | Översätt via Views Translate UI |
| Produktnamn/beskrivningar | Hanteras via Drupal content translation | Verifiera att alla 12 produkter har SV-översättning |
| "Skriv ut / Spara som PDF"-knapp | Hårdkodad svenska | `Drupal.t()` i JS |

### Acceptanskriterier
- [ ] Specs-blockets labels visas på rätt språk (SV/EN)
- [ ] Print-knappens text är översatt
- [ ] Syskon-block etiketter ("OPTI-serien:" / "OPTI series:") visas på rätt språk
- [ ] Alla 12 produkter har SV-översättning
- [ ] Sajten ser konsekvent ut på `/sv/` och `/en/`

---

## 2. PLAN

### Approach per element

#### ConfiguratorSpecsBlock labels — `t()` i PHP
Byt hårdkodade strängar mot `$this->t('Längd')` etc. i `specRows()`.
Lägg till engelska översättningar via **Admin → Configuration → Regional → Translations**.

#### Print-knapp — `Drupal.t()` i JS
`configurator.js` använder redan `Drupal.t()` på ett ställe.
Byt `'Skriv ut / Spara som PDF'` → `Drupal.t('Print / Save as PDF')`.
Lägg till svensk översättning i Interface Translation.

#### Views syskon-block etiketter
Gå till Views → Featured Products → Other [X] models → Translate.
Lägg till svenska översättning av Custom text-fältet.

#### Produktöversättningar
Verifiera att alla 12 produkter har SV-översättning via:
`ddev drush php:eval` — se TASK-022 verify-kommando nedan.

### Verify-kommando
```bash
ddev drush php:eval '$products = \Drupal::entityTypeManager()->getStorage("commerce_product")->loadMultiple(); foreach ($products as $p) { $hasSv = $p->hasTranslation("sv"); echo $p->id() . " | " . $p->getTitle() . " | SV: " . ($hasSv ? "JA" : "NEJ") . PHP_EOL; }'
```

---

## 3. IMPLEMENT

### Steg 1 — ConfiguratorSpecsBlock: byt till t()
- `specRows()` i `ConfiguratorSpecsBlock.php` → `$this->t('Längd')` etc.
- Rubrik "Tekniska specifikationer" → `$this->t('Technical specifications')`
- Print-knapp label → `$this->t('Print / Save as PDF')`

### Steg 2 — configurator.js: Drupal.t()
- `'Skriv ut / Spara som PDF'` → `Drupal.t('Print / Save as PDF')`
- `'Antal:'` → `Drupal.t('Quantity')`
- `'Lägg i offert'` → `Drupal.t('Add to quote')`
- Övriga hårdkodade strängar

### Steg 3 — Interface Translation
- Admin → Configuration → Regional → Translations → Import/manual
- Lägg till SV-översättningar för alla `t()`-strängar

### Steg 4 — Views Translate
- Other MAX models → Translate → Svenska
- Other OPTI models → Translate → Svenska
- Other SROW models → Translate → Svenska

### Steg 5 — Verifiera produktöversättningar
- Kör verify-kommandot ovan
- Komplettera saknade översättningar

---

## 4. VERIFY

- [ ] `/en/product/triton-opti` — specs-labels på engelska
- [ ] `/sv/product/triton-opti` — specs-labels på svenska
- [ ] Print-knapp rätt språk på båda
- [ ] Syskon-block etikett rätt språk
- [ ] Alla 12 produkter har SV

---

## 5. COMPLETION

### Status: ✅ Completed — 2026-03-30

### Genomfört
- ConfiguratorSpecsBlock labels wrappade i `t()`
- Print-knapp och JS-strängar wrappade i `Drupal.t()`
- SV-översättningar tillagda via Interface Translation
- Views syskon-block etiketter översatta
- Produktöversättningar verifierade
