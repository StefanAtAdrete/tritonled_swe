# Bildkartläggning — Endcap per produktmodell

**Skapad**: 2026-03-18  
**Syfte**: Referens för `imageMap` i `field_configurator_schema`  
**Används av**: TASK-016e

---

## Endcap-koder → bildnamn

| Kod | Label | Förklaring |
|-----|-------|------------|
| C | Cable Gland (IP43) | CG |
| E | EnstoNet (IP20) | EN |
| V | W1 | Wago W1 |
| B | Wago Dali Blue (IP20) | W2 |
| W | Wago Infinity (IP20) | W3 |

---

## MAX BASE (TM-) — produkt 15, 16

| Kod | Fil | URL |
|-----|-----|-----|
| C | TM-CableGland_CG(s).png | `/sites/default/files/2026-03/TM-CableGland_CG%28s%29.png` |
| E | TM-Ensto(s.png | `/sites/default/files/2026-03/TM-Ensto%28s.png` |
| V | TM-Wago_W1(s).png | `/sites/default/files/2026-03/TM-Wago_W1%28s%29.png` |
| B | TM-Wago_W2(s).png | `/sites/default/files/2026-03/TM-Wago_W2%28s%29.png` |
| W | TM-Wago_W3(s-).png | `/sites/default/files/2026-03/TM-Wago_W3%28s-%29.png` |

Alternativa (äldre) filer under /products/max/:
- TM_CG(m).png → `/sites/default/files/products/max/TM_CG%28m%29.png`
- TM_EN(m).png → `/sites/default/files/products/max/TM_EN%28m%29.png`
- TM_W1(m).png → `/sites/default/files/products/max/TM_W1%28m%29.png`
- TM_W2(m).png → `/sites/default/files/products/max/TM_W2%28m%29.png`
- TM_W3(m).png → `/sites/default/files/products/max/TM_W3%28m%29.png`

**Rekommendation**: Använd 2026-03-filerna (nyare, konsekvent namngivning).

---

## MAX-S (TMS-) — produkt 17

| Kod | Fil | URL |
|-----|-----|-----|
| C | TMS-CableGland_CG(s-).png | `/sites/default/files/2026-03/TMS-CableGland_CG%28s-%29.png` |
| E | TMS-Ensto_EN(s-).png | `/sites/default/files/2026-03/TMS-Ensto_EN%28s-%29.png` |
| V | TMS-Wago_W1(s-).png | `/sites/default/files/2026-03/TMS-Wago_W1%28s-%29.png` |
| B | TMS-Wago_W2(s-).png | `/sites/default/files/2026-03/TMS-Wago_W2%28s-%29.png` |
| W | TMS-Wago_W3(s-).png | `/sites/default/files/2026-03/TMS-Wago_W3%28s-%29.png` |

---

## MAX-E (TME-) — produkt 18

| Kod | Fil | URL |
|-----|-----|-----|
| C | Saknas — använd TM- som fallback | — |
| E | TME-Ensto_EN(s-).png | `/sites/default/files/2026-03/TME-Ensto_EN%28s-%29.png` |
| V | TME-Wago_W1(s-).png | `/sites/default/files/2026-03/TME-Wago_W1%28s-%29.png` |
| B | TME-Wago_W2(s-).png | `/sites/default/files/2026-03/TME-Wago_W2%28s-%29.png` |
| W | TME-Wago_W3(s-).png | `/sites/default/files/2026-03/TME-Wago_W3%28s-%29.png` |

⚠️ TME CableGland saknas — behöver laddas upp eller använd TM-CableGland som fallback.

---

## MAX-ED (produkt 19) — saknar egna bilder

Använd TM- (MAX BASE) som fallback tills egna bilder finns.

---

## OPTI BASE (TO-) — produkt 20, 21

| Kod | Fil | URL |
|-----|-----|-----|
| C | TO_CG(s).png | `/sites/default/files/2026-03/TO_CG%28s%29.png` |
| E | TO_Ensto(s).png | `/sites/default/files/2026-03/TO_Ensto%28s%29.png` |
| V | TO_WagoW1(s).png | `/sites/default/files/2026-03/TO_WagoW1%28s%29.png` |
| B | TO-W2(s).png | `/sites/default/files/2026-03/TO-W2%28s%29.png` |
| W | Saknas | — |

---

## OPTI-S (TOS-) — produkt 21

| Kod | Fil | URL |
|-----|-----|-----|
| C | TOSCG(s-).png | `/sites/default/files/2026-03/TOSCG%28s-%29.png` |
| E | TOSEN(s-).png | `/sites/default/files/2026-03/TOSEN%28s-%29.png` |
| V | TOSW1(s-).png | `/sites/default/files/2026-03/TOSW1%28s-%29.png` |
| B | TOSW2(s-).png | `/sites/default/files/2026-03/TOSW2%28s-%29.png` |
| W | Saknas | — |

---

## OPTI-E (TOE-) — produkt 22

| Kod | Fil | URL |
|-----|-----|-----|
| C | TOSCG(s-).png | `/sites/default/files/2026-03/TOSCG%28s-%29.png` |
| E | TOEEN(s-).png | `/sites/default/files/2026-03/TOEEN%28s-%29.png` |
| V | TOEW1(s-).png | `/sites/default/files/2026-03/TOEW1%28s-%29.png` |
| B | TOEW2(s-).png | `/sites/default/files/2026-03/TOEW2%28s-%29.png` |
| W | TOEW3(s-).png | `/sites/default/files/2026-03/TOEW3%28s-%29.png` |

---

## SROW BASE (TS-) — produkt 24

⚠️ SROW har BARA Cable Gland (C) som endcap — verifierat mot triton-solutions.co 2026-03-23.
Bilderna TS-EN, TS-W1, TS-W2 som listades tidigare existerar inte som produktvarianter.

| Kod | Fil | URL |
|-----|-----|-----|
| C | TS_CG(s).png | `/sites/default/files/2026-03/TS_CG%28s%29.png` |

---

## SROW-S (TSS-) — utgår

⚠️ SROW-S existerar inte som separat produkt — borttagen från kartläggningen.

---

## SROW-E (TSE-) — produkt 25

⚠️ SROW-E har BARA Cable Gland (C) som endcap — verifierat mot triton-solutions.co 2026-03-23.

| Kod | Fil | URL |
|-----|-----|-----|
| C | Saknas — använd TS-default som fallback | — |

---

## Saknade bilder (behöver laddas upp)

| Produkt | Kod | Vad saknas |
|---------|-----|------------|
| MAX-E (TME) | C | CableGland-bild |
| OPTI BASE (TO) | W | Wago Infinity |
| OPTI-S (TOS) | W | Wago Infinity |
| SROW BASE (TS) | W | Wago Infinity |
| SROW-S (TSS) | W | Wago Infinity |
| SROW-E (TSE) | C | CableGland |
| MAX-ED (19) | Alla | Egna bilder saknas — använd TM- |

---

## imageMap-format i schemat

```json
{
  "id": "endcap",
  "skuPart": "middle",
  "imageMap": {
    "C": "/sites/default/files/2026-03/TM-CableGland_CG%28s%29.png",
    "E": "/sites/default/files/2026-03/TM-Ensto%28s.png",
    "V": "/sites/default/files/2026-03/TM-Wago_W1%28s%29.png",
    "B": "/sites/default/files/2026-03/TM-Wago_W2%28s%29.png",
    "W": "/sites/default/files/2026-03/TM-Wago_W3%28s-%29.png"
  },
  "options": [...]
}
```
