# Task 017b: Produktseriesida + Live Specs-display

**Skapad**: 2026-03-18  
**Status**: Planned  
**Prioritet**: Medel  
**Relaterad**: TASK-015

---

## Bakgrund

Triton Solutions referensimplementation har ett 3-stegs flöde:
1. Produktseriesida (select/max)
2. Konfigurationssida (config/max-s) — konfigurera
3. Produktsida (product/max-s?sku=...) — visar specs + bild

**Förbättring:** Slå ihop steg 2+3 — specs och bild uppdateras **live** direkt i konfiguratorn utan extra sidladdning. Bättre UX, färre klick.

---

## Del 1: Produktseriesida (View)

En sida som listar alla produkter inom en serie som kort.

**Exempel:** `/sv/produkter/max` visar MAX BASE, MAX-PRO, MAX-S, MAX-E, MAX-ED som kort med bild + knapp → länk till produktsidan.

### Implementation
- Drupal View (block eller page) som filtrerar Commerce-produkter per product type
- Alternativt: taxonomi-baserad filtrering om produktserier finns som taxonomy
- Bootstrap card-layout via Views och view modes
- **Kräver ingen custom kod** — Views + Bootstrap klasser

---

## Del 2: Live Specs-display i konfiguratorn

När användaren väljer steg i konfiguratorn uppdateras en specs-tabell live med:

| Spec | Källa |
|------|-------|
| Artikelnummer | Genererat SKU |
| Längd | Val i `length`-steget (option label) |
| Effekt | Val i `watt`-steget (t.ex. "19W") |
| Ljusflöde | Parsas från watt-label (t.ex. "3316lm") |
| Ström | Parsas från watt-label (t.ex. "350mA") |
| Ljuseffektivitet | Parsas från watt-label (t.ex. "174lm/W") |
| Anslutning | Val i `endcap`-steget (option label) |
| Optik | Val i `optic`-steget (option label) |
| Kelvin | Val i `kelvin`-steget (option label) |
| Driver | Val i `driver`-steget (option label) |
| CRI | Val i `cri`-steget (option label) |
| Färg | Val i `color`-steget (option label) |

### Implementation
- JS i `configurator.js` — renderar `<dl>` eller `<table>` med specs
- Uppdateras vid varje val-ändring
- Watt-label parsas med regex: `(\d+)W (\d+)lm (\d+)mA (\d+)lm/W`
- **Kräver ingen extra Drupal-konfiguration** — ren JS

---

## Del 3: URL-parametrar (nice-to-have)

Konfigurationen kodas i URL-parametrar så att länken är delbar/bookmarkbar:
`/sv/product/triton-max-gen-3-base?length=A&driver=0&endcap=C&cri=8&kelvin=-J&watt=19&optic=N&color=1`

### Implementation
- JS: `history.replaceState()` vid varje val
- JS: Läser URL-parametrar vid sidladdning och förifyljer selections
- **Kräver ingen Drupal-konfiguration**

---

## Acceptanskriterier

- [ ] Produktseriesida visar alla produkter i en serie som kort
- [ ] Specs-tabell uppdateras live vid val i konfiguratorn
- [ ] Watt-label parsas korrekt (W, lm, mA, lm/W)
- [ ] Specs visas även när inte alla steg är valda (partiell display)
- [ ] (Nice-to-have) URL uppdateras vid val

## Prioritet

Del 2 (live specs) är högst prioriterat — direkt värde för UX.  
Del 1 (produktseriesida) är enkel men beroende av produktdata.  
Del 3 (URL-parametrar) är nice-to-have.

Kräver godkännande innan implementation.
