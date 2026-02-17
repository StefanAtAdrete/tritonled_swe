# Arbetsmetodik: Uppdelning av huvuduppgifter

**Skapad**: 2026-02-17
**Syfte**: Dokumentera hur vi bryter ner komplexa uppgifter i Drupal

---

## Principen

En frontend-sektion i Drupal är **aldrig** bara en uppgift.
Den består alltid av flera lager som måste byggas i rätt ordning.

**Gör alltid detta innan du börjar koda:**
1. Identifiera alla sub-tasks
2. Sätt rätt ordning (beroenden)
3. Verifiera verktyg per sub-task
4. Identifiera vad som kräver godkännande

---

## Standardmönster för frontend-sektioner

```
1. Content type / Media type
   → Vad ska hanteras och lagras?
   → Verktyg: Admin UI, config export

2. Image styles
   → Rätt bildformat per breakpoint
   → Verktyg: Admin UI (Structure → Image styles)
   → MÅSTE finnas innan view modes

3. View modes
   → Hur renderas innehållet i sin kontext?
   → Verktyg: Admin UI (Manage display)
   → MÅSTE finnas innan Views

4. Views (block/page)
   → Samlar och strukturerar innehållet
   → Verktyg: Admin UI + contrib format-plugins
   → Kräver sällan custom kod

5. Layout Builder
   → Placerar blocket på sidan
   → Verktyg: Admin UI

6. Styling
   → Bootstrap klasser FÖRST
   → Minimal CSS om nödvändigt (kräver godkännande)
   → SDC/template: sista utväg (kräver explicit godkännande)
```

---

## Vad kräver godkännande?

| Åtgärd | Kräver godkännande? |
|--------|---------------------|
| Skapa content type via UI | NEJ |
| Skapa image style via UI | NEJ |
| Konfigurera view mode via UI | NEJ |
| Skapa View via UI | NEJ |
| Bootstrap klasser | NEJ |
| Preprocess hook | JA |
| Custom CSS-fil | JA |
| Template (.html.twig) | JA – explicit |
| SDC-komponent | JA – explicit |
| Custom modul | JA – explicit |

---

## Exempel: Hero Carousel (TASK-002)

```
SUB-A: Content type "Hero Slide"    → Config, ingen kod
SUB-B: Image styles (4:1 / 1:1)    → Config, ingen kod
SUB-C: View mode "Hero"             → Config, ingen kod
SUB-D: View block (carousel)        → views_bootstrap, ingen kod
SUB-E: Layout Builder placering     → UI, ingen kod
SUB-F: Styling overlay/ratio        → Bootstrap + ev. minimal CSS
```

Resultat: 5 av 6 sub-tasks löses utan en rad kod.

---

**Version**: 1.0
**Skapad**: 2026-02-17
