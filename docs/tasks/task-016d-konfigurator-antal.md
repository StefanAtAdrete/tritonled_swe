# Task 016d: Konfigurator — Antal-fält

**Skapad**: 2026-03-18  
**Status**: Planned  
**Prioritet**: Medium — offert-kvalitet  
**Relaterad**: TASK-015

---

## Problem

Konfiguratorn saknar ett antal-fält. Offerten/kundvagnen visar alltid kvantitet 1 vilket gör den oanvändbar för B2B-kunder som ofta beställer flera enheter.

## Önskat beteende

- Ett "Antal" (quantity) fält visas i konfiguratorn
- Standardvärde: 1
- Minimum: 1
- Skickas med till Cart API vid "Lägg i offert"
- Visas i cart/offert-sidan som kvantitet

## Acceptanskriterier

- [ ] Antal-fält visas i konfiguratorn (number input, min=1, default=1)
- [ ] Värdet skickas korrekt till `ConfiguratorCartController::addToCart()`
- [ ] Kvantiteten sätts på order item i cart
- [ ] Fungerar för alla 12 produkter

## Plan

**JS (`configurator.js`):**
- Lägg till `<input type="number" min="1" value="1">` i render()
- Inkludera värdet i POST-body som `quantity`

**PHP (`ConfiguratorCartController.php`):**
- Läs `$data['quantity']` ur request
- Validera: heltal >= 1
- Sätt `$order_item->setQuantity($quantity)` innan save

Kräver godkännande innan implementation.
