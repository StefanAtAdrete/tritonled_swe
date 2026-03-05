# Task 014: Reimportera OPTI, SROW, MAX med korrekta CSV:er

**Created**: 2026-03-05  
**Status**: Not Started  
**Last Updated**: 2026-03-05  
**Related Tasks**: TASK-012, TASK-013, TASK-015

---

## 1. DEFINE

### Mål
Reimportera alla varianter för OPTI, SROW och MAX med korrekta CSV:er baserade på beslutad produktstruktur. Accessories-kolumnen tas bort, connection hanteras via `attribute_connection`.

### Syfte
Befintliga varianter är importerade med felaktig attributstruktur (accessories istället för connection). Nya CSV:er ska spegla den beslutade strukturen för alla Triton-produkter.

### Standardattribut per variant (gäller alla Triton-produkter)
- Längd
- Watt
- CCT
- CRI
- IP-rating
- Connection (attribute_connection)
- Model (Standard, Sensor, Emergency)

### Acceptanskriterier
- [ ] OPTI: gamla varianter borttagna, nya importerade med korrekt struktur
- [ ] SROW: gamla varianter borttagna, nya importerade med korrekt struktur
- [ ] MAX: gamla varianter borttagna, nya importerade med korrekt struktur
- [ ] Ingen `attribute_accessories` i någon variant
- [ ] Media (bilder) korrekt mappade per variant
- [ ] Produktsidorna fungerar med AJAX-variantbyte

**Godkänt av Stefan**: ⏳ Väntar

---

## 2. PLAN

### Vald lösning
**Approach**: Feeds CSV-import  
**Specifik lösning**:
1. Ta bort gamla varianter per produkt
2. Skapa nya CSV-filer utan accessories, med korrekt connection
3. Importera via befintliga Feeds-instanser (tritonled_variations)
4. Verifiera media-mappning (reference_by: mid, autocreate: 0)
5. Rensa feeds_item efter import (förhindrar AJAX-krasch)

### Viktiga Feeds-regler (lärdomar från TASK-012)
- Importordning: produktfeed FÖRST, sedan variationsfeed
- Media-mappning: `reference_by: mid` + `autocreate: 0` TILLSAMMANS
- Efter import: rensa `feeds_item` på varianter
- CSV parser: `line_limit: 100` (inte 0)
- Language: `en` i alla mappningar (inte `und`)

**Godkänt av Stefan**: ⏳ Väntar

---

## 3. IMPLEMENT

⏳ Ej påbörjat

---

## 4. VERIFY

⏳ Ej påbörjat

---

## 5. COMPLETION

⏳ Ej påbörjat
