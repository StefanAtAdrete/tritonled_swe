# Aktuell Task

**Senast uppdaterad**: 2026-04-20

---

## ⏭️ Nästa session startar här: TASK-033 (NY)

**Uppgift**: Rätta källspråk på produkter 15-26 (MAX/OPTI/SROW) + path aliases

### Bakgrund
Produkterna 15-26 har `en` som källspråk men sajten är primärt svensk. Detta orsakar:
- Felaktiga URL:er för svenska versionen (`/sv/product/...` istället för `/product/...`)
- Felaktig rendering-kontext i Views-block
- path_alias genereras inte automatiskt via pathauto för commerce_product

### Plan (kräver backup före!)
1. Ta Backup & Migrate snapshot
2. Ta bort svenska översättningar på produkterna 15-26
3. Ändra källspråk från `en` → `sv` på produkterna 15-26
4. Lägg tillbaka engelska som översättning
5. Regenerera path aliases via pathauto
6. Verifiera att MAX/OPTI/SROW-blocken på startsidan fungerar på båda språken

### Viktigt
- **Backup MÅSTE tas innan** — detta är destructive på translations
- Produkterna 15-26: Triton MAX, MAX-PRO, MAX-S, MAX-E, MAX-ED, OPTI, OPTI-S, OPTI-E, OPTI-ED, SROW, SROW-E, SROW-ED
- Pathauto verkar inte generera aliases automatiskt för commerce_product — undersök varför

### Nuläge (workaround)
- `/sv`-prefix återställt för svenska — sajten fungerar men med prefix
- Path aliases skapas via `createEntityAlias` men försvinner vid `cr`

---

## TASK-031 — Fortsättning

**Uppgift**: Kategori C skelett — `accessories` Layout Builder + skeleton-produkt

### Nästa steg
1. Skapa Layout Builder YAML för `accessories`
2. Skapa skeleton-variation + skeleton-produkt för `accessories`
3. Verifiera accessories-sidan i browsern
4. Skeleton-produkter för `street_area` och `ex_hazardous`
5. Commit + checkpoint

---

### Gjort idag (2026-04-20) — session 3
- ✅ Path aliases skapade för alla commerce_product via createEntityAlias
- ⚠️ MAX/OPTI/SROW källspråksproblem identifierat — workaround: /sv-prefix återställt
- ⚠️ Pathauto genererar inte aliases automatiskt för commerce_product — utreds i TASK-033

### Gjort idag (2026-04-20) — session 2
- ✅ `street_area` — 10 produktfält + attribute_watt på variation skapade
- ✅ `street_area` — Layout Builder YAML verifierad
- ✅ `ex_hazardous` — 9 produktfält + attribute_watt på variation skapade
- ✅ `ex_hazardous` — Layout Builder YAML uppdaterad
- ✅ `accessories` — 9 produktfält + field_variation_media + field_warranty_years skapade
- ✅ Skill skapad: `add-category-c-product`

### Gjort idag (2026-04-20) — session 1
- ✅ Floodlight-produkt svensk översättning tillagd
- ✅ Drupal core uppdaterad 11.3.6 → 11.3.7
- ✅ PhotoSwipe installerad, Colorbox avinstallerad
- ✅ High mast: fält + Layout Builder display config skapad

---

## Öppna tasks

| Task | Status | Beskrivning |
|------|--------|-------------|
| TASK-033 | 🆕 Ny | Rätta källspråk produkter 15-26 + path aliases |
| TASK-031 | 🔄 In Progress | Site Audit — Layout Builder Kategori C + översättningar |
| TASK-029 | ⏸️ Pausad | Fas 3: CSV-mallar & feeds (väntar på audit) |
| TASK-018 | 🔄 In Progress | Cart page layout |
| TASK-017b | 🔄 In Progress | Produktseriesidor + Views |
| TASK-013 | 🔄 In Progress | Attribut-cleanup |
| TASK-030 | ⏳ Parkerad | UX-feedback Thomas |
| TASK-032 | ✅ Klar | Hero-bildspel startsida |
