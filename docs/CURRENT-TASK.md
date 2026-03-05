# Aktuell Task

**Task**: TASK-014 (In Progress)
**Status**: In Progress
**Senast uppdaterad**: 2026-03-05
**Fil**: `/docs/tasks/task-014-reimport-varianter.md`

## Senast gjordes (denna session 2026-03-05)

### Splide caption-fix
- Aktiverade `Splide slider` view mode på Image media type
- Lade till `.slide__caption { display: none; }` i `product-gallery.css`
- Rotorsak: Splide renderar media-entitetens namn som caption när nav är dold

### Attribut-städning (TASK-013, delvis klar)
- Inventerade alla attribut — `attribute_accessories` innehåller Connection-värden + DALI
- Skapade nytt `driver`-attribut med värden: On/Off, DALI, DALI-2, 1-10V, Wireless/RF
- Lade till `driver` på variation type default
- Config exporterad
- `attribute_accessories` kvarstår tills TASK-014 är klar (12 varianter refererar det)

### Beslutad produktstruktur (gäller alla Triton-produkter)
**Standardattribut**: Längd, Watt, CCT, CRI, IP-rating, Connection, Model  
**Optik/tillval** (line item fields): Beam angle, övriga optiska val  
**Datafält**: Voltage  
**Separat produkt**: Bracket  

### Tasks skapade
- TASK-013: Attribut-cleanup (In Progress — väntar på TASK-014)
- TASK-014: Reimport OPTI/SROW/MAX (In Progress)
- TASK-015: Bracket som separat produkt
- TASK-016: Optik/tillval som line item fields

## Nästa steg (TASK-014)

1. Stefan laddar upp PDF med produktdata för OPTI, SROW, MAX
2. Analysera PDF → kartlägg attributstruktur per produkt
3. Skapa nya CSV-filer utan accessories, med korrekt struktur
4. Ta bort gamla varianter och reimportera
5. Avsluta TASK-013 (ta bort attribute_accessories)

## Nuläge varianter
- MAX (ID:8): 488 varianter
- SROW (ID:5): 795 varianter  
- OPTI (ID:13): 260 varianter
