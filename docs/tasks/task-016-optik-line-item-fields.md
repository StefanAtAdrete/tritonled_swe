# Task 016: Optik/tillval som line item fields (text-på-mugg)

**Created**: 2026-03-05  
**Status**: Not Started  
**Last Updated**: 2026-03-05  
**Related Tasks**: TASK-012, TASK-014, TASK-015

---

## 1. DEFINE

### Mål
Implementera optik/tillval (beam angle, övriga optiska val) som valbara line item fields på produktsidan. Valen sparas som metadata på order line item — inte som separata varianter.

### Syfte
Optiska val är produktkonfiguration, inte variantdefinition. Kunden ska kunna välja optik direkt på produktsidan (knappar/lista) och valet följer med på offerten utan att skapa nya SKU:er.

### Beteende
- Visas som knappar på produktsidan (likt attribut men utan AJAX-variantbyte)
- Ej tvingande — kunden kan lämna tomt
- Sparas på order line item
- Syns på offerten som tillval på produktraden
- Förutbestämda val (inte fritext)

### Acceptanskriterier
- [ ] Beam angle valbart på produktsidan via knappar
- [ ] Val sparas på order line item
- [ ] Val visas på offerten/order
- [ ] Ej tvingande — kan lämnas tomt
- [ ] Fungerar för alla Triton-produkter

**Godkänt av Stefan**: ⏳ Väntar

---

## 2. PLAN

### Öppna frågor (kräver beslut)
- Vilket Commerce-mönster? (Order item fields, Product add-ons, custom?)
- Är beam angle-värden samma för alla produkter eller produktspecifika?
- Ska valen styras av vilken variant som är vald?

### Kandidatmoduler att undersöka
- **Commerce Order Item Fields** (core-funktionalitet)
- **Commerce Product Options** (contrib)
- **Commerce Customizable Products** (contrib — text-på-mugg-mönstret)

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
