# Product Configurator Schemas

Hämtade från triton-solutions.co via browser automation (Next.js RSC payload).
Datum: 2026-03-17

## Status

| Fil | Serie | Modeller | Status |
|-----|-------|----------|--------|
| `max-configurator-schemas.json` | MAX | max-base, max-pro, max-s, max-e, max-ed | ✅ Komplett |
| `opti-configurator-schemas.json` | OPTI | opti-base, opti-s, opti-e, opti-ed | ✅ Komplett |
| `srow-configurator-schemas.json` | SROW | srow-base, srow-e, srow-ed | ✅ Komplett |

**Totalt: 12 modeller, 3 serier — alla hämtade.**

## SKU-prefix per modell

| Modell | Prefix |
|--------|--------|
| MAX BASE | M- |
| MAX PRO | MP- |
| MAX-S (Sensor) | MS- |
| MAX-E (Emergency) | ME- |
| MAX-ED (Emergency+Daylight) | MED- |
| OPTI BASE | O- |
| OPTI-S (Sensor) | OS- |
| OPTI-E (Emergency) | OE- |
| OPTI-ED (Emergency+Daylight) | OED- |
| SROW BASE | S- |
| SROW-E (Emergency) | SE- |
| SROW-ED (Emergency+Daylight) | SED- |

## KRITISKA skillnader mellan serier

### SROW är fundamentalt annorlunda än MAX/OPTI

| Attribut | MAX/OPTI | SROW |
|----------|----------|------|
| Chips | Nej | ✅ Ja (48 eller 72 chips/modul) |
| IP-klass | Fast per modell | ✅ Valbar (IP54 eller IP65) |
| CRI | Ra80/Ra90 | ❌ Ingen CRI-väljare |
| Ändstycke | Flera val (CG, EN, W1, W2, W3) | Bara Cable Gland |
| Watt beror på | CRI + Längd | Chips + Längd |
| Optik | 30°/60°/80-90°/110° | 60°/90°/145° Batwing |
| Sensor | Ja (MAX-S, OPTI-S) | Nej (ingen SROW-S) |

### Optik-skillnader per serie

| Kod | MAX | OPTI | SROW |
|-----|-----|------|------|
| N | 30° HighRack | 30° HighRack | ❌ |
| M | 60° Medium | 60° Medium | 60° Medium |
| W/V | 90° Wide | 80° Wide (kod V) | 90° Wide (kod V) |
| D | 110° Diffuse | 110° Diffuse | ❌ |
| S | Retail Symmetric1 | Retail Symmetric1 | ❌ |
| B | ❌ | ❌ | 145° Batwing |

### Längder per serie

| Serie | Modell | Längder |
|-------|--------|---------|
| MAX BASE | Standard | 0,5 / 1,0 / 1,5 / 2,0m |
| MAX-S | Sensor | 0,7 / 1,2 / 1,7 / 2,2m |
| MAX-E/ED | Emergency | 0,7m |
| MAX PRO | High-power | 1,5 / 2,0m |
| OPTI BASE | Standard | 0,6 / 1,2 / 1,7 / 2,0m |
| OPTI-S | Sensor | 0,8 / 1,4 / 1,9 / 2,2m |
| OPTI-E/ED | Emergency | 0,8m |
| SROW BASE | Standard | 0,6 / 1,2 / 1,8m |
| SROW-E/ED | Emergency | 0,8m |

## Gemensamma attribut (alla serier)
- length, driver, kelvin, watt, optic, color

## Unika attribut per serie
- **MAX/OPTI:** cri (Ra80/Ra90), endcap (flera val), sensor (MAX-S/OPTI-S)
- **SROW:** chips (48/72), ipClass (IP54/IP65)

## dependsOn-logik

### Ändstycke beror på drivdon (MAX/OPTI)
- W1 / Wago Grey: bara On/Off
- Wago Dali Blue: bara DALI2/B2LD2
- Wago Infinity: On/Off + DALI2/B2LD2

### Watt beror på:
- MAX/OPTI: CRI + Längd
- SROW: Chips + Längd

### sensor beror på drivdon (MAX-S/OPTI-S)
- MultiSensor/Connect ME: bara B2LD2
- OnOff sensorer: bara On/Off

## sensor-semantics
- `sensor_type`: MAX-S, OPTI-S (MultiSensor XS/XL/XXL, Connect ME, OnOff)
- `battery_duration`: MAX-E, MAX-ED, OPTI-E, OPTI-ED (1H/3H Battery)

## Konsekvens för Drupal Commerce Product Type

SROW behöver antingen:
1. **Separat Product Type** med `chips` och `ipClass` attribut istället för `cri`
2. **Samma Product Type** med alla möjliga attribut, och SROW använder bara sin delmängd

Rekommendation: Separata Product Types — SROW är för annorlunda för att dela typ med MAX/OPTI.

## Användning

Dessa JSON-filer används som:
1. **Referens för CSV-import** — vilka attributvärden och kombinationer som är giltiga
2. **TASK-015 konfigurator** — `field_configurator_schema` på Commerce Products
3. **Arkitekturplanering** — vilka Commerce-attribut som behövs
