# Aktuell Task

**Task**: TASK-023 Konfigurator mobiloptimering ✅
**Status**: ✅ Klar
**Senast uppdaterad**: 2026-03-27

---

## Senast gjort: Session 2026-03-27

### Bilder & Media
- Bekräftade att TME-bilder (Emergency) redan finns i Drupal: MID 81-84, 101
- Skapade TMED-media-entiteter för MAX-ED (product 19) som återanvänder TME-filerna:
  - MID 112: TMED-E → FID 156
  - MID 113: TMED-V → FID 157
  - MID 114: TMED-B → FID 158
  - MID 115: TMED-W → FID 159
- Skapade TMP-media-entiteter för MAX-PRO (product 16) som återanvänder TM-filerna:
  - MID 116: TMP-C → FID 151
  - MID 117: TMP-E → FID 152
  - MID 118: TMP-B → FID 154
  - MID 119: TMP-W → FID 155
  - MID 120: TMP-default → FID 151
- MAX-PRO har ingen V (Wago Grey) — bara C, E, B, W per schema

### TASK-023 — Konfigurator mobiloptimering ✅
- Ersatte native `<select>` med Bootstrap 5 custom dropdowns i `configurator.js`
- Bootstrap/Popper.js hanterar positionering — öppnar alltid nedåt
- Uppdaterade: `render()`, `autoSelectFirst()`, `updateVisibility()`, `clearSelectionsAfter()`
- Ingen backend-ändring — POST-data oförändrad
- Verifierat: tydligare UX på mobil

---

## Öppna tasks

| Task | Status | Beskrivning |
|------|--------|-------------|
| TASK-022 | 🔄 Delvis klar | Översättning — Views syskon-block kvar |
| TASK-023 | ✅ Klar | Konfigurator mobiloptimering |
| TASK-017b | 🔄 In Progress | Produktseriesidor + Views |
| TASK-017 | Planned | Cart block styling |
| TASK-018 | In Progress | Cart page layout |
| TASK-013 | In Progress | Attribut-cleanup |
| TASK-019 | In Progress | Klaro GDPR cookie consent |
| Config Split | ✅ Klar | Separera lokal/prod config |

---

## Media-entiteter per produkt (konfigurator)

| Product | ID | MIDs |
|---------|-----|------|
| MAX BASE | 15 | 41(TM-C), 42(TM-E), 43(TM-V), 44(TM-B), 45(TM-W), 67(TM-default) |
| MAX-PRO | 16 | 116(TMP-C), 117(TMP-E), 118(TMP-B), 119(TMP-W), 120(TMP-default) |
| MAX-E | 18 | 81(TME-E), 82(TME-V), 83(TME-B), 84(TME-W), 101(TME-default) |
| MAX-ED | 19 | 112(TMED-E), 113(TMED-V), 114(TMED-B), 115(TMED-W), 102(TMED-default) |
