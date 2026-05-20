# TASK-058 — Produkttyp: bollard

**Created**: 2026-05-20
**Status**: Not Started
**Last Updated**: 2026-05-20
**Related Tasks**: TASK-056 (master), TASK-057 (wall_light)

---

## 1. DEFINE

### Mål
Skapa produkttypen `bollard` med tillhörande taxonomy term, fältstruktur, feeds och CSV-import för UpShine bollard-serier.

### Produktserier (UpShine)
| Serie | Typ | Färg |
|---|---|---|
| WL203A / WL203B | Wall Light @ Bollard | — |
| NL203-50 / NL203-100 | Bollard | — |
| WL196 | Wall Light @ Bollard | Grey |
| NL196-600 / NL196-900 | Bollard | Grey |

### Acceptanskriterier
- [ ] Taxonomy term `Bollard` (EN) / `Pollare` (SV) skapad
- [ ] Product type `bollard` + variation type `bollard_variation` skapad
- [ ] Fält tilldelade (kopiera från befintlig typ)
- [ ] CSV-mall skapad och dokumenterad
- [ ] Feeds skapade: `tritonled_bollard_products` + `tritonled_bollard_variations`
- [ ] Minst 1 testprodukt importerad och verifierad
- [ ] Full import av alla NL/WL bollard-serier
- [ ] Gemensam landningssida (wall_light + bollard) skapad i Views + Layout Builder
- [ ] Bilder kopplade (manuellt, sista steget)

**Godkänt av Stefan**: ⏳ Väntar

---

## 2. PLAN

### Approach
Kopiera fältstruktur från `street_area` (närmast likvärdig utomhusprodukt).

**Fält på variation** (samma som övriga Category C):
- `field_lumens`, `field_warranty_years`, `field_cri`, `field_variation_media`

**Fält på produkt:**
- `field_product_category` (→ `bollard` term)
- `field_product_media`
- `field_ip_class`, `field_driver`, `field_dimming`
- `field_producer` (UpShine)

**Landningssida (gemensam med wall_light):**
En View som filtrerar `product_categories` IN (`wall_light`, `bollard`).
Sidas via Layout Builder — skapas i denna task (eftersom bollard är sista av de två).

### Feeds
- Feed type: `tritonled_bollard_products`
- Feed type: `tritonled_bollard_variations`
- CSV-fil: `private://feeds/bollard.csv`

**Godkänt av Stefan**: ⏳ Väntar

---

## 3. IMPLEMENT

*(Påbörjas efter godkänt PLAN)*

*(Samma stegstruktur som TASK-057)*

---

## 4. VERIFY

*(Efter implementation)*

---

## 5. COMPLETION

### Nästa steg
→ Gemensam landningssida Wall Light + Bollard klar
→ TASK-059 (linear_led / Batten DB218)
