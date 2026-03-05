# Task 012: Produktdata-import och varianthantering (OPTI, SROW, MAX)

**Created**: 2026-03-04  
**Status**: In Progress  
**Last Updated**: 2026-03-04  
**Related Tasks**: TASK-011 (lb_tabs översättning)

---

## 1. DEFINE

### Mål
Ta en produkt i taget (OPTI, SROW, MAX), analysera PDF-datablad, skapa en dedikerad Feeds-import per produkt och byta ut befintliga varianter mot korrekta varianter baserade på produktdatabladen.

### Syfte
De tre produkterna har SKU och varianter men data är inte komplett/korrekt. Vi behöver ett reproducerbart importflöde baserat på verkliga produktdata.

### Scope per produkt
1. **Analysera PDF-datablad** — vilka varianter finns? Vilka fält är relevanta?
2. **Resonera om datastruktur** — Driver (spänningskälla), Connection (kopplingar), Bracket (tillbehör/separat produkt?)
3. **Skapa Feeds-import** — en feed per produkt
4. **Ta bort gamla varianter** — rensa befintliga felaktiga varianter
5. **Importera korrekta varianter** — via feed

### Viktiga designbeslut att ta ställning till
- **Driver**: Är spänningskällan (Meanwell etc) ett eget attribut eller fält?
- **Connection**: Kopplingar till/från armaturen — attribut eller fält?
- **Bracket**: Tillbehör — add-on variant på produkten ELLER separat produkt?

### ✅ Beslutad produktstruktur för Triton-produkter (2026-03-05)

Gäller OPTI som mall — samma struktur appliceras på alla Triton-produkter.

**Standardattribut** (ingår i bas-SKU, definierar varianten):
- Längd
- Watt
- CCT (färgtemperatur)
- CRI
- IP-rating
- Connection (CG, EN, W1, W2, W3)
- Model (Standard, Sensor, Emergency)

**Optik/tillval** (line item fields — "text-på-mugg"-funktionalitet):
- Beam angle (förutbestämda val, visas som knappar på produktsidan)
- Övriga optiska val
- Sparas som metadata på order line item, påverkar ej variant/SKU
- Syns på offerten som tillval på produktraden
- Ej tvingande

**Datafält** (informationstext, ej valbart av kunden):
- Voltage

**Separat produkt** (egen Commerce-produkt, visas som add-on suggestion):
- Bracket

### ⚠️ Att åtgärda
- `attribute_accessories` är felnamnat — innehåller egentligen Connection-värden (drivers/kopplingar)
- Ska döpas om till `attribute_connection` eller ersättas av det befintliga `attribute_connection`
- Gäller OPTI, SROW och MAX — alla tre behöver uppdaterade CSV-importer utan accessories-kolumnen
- Bracket ska istället skapas som separat Commerce-produkt per produktserie

### Acceptanskriterier
- [ ] OPTI: korrekta varianter importerade via Feeds
- [ ] SROW: korrekta varianter importerade via Feeds
- [ ] MAX: korrekta varianter importerade via Feeds
- [ ] Varje produkt har en dedikerad feed-konfiguration
- [ ] Gamla felaktiga varianter borttagna
- [ ] Bracket-strategi beslutad och dokumenterad

**Godkänt av Stefan**: ⏳ Väntar på PDF

---

## 2. PLAN

### Öppna affärslogiska frågor (kräver beslut av produktchef)

1. **Variantmodell** — Tre alternativ:
   - A) Fullt statiska SKU:er (alla kombinationer = tusentals varianter)
   - B) Hybrid: Bas-variant (längd/watt/CRI/dimning/connection/IP) + optik som konfigurationssteg på order line item (~200-500 varianter per produkt)
   - C) Dynamisk SKU-konfigurator (0 fördefinierade varianter, helt custom kod)

2. **Optik** — Ingår optik i bas-SKU eller väljs separat?

3. **Prissättning** — Prissätts per hel kombination eller per attribut/tillägg?

4. **Affärsprocess** — E-handel (statiska priser) eller offertverktyg (konfiguration → manuell prissättning)?

⏳ Väntar på beslut från produktchef innan plan kan fastställas

---

## 3. IMPLEMENT

⏳ Ej påbörjat

---

## 4. VERIFY

⏳ Ej påbörjat

---

## 5. COMPLETION

⏳ Ej påbörjat
