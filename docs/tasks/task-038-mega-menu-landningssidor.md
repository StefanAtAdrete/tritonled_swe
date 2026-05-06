# TASK-038 — Mega menu + produktkategori-landningssidor

**Status: TODO**
**Prioritet: Hög**

## Beskrivning
Mega menu för "Produkter" där varje produkttyp har en egen landningssida.
Landningssidorna visar produkter filtrerade efter taxonomi (produktkategori).

## Referens
- node/13 som inspiration för layout
- Drupal Commerce best practice för kategorisidor

## Att besluta
- Taxonomy term-sidor (automatiskt) vs manuella landningssidor?
- Views med taxonomy-filter eller Views contextual filter?
- Mega menu: Bootstrap Mega Menu-modul eller custom HTML i nav?

## Approach
1. Taxonomy: `product_category` (eller befintlig) som landningssidor
2. Views: Commerce Products filtrerat på taxonomy term (contextual filter)
3. URL: `/produkter/[kategori-namn]` via Pathauto (kopplas till TASK-039)
4. Mega menu: Länka direkt till taxonomy term-sidorna

## Acceptanskriterier
- Varje produktkategori har en landningssida med sina produkter
- Mega menu visar kategorierna med länk till resp. sida
- Fungerar på SV och EN
