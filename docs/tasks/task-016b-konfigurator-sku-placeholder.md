# Task 016b: Konfigurator — Omstart vid ändring

**Skapad**: 2026-03-18  
**Status**: ✅ KLAR (2026-03-23) — löst av auto-select i SESSION 5. Alla steg fylls alltid i automatiskt, SKU är alltid komplett.  
**Prioritet**: Hög — UX-problem  
**Relaterad**: TASK-015

---

## Problem

När användaren ändrar ett val i konfiguratorn (t.ex. byter Längd efter att ha valt alla steg) så återspeglas ändringen inte korrekt i SKU:t. Användaren måste börja om manuellt utan tydlig återkoppling.

## Önskat beteende

- SKU ska alltid ha platshållare för varje del, t.ex. `M-[A][0][C][8][-J][19][N][1]`
- Varje del uppdateras live när respektive steg väljs
- Delar som inte valts visas som `[?]` eller liknande placeholder
- Alternativt: När ett val ändras återställs alla nedströmssteg OCH SKU-placeholders uppdateras omedelbart

## Acceptanskriterier

- [ ] SKU visar alltid alla delar — valda som kod, ovalda som placeholder
- [ ] Ändring av ett steg återställer nedströmssteg visuellt och i SKU
- [ ] Användaren ser tydligt vilka delar som saknas

## Plan

Uppdatera `buildSku()` i `configurator.js`:
- Iterera alla steps
- Om valt: använd koden
- Om ej valt: visa `[?]` för middle-delar, `[?]` för end-delar
- Exempel: `M-A0[?]8-[?]19N1`

Kräver godkännande innan implementation.
