# Produktpresentation — Mall (baserad på Nord Line / TOPPO)
**Skapad**: 2026-04-11  
**Referens**: Nord Line Product Introduction - Toppo 2026Q2.pdf  
**Syfte**: Mall för MAX-seriens produktpresentation (mejl + webb)

---

## Struktur — 5 sektioner

### 1. COVER / INTRO
- Produktnamn + underrubrik
- Applikationsområden (INDUSTRIAL | COMMERCIAL | OFFICE | RETAIL)
- Hero-bild (helbild av produkten, mörk bakgrund)

---

### 2. PRODUKTEGENSKAPER (Product Features)
- Livslängd + garanti (ex: L80 100,000h / X års garanti)
- Ljusutbyte per optiktyp (lm/W)
- Effektjustering (wattintervall)
- CCT-alternativ
- IP/IK-klass
- Tillbehör (nödbelysning, sensor)
- Styrning (DALI, Casambi etc.)
- Kabelanslutning

**Bild**: Produktvarianter i profil (side-by-side), med callout-etiketter per optik

---

### 3. SPRÄNGSKISS (Exploded View)
- Isometrisk 3D-bild av produkten, öppen/exploderad
- Callout-linjer med etiketter till varje komponent/funktion:
  - Monteringssystem
  - Terminal/kabelanslutning
  - Tillbehör (sensor, nödbelysning)
  - Optik/lock
  - Monteringsalternativ

---

### 4. TILLBEHÖR / TILLVAL (Options)
- Sektion per tillbehör (ex: Plug & Play Sensor, Protective Mesh)
- Bild: exploderad vy som visar tillbehöret monterat
- Kort beskrivning per tillbehör

---

### 5. DIMENSIONER
- Topview/sideview teknisk ritning
- Mått: L (flera längder) × W × H
- Endcap-mått separat

---

### 6. DATABLAD (Datasheet)
Tabell med kolumner:

| Nr | Produkt | Längd | Effekt | Strålvinkel | lm/W | Ljusflöde | CCT | UGR |
|----|---------|-------|--------|-------------|------|-----------|-----|-----|
| 1  | [Namn]  | Xmm   | X–XW   | X°          | Xlm/W | Max. XLMLM | XCCT | <XX |

---

### 7. INSTALLATION
- Surface mounting (bild + steg)
- Wire/kabel-montering (bildsekvens 1-2-3)

---

## Filer som behövs

### Obligatoriska bilder
| Fil | Innehåll | Format |
|-----|----------|--------|
| `[produkt]-hero.png` | Helbild, mörk bakgrund, inga callouts | PNG, transparent eller svart bg |
| `[produkt]-exploded.png` | Sprängskiss isometrisk, öppen produkt | PNG, hög upplösning |
| `[produkt]-variants.png` | Optikalternativ side-by-side | PNG |
| `[produkt]-dimensions.png` | Teknisk ritning med mått | PNG eller SVG |
| `[produkt]-installation-surface.png` | Infografik ytmontering | PNG |
| `[produkt]-installation-wire.png` | Bildsekvens trådmontering (3 steg) | PNG |

### Per tillbehör
| Fil | Innehåll |
|-----|----------|
| `[produkt]-accessory-[namn].png` | Exploderad vy med tillbehör monterat |

### Viktigt om bildstil
- **Bakgrund**: Svart (#000000) eller mörkgrå — ger premiumkänsla
- **Callout-linjer**: Gul/guld (#F5C518 eller liknande) — samma färg som TritonLEDs varumärkesfärg
- **Typsnitt callouts**: Vit, sans-serif, medium vikt
- **3D-stil**: Fotorealistisk render ELLER rent teknisk illustration — inte mix

---

## Noteringar för MAX-serien
- MAX finns i flera varianter: MAX BASE, MAX-PRO, MAX-E, MAX-ED — varje variant kan ha egen sprängskiss ELLER en gemensam med variantmarkering
- Endcap-varianter (C, E, V, B, W) kan visas som separate callouts på sprängskissen
- Konfiguratorn på sajten kan länkas från presentationen
- Laurits vill börja med mejl-format → håll bilderna under 150KB/st för e-postkompatibilitet

---

## Frågor att stämma av med Laurits
- [ ] Vilka MAX-varianter ska ingå i första omgången?
- [ ] Ska det vara EN presentation för hela MAX-serien eller en per variant?
- [ ] Har vi 3D-filer (CAD/render) eller behöver vi beställa/göra nya bilder?
- [ ] Vilka kolumner från DE-stock-listan är viktigast att ta med?
