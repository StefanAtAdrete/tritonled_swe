# CURRENT TASK: TASK-032 — Product Card ✅ KLAR

## Status: KLAR — Pushad till GitHub

---

## Vad som är klart

Produktkortet fullt implementerat, verifierat och pushad:
- ✅ Badge med rätt färg (SV + EN)
- ✅ 3 tekniska feature-punkter per produkt
- ✅ Teknisk fot (Watt · Lumen)
- ✅ bg-light card-body
- ✅ Klickbart kort (CSS ::after stretched-link)
- ✅ Hover-effekt (translateY + shadow)
- ✅ Radavstånd mb-4 på col-element
- ✅ Alla 26 produkter har fullständig data (SV + EN)
- ✅ Taxonomitermer översatta till engelska
- ✅ FDT-skill uppdaterad med JSON:API + mockup-regler

---

## Nästa task — kandidater

| Task | Beskrivning | Prioritet |
|---|---|---|
| ConfiguratorImageBlock | Återbygg i LB UI för `led_luminaire_max_opti` | Hög |
| TASK-017b | Produktseriesidor / Views | Medel |
| TASK-018 | Cart page layout | Medel |
| TASK-013 | Attributrensning | Låg |

---

## Tech debt från TASK-032
- Badge-färglogik i Twig → bör till preprocess hook i `.theme`
- `field_short_description` kan förfinas per produkt i admin
- `{{ url }}` tom i Rendered Entity Views-kontext — dokumenterat i task-032

---

## Nyckellärdomar

Se `/docs/tasks/task-032-product-card.md` för fullständig lista.
