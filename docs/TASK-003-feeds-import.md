# TASK-003: Commerce Feeds CSV Import

## Status: PÅGÅENDE

### Vad som fungerar
- Feed 1 (Products): MAX + SROW produkter importerade
- Feed 2 (Variations): MAX + SROW varianter importerade
- Feed 3 (OPTI Products): Triton OPTI produkt importerad
- Feed 4 (OPTI Variations): 500 OPTI-varianter importerade med bilder
- Attributreferenser fungerar (watt, cct, ip_rating, beam_angle, color, length, connection)
- Media-mappning fixad — reference_by=mid, autocreate=0
- Separata feeds per produktserie etablerat som standard

### Feed-instanser
| ID | Label | Typ | Fil |
|----|-------|-----|-----|
| 1 | TritonLED Products Import | tritonled_products | master_3.csv |
| 2 | TritonLED Variations Import | tritonled_variations | master_4.csv |
| 3 | TritonLED OPTI Products Import | tritonled_products | triton-opti.csv |
| 4 | TritonLED OPTI Variations Import | tritonled_variations | triton-opti.csv |

### OPTI-varianter
- 500 varianter: 4 längder × 3 watt × 2 drivers × 2 connections × 5 beam angles × 2 IP
- B2L-driver endast på D/2005mm 162W
- Bilder: Wago (ID 28), EnstoNet (ID 27)
- CSV: `private/feeds/triton-opti.csv`

---

## Lärdomar — se docs/03-solutions/feeds-csv-import.md

---

## Nästa steg
1. Skapa feeds för SROW (separera från master)
2. Verifiera att alla attributvärden finns (connection, bracket etc.)
3. Sätt upp Cron-schema för automatisk import
4. Testa uppdateringsflöde — ändra en rad i CSV och kör feed igen
