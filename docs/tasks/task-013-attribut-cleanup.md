# Task 013: Städa upp attribut — attribute_accessories → attribute_connection

**Created**: 2026-03-05  
**Status**: In Progress  
**Last Updated**: 2026-03-05  
**Related Tasks**: TASK-012, TASK-014

---

## 1. DEFINE

### Mål
Ta bort `attribute_accessories` från alla produktvarianter och säkerställa att `attribute_connection` används konsekvent för kopplingstyper (CG, EN, W1, W2, W3 etc).

### Syfte
`attribute_accessories` är felnamnat — det innehåller egentligen Connection-värden (drivers/kopplingar). Attributet ska ersättas av det befintliga `attribute_connection` för att spegla korrekt datastruktur.

### Acceptanskriterier
- [ ] `attribute_accessories` borttaget från variation type
- [ ] Alla OPTI-varianter använder `attribute_connection` för kopplingstyper
- [ ] Alla SROW-varianter använder `attribute_connection` för kopplingstyper
- [ ] Alla MAX-varianter använder `attribute_connection` för kopplingstyper
- [ ] Inga brutna varianter efter städning

**Godkänt av Stefan**: ⏳ Väntar

---

## 2. PLAN

### Vald lösning
**Approach**: Config + CSV-reimport  
**Specifik lösning**:
1. Skapa nytt attribut `driver` med värden: On/Off, DALI, DALI-2, 1-10V, Wireless/RF
2. Verifiera att `attribute_connection` redan har rätt värden (CG, EN, W1, W2, W3)
3. Ta bort accessories-kolumnen från alla CSV-filer (TASK-014)
4. Reimportera varianter via Feeds (TASK-014 hanterar detta)
5. Ta bort `attribute_accessories` från variation type när inga varianter längre refererar det

### Attribut-inventering (2026-03-05)
Befintliga attribut som behöver åtgärdas:
- `accessories` → **ta bort** — innehåller W1/W2/W3/CG/EN (dubletter av connection) + DALI (flytta till driver)
- `beam_angle` → **behåll** tills vidare, men flytta till line item fields i TASK-016
- `bracket` → **behåll** tills vidare, men flytta till separat produkt i TASK-015
- `voltage` → **behåll** tills vidare, men flytta till datafält i TASK-016

Nytt attribut att skapa:
- `driver` med värden: On/Off, DALI, DALI-2, 1-10V, Wireless/RF

### Motivering
Renare datastruktur, rätt semantik. Görs via CSV-reimport som ändå behövs (TASK-014).

### Beroenden
- Måste göras INNAN TASK-014 (reimport av varianter)
- TASK-014 hanterar själva reimport-steget

**Godkänt av Stefan**: ⏳ Väntar

---

## 3. IMPLEMENT

### Steg
1. **Skapat `driver`-attribut med värden**
   - On/Off (141), DALI (142), DALI-2 (143), 1-10V (144), Wireless/RF (145)
   - Git commit: `[TASK-013] Skapa driver-attribut`

2. **Lagt till `driver` på variation type `default`**
   - Via Drush php-eval
   - Config exporterad: `ddev drush cex -y`

3. **`attribute_accessories` kvarstår tills vidare**
   - 12 varianter refererar fortfarande accessories-värden
   - Kan tas bort först efter TASK-014 (reimport av varianter)

---

## 4. VERIFY

⏳ Ej påbörjat

---

## 5. COMPLETION

⏳ Ej påbörjat
