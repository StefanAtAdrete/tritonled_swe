# CURRENT TASK: TASK-032 — Product Card ✅ KLAR

## Status: Steg 5 VERIFY — GODKÄNT. Klar för commit och nästa task.

---

## Vad som är klart

Produktkortet är fullt implementerat och verifierat på BÅDA språken (SV + EN):
- ✅ Badge med miljöterm + rätt Bootstrap-färg per kategori
- ✅ 3 tekniska feature-punkter per produkt
- ✅ Teknisk fot (Watt · Lumen)
- ✅ Alla 26 produkter (15–40) har fullständig data
- ✅ Taxonomitermer översatta till engelska

## Senaste commit
`e0929c77a` — [TASK-032] Add technical footer and Lager & industri badge to product card template

## Uncommittad kod
- `commerce-product--card.html.twig` (badge-färg för EN + `|replace` fix)
- `docs/tasks/task-032-product-card.md` (uppdaterad)

## Nästa steg
1. Commit: `[TASK-032] Fix badge colors for EN language + HTML entity fix`
2. Beslut: Steg 6 (JSON:API endpoint) nu eller parkera?
3. Nästa task att ta tag i — kandidater:
   - TASK-018: Cart page layout
   - TASK-017b: Produktseriesidor (Views)
   - TASK-013: Attributrensning
   - Rebuild ConfiguratorImageBlock för `led_luminaire_max_opti`

## Tech debt att ta tag i senare
- Badge-färglogik i Twig-template bör flyttas till preprocess hook
- `field_short_description` innehåll kan förfinas per produkt (Stefan gör i admin)

## Nyckellärdomar från TASK-032
Se `/docs/tasks/task-032-product-card.md` för fullständig lista.

Kortversion:
- JSON:API PATCH via browser = snabbaste bulk-content-metoden
- SV-produkter PATCHas via `/jsonapi/`, EN-baserade via `/en/jsonapi/`
- `|render|striptags|replace({'&amp;': '&'})` för termnamn med specialtecken
- `getUntranslated()` fungerar EJ i Twig — bara i PHP
- Badge-villkor måste täcka BÅDA språkens term-namn
