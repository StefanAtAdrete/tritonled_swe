# TASK-039 — Startsida: Tre ikoner-block med länk till produktsida

**Status: TODO**
**Prioritet: HÖG — Akut**

---

## Beskrivning
Tre ikoner-block på startsidan som lyfter fram TritonLEDs viktigaste säljargument.
Varje block länkar med ankarlänk till rätt sektion på:
`https://preview.affarsfabriken.se/node/12`

Målgrupp: **Elektriker** (installation, teknik) och **Inköpare** (ekonomi, livslängd, risk).

---

## Budskap — vad ska lyftas fram
- Produkterna är **billigare över tid** än konkurrenter (håller länge, utbytbara delar)
- **Modulärt system** — om 10 år kan delar bytas utan att det ser konstigt ut
- **Tekniken kan uppdateras** — drivdon, kopplingar, driver byts separat
- **Europeisk produktion** — hög kvalitet på chips och komponenter
- **Enkel installation** — Wago-koppling, verktygssnål kabeldragning

---

## De tre blocken

### Block 1 — Lägre totalkostnad
**Ikon**: Kalkylator eller mynt (kostnad/ekonomi)
**Rubrik**: Lönsam investering
**Text**: TritonLED kostar mindre över tid. Tack vare lång livslängd, europeisk produktion och utbytbara komponenter slipper du byta hela installationen — bara det som behöver uppgraderas.
**Länk**: `https://preview.affarsfabriken.se/node/12#kostnad`
**Ankaret på sidan**: Sektion 3 — "Håller över tid och kan kompletteras"

---

### Block 2 — Modulärt & framtidssäkert
**Ikon**: Pussel eller moduler
**Rubrik**: Modulärt system
**Text**: Välj kopplingstyp, driver och effekt separat. Om 10 år kan delar bytas ut utan att armaturen ser omodern ut. Systemet växer med din anläggning.
**Länk**: `https://preview.affarsfabriken.se/node/12#modulart`
**Ankaret på sidan**: Sektion 2 — "Modulära armaturer med utbytbara delar"

---

### Block 3 — Snabb installation
**Ikon**: Blixt eller skiftnyckel
**Rubrik**: Enkel att installera
**Text**: Wago-koppling (W1/W2/W3) gör kabeldragningen snabb och verktygssnål. Monteringsfäste och ram väljs direkt i konfigurationen — levereras klart att montera.
**Länk**: `https://preview.affarsfabriken.se/node/12#installation`
**Ankaret på sidan**: Sektion 4 — "Enkel och snabb installation"

---

## Ankarlänkar — måste läggas till på affarsfabriken.se/node/12

Lägg till id-attribut på respektive sektion i node/12-innehållet:

```html
<h2 id="modulart">Modulära armaturer med utbytbara delar</h2>
<h2 id="kostnad">Håller över tid och kan kompletteras</h2>
<h2 id="installation">Enkel och snabb installation</h2>
```

Via Layout Builder eller CKEditor på affarsfabriken.se.

---

## Teknisk implementation — tritonled.se

### Alternativ A: Custom block content (rekommenderas)
- Skapa tre block content-entiteter (typ: Basic block eller custom)
- Fält: ikon (SVG eller Bootstrap Icons), rubrik, brödtext, länk-URL
- Placera i rad på startsidan via Layout Builder

### Alternativ B: Hårdkodade block i Layout Builder
- Tre separata Text-block med HTML/Bootstrap
- Enklare men svårare att administrera

**Rekommendation: Alternativ A** om ett block content-typ med ikon-fält redan finns,
annars **Alternativ B** som snabbaste vägen.

---

## Befintlig block content-typ?
Kontrollera om `block_content.type.basic` stöder ikon-fält eller om en ny typ behövs.

---

## Acceptanskriterier
- Tre block visas på startsidan i en rad (col-md-4 vardera)
- Varje block har: ikon, rubrik, brödtext, länk-knapp
- Klick på länk öppnar rätt sektion på affarsfabriken.se/node/12
- Fungerar på mobil (staplas vertikalt)
- SV och EN-versioner av texterna

---

## Texter — EN-versioner

### Block 1 — Profitable Investment
Long-term savings through durability. European-made components and replaceable parts mean you upgrade only what's needed — not the entire installation.

### Block 2 — Modular System
Choose connection type, driver and wattage separately. In 10 years, parts can be replaced without the fixture looking outdated. The system scales with your facility.

### Block 3 — Easy to Install
Wago connectors (W1/W2/W3) make cable routing fast and tool-light. Mounting bracket and frame are configured at the time of order — delivered ready to mount.
