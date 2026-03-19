# Task 016c: Konfigurator — Återställ till första giltig kombination

**Skapad**: 2026-03-18  
**Status**: Planned  
**Prioritet**: Medium — UX  
**Relaterad**: TASK-015

---

## Problem

När användaren navigerar bort från produktsidan och sedan tillbaka är konfiguratorn tom (alla val återställda till "— Välj —"). Bättre UX vore att konfiguratorn automatiskt väljer den första giltiga kombinationen.

## Önskat beteende

- Vid sidladdning väljs automatiskt det första tillgängliga alternativet för varje steg i ordning
- "Första giltiga kombination" = välj option[0] för steg 1, sedan option[0] som är giltig för steg 2 givet steg 1, osv.
- Resulterar i ett komplett SKU direkt vid sidladdning
- "Lägg i offert"-knappen är aktiverad direkt

## Acceptanskriterier

- [ ] Konfiguratorn är förifylld med första giltiga kombination vid sidladdning
- [ ] SKU visas komplett direkt
- [ ] "Lägg i offert"-knappen är aktiverad direkt
- [ ] Fungerar för alla 12 produkter

## Plan

Lägg till `autoSelectFirst()` i `configurator.js` som körs efter `render()`:
- Iterera steps i ordning
- För varje steg: välj första option som `isOptionAvailable()` returnerar true för
- Sätt `selections[step.id]` + `select.value`
- Kör `updateVisibility()` + `updateSku()` + `updateButton()` efter

Kräver godkännande innan implementation.
