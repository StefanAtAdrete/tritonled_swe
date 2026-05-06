# TASK-039 — Pathauto — URL-struktur SV/EN

**Status: TODO**
**Prioritet: Medel**

## Beskrivning
Installera och konfigurera Pathauto för att generera SEO-vänliga URL:er.

## Frågeställning
- Svenska som default (ingen prefix) → `/produkter/tritonmax-linear`
- Engelska med `/en/` prefix → `/en/products/tritonmax-linear`
- Ska slug vara på svenska eller engelska? Rekommendation: svenska för SV, engelska för EN (Pathauto hanterar detta via language pattern)

## Pattern-förslag
- Artikel/case: `/[node:title]`
- Commerce produkt: `/produkter/[node:title]` (SV) / `/products/[node:title]` (EN)
- Taxonomy term: `/produkter/[term:name]` (SV)

## Steg
1. `ddev composer require drupal/pathauto`
2. `ddev drush en pathauto -y`
3. Konfigurera patterns per content type och språk
4. Bulk-generera alias för befintligt innehåll

## Acceptanskriterier
- Alla noder får automatiska, läsbara URL:er
- SV och EN har egna slug-patterns
- Befintliga URL:er omdirigeras (redirect-modul)
