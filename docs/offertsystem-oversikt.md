# Offertsystem — Arkitekturöversikt

**Status:** Beslutsunderlag (inte aktiv task)
**Skapad:** 2026-05-19
**Senast uppdaterad:** 2026-05-19 (datamodell reviderad — egen sub-entity, Offertgrupp tillagd)
**Master-task:** TASK-043 (när startad)

---

## Syfte

Bygga ett internt offert- och kundlojalitetssystem i Drupal som:

1. Tar emot förfrågningar från elektriker via publika sajten
2. Låter 2+ säljare bygga och iterera offerter (30–40/månad)
3. Genererar pedagogiska PDF-offerter med energikalkyl och styckeslista
4. Låser elektrikern till TritonLED via konto, projekt-historik och case
5. Skapar på sikt naturlig pipeline: avslutat Projekt → publicerat Kundcase

**Inte:** publik prisvisning, e-handel, direktköp. B2B, offertbaserat.

---

## Användare

| Roll | Ser | Kan göra |
|---|---|---|
| Anonym | Produkter, kundcase, tekniska data | Skicka förfrågan via Webform |
| Elektriker (inloggad) | Egna projekt + offerter, PDF-arkiv | Se status, ladda ner PDF, kommentera |
| Säljare | Allt internt | Bygga offerter, iterera, skicka |
| Admin (Stefan) | Allt | Konfig, prislista, påslag |

**Multi-user per företag:** Ej i Fas 1. Delad inloggning manuellt.

---

## Två produktkategorier — viktig distinktion

**A. Konfiguratorprodukter (MAX, OPTI, SROW)**
- 55 000+ varianter via JSON-schema
- Ingen lagrad variant → ingen lagrad pris
- Pris **måste** beräknas av prismotor från komponentpriser
- SKU genereras deterministiskt från konfiguratorvalen

**B. Category C-produkter (floodlight, highbay, linear_led, high_mast, street_area, ex_hazardous, accessories)**
- Klassiska `commerce_product_variation`
- Pris **kan** ligga direkt på variation
- Drupal Commerce standard

**Konsekvens:** Offertraden använder enhetligt prisinterface som internt routar:
- Variation med pris? → använd variation-priset
- Konfigurator-konfig? → kör prismotorn

---

## Datamodell

```
User (Elektriker, Drupal core)
  └─ Företagsprofil (Profile-modul: orgnr, adress, kontakter)
      └─ Projekt (custom content entity, revisionable)
          └─ Offert (custom content entity, revisionable, moderated)
              ├─ Offertgrupp[] (custom content entity, revisionable)
              │     ├─ field_namn, field_color, field_anteckning
              │     ├─ field_weight (ordning), field_is_default ("Övrigt")
              │     └─ + framtida fält via Field UI (takhöjd, IP-krav, brinntid…)
              └─ Offertrad[] (custom content entity, revisionable)
                    ├─ field_grupp → Offertgrupp (required)
                    ├─ field_produkt → commerce_product (autocomplete)
                    ├─ field_konfig_snapshot (JSON, endast Cat A)
                    ├─ field_antal, field_pris_snapshot
                    ├─ field_plats_text, field_notering
                    ├─ field_weight (ordning inom grupp)
                    └─ + framtida fält via Field UI
```

**Platt struktur med grupp-referens** — inte hierarkisk grupp→rad. Drag-och-släpp mellan grupper blir då bara `field_grupp = ny grupp`. Trello/Jira-pattern.

---

## Lagring — egen sub-entitet, inte Paragraphs

**Beslut:** Offertrad och Offertgrupp byggs som **Custom Content Entities**, inte Paragraphs.

**Skäl:**

- Paragraphs är "tung" och inte rätt verktyg för iterativ administration med drag-och-släpp mellan grupper.
- Custom Content Entity är samma infrastruktur som Node/User — standard Drupal, inte specialkonstruktion.
- Field UI ger framtida flexibilitet: nya fält läggs till via admin, inte via kod-deploy.
- Egna routes, access-control, Views-listor, display modes — allt "gratis".
- Bundles förbereder för framtida differentiering (Inomhus/Utomhus/Nödbelysning).
- Entity revisions följer naturligt med när offerten revisioneras A→B→C.

**Performance:** ~10 000 rader/år för 30–40 offerter/månad är *smått* i Drupal-mått. Cache-lagret hanterar utan problem.

**Webform och Views är fel verktyg här:**
- Webform = submission-orienterat, saknar revisions, dåligt för iterativ admin → **används för publik förfrågan (TASK-045) men inte offert-bygge**
- Views = läsverktyg, inte input → **används för admin-listor och översikter**

---

## Default-gruppen "Övrigt"

Auto-skapas när Offert skapas (via `hook_entity_insert` eller event subscriber).

- Markerad med `field_is_default = TRUE` → kan inte raderas
- Döljs i PDF om den är tom
- Drag-och-släpp fungerar likadant som vilken grupp som helst
- Rader utan explicit grupp hamnar här

---

## Drag-och-släpp-strategi (faser)

Tre nivåer behövs:
1. Ordning av grupper inom offert (vertikalt)
2. Ordning av rader inom grupp (vertikalt)
3. Flytta rad mellan grupper (horisontellt eller via dropdown)

**Fas 2 (start):** `field_weight` + pilar upp/ned + "Flytta till grupp"-dropdown
→ Nollarbete, fungerar direkt med Drupal core.

**Fas 2+ (när säljare klagar):** Sortable.js-baserat custom widget i en separat task
→ Riktig Kanban-känsla, mer jobb men matchar UX-förväntan.

---

## Tekniska beslut för alla tre custom-entiteter

| Beslut | Värde | Skäl |
|---|---|---|
| `revisionable` | **TRUE** | Annars går revisioner A/B/C sönder när rad/grupp ändras |
| `translatable` | **FALSE** | Offerter är interna och svenska. Lätt att slå på senare om export öppnas |
| Bundles i Fas 2 | **Nej** | Single bundle räcker. Förbered struktur men aktivera senare vid behov |
| Field UI | **Ja** | Initialfält som configurable fields, inte hårdkodade base fields |

---

## Pristrappa (prismotor)

**Lagrad data (litet):**
- FOB-prislista per komponent (custom config entity, importerad från Excel)
- Påslag per varugrupp (config: M-80Ra: 44%, MP-80Ra: 40%, MS: 35%…)
- EUR/SEK-kurs (state, cron från frankfurter.app)

**Beräknat runtime:**
- FOB(EUR) × kurs × (1 + påslag) = försäljningspris(SEK)

**Snapshot-regel:** Pris fryses på offertraden vid **utskick** av offert. Senare prisändringar påverkar inte historiska offerter. Drafts mellan utskick får uppdaterade priser.

**Pris-fältets ansvar:** lagra. **Prismotor-servicens ansvar:** räkna. Inga fält-kalkyleringar i Webform/computed-fält — allt går via `tritonled.quote_pricer`.

---

## Statusflöde

| Status | Innebörd |
|---|---|
| Utkast | Säljare bygger internt |
| Skickad | PDF + mail ute, väntar |
| Under revidering | Elektriker har kommit med ny info, ny beräkning |
| Accepterad | → blir order |
| Förlorad / Avvisad | Stängd |
| Utgången | Datum passerat (cron sätter automatiskt) |

**Modul:** Content Moderation (Drupal core).

---

## Revisioner A/B/C

- Skapas **vid utskick**, inte vid varje sparad ändring
- Off-26121A → fri redigering → "Skicka uppdaterad" → Off-26121B låses som arkivkopia
- Drupal entity revisions (på Offert, Offertgrupp, Offertrad)
- Numreringsmönster: `Off-YYDDD` + revisionsbokstav (auto per år+dag)

---

## PDF + mail

- **PDF genereras vid utskick** från live-data (Entity Print + Twig)
- **PDF sparas som fil-bilaga** på offerten → arkivkopia för den revisionen
- **Regenereras inte** vid visning (annars är Off-26121A inte längre A)
- **Mail:** Symfony Mailer skickar med PDF som bilaga + länk till "Min sida"

---

## Terminologi

| Term | Var | Vad |
|---|---|---|
| **Kundcase** | Publikt (`/kundcase/...`) | Marknadsföring, referenscase, befintlig Node-typ |
| **Projekt** | Internt (custom entity) | Offert-container för pågående arbete |

**Pipeline-bonus (senare):** Avslutat Projekt → "Skapa Kundcase av detta" → utkast med produktlista, energikalkyl, plats. Marknad redigerar och publicerar.

---

## Faser (A rullar parallellt manuellt)

| Fas | Innehåll | Mål |
|---|---|---|
| 1 | Kundkonton + Webform-förfrågan | Få in förfrågningar via sajten |
| 2 | Offert/grupp/rad-entiteter + admin-UI | Säljare kan bygga offerter i Drupal (manuell pris) |
| 3 | Prismotor (Kategori B först, sen A) | Excel pensioneras gradvis |
| 4 | Projekt + revisioner + elektriker-portal | "Mina projekt" live |
| 5 | Energikalkyl publikt + i offert | Säljargument synligt |

---

## Task-träd

```
TASK-043 (parent): Offertsystem — master
├─ TASK-044: Kundkonton + företagsprofil
├─ TASK-045: Webform "Förfrågan" → kopplad till user
├─ TASK-046: Offert-entity (datamodell + grund-CRUD + numrerings-service)
├─ TASK-047: Offertgrupp + Offertrad-entiteter (sub-entities + IEF + default-grupp)
├─ TASK-048: Prismotor — del 1: Category C (variation-pricing)
├─ TASK-049: Prismotor — del 2: Konfigurator (FOB + påslag + EUR/SEK + custom widget)
├─ TASK-050: PDF-generering (Entity Print + Twig)
├─ TASK-051: Mail-utskick (Symfony Mailer + token)
├─ TASK-052: Projekt-entity (container för flera offerter)
├─ TASK-053: Content Moderation workflow (status + revisioner A/B/C)
├─ TASK-054: Elektriker-portal "Mina projekt" (Views + access)
└─ TASK-055: Energikalkyl (service + publik visning + i offert)
```

**Eventuellt senare:**
- TASK-056: Sortable.js drag-och-släpp widget för offertrader/grupper
- TASK-057: Grupp-mallar (återanvändbara grupp-templates)
- TASK-058: Projekt → Kundcase auto-generering

---

## Claude Code /goal-lämplighet per task

| Task | /goal-lämplig | Kommentar |
|---|---|---|
| TASK-044 Kundkonton | ✅ Ja | Standard Drupal |
| TASK-045 Webform | ✅ Ja | Standard |
| TASK-046 Offert-entity | ⚠️ Efter fältval | Datamodell ska godkännas först |
| TASK-047 Grupp+Rad | ⚠️ Efter 046 | Custom entities + default-grupp-logik |
| TASK-048 Prismotor Cat C | ⚠️ Med spec | Affärslogik kräver tester |
| TASK-049 Prismotor Konfig | ❌ Nej | För komplex, måste valideras stegvis. Custom widget för konfig-val |
| TASK-050 PDF | ⚠️ Mockup först | Design måste vara klar |
| TASK-051 Mail | ✅ Ja | Standard |
| TASK-052 Projekt-entity | ✅ Ja | Samma pattern som 046 |
| TASK-053 Workflow | ✅ Ja | Konfiguration |
| TASK-054 Portal | ❌ Nej | UX-känsligt, kräver beslut |
| TASK-055 Energikalkyl | ⚠️ Formel först | Enkel logik när formel är spikad |

**Tumregel:** Datamodell + admin-UI = /goal-vänligt. Affärslogik + kundvänd UX = Stefan i loop.

---

## Kontrib-moduler (förlita på, ingen kod)

- **Profile** — företagsuppgifter på user
- **Inline Entity Form** — embedded redigering av Offertgrupp/Offertrad i Offert-formuläret
- **Entity Print** — PDF
- **Content Moderation** (core) — offertstatus + revisioner
- **Feeds** — FOB-priser från Excel
- **Symfony Mailer** — mail-utskick
- **Token** — länkar i mail

*Ej längre planerat: Paragraphs (utbytt mot egna sub-entiteter).*

---

## Custom som måste byggas

- **Custom modul** `tritonled_quote` — innehåller allt nedan:
  - Offert (content entity, revisionable, moderated) — TASK-046
  - Offertgrupp (content entity, revisionable) — TASK-047
  - Offertrad (content entity, revisionable) — TASK-047
  - Default-grupp auto-create-logik — TASK-047
  - Numrerings-service Off-YYDDD + revisionsbokstav — TASK-046
- **Prismotor-service** `tritonled.quote_pricer` — TASK-048+049
- **Konfigurator-val-widget** (Cat A) — TASK-049
- **PDF Twig-template** — TASK-050
- **Energikalkyl-service** — TASK-055
- Senare: Sortable.js drag-och-släpp-widget — TASK-056

---

## Initialfält per entitet

### Offert
- `field_offert_nummer` (string, auto: Off-YYDDD)
- `field_projekt` (entity reference → Projekt)
- `field_kund` (entity reference → User)
- `field_giltig_tom` (datetime)
- `field_valuta_kurs_snapshot` (decimal, fryses vid utskick)
- `field_total_snapshot` (decimal, beräknas)
- `field_offertgrupper` (entity reference, multiple → Offertgrupp)
- moderation_state (Content Moderation)

### Offertgrupp
- `field_namn` (text, required)
- `field_color` (color field, default #cccccc)
- `field_anteckning` (long text)
- `field_weight` (integer)
- `field_is_default` (boolean)
- `field_parent_offert` (entity reference → Offert)

### Offertrad
- `field_grupp` (entity reference → Offertgrupp, required)
- `field_produkt` (entity reference → commerce_product, autocomplete)
- `field_konfig_snapshot` (long text/JSON, endast Cat A)
- `field_antal` (integer, required)
- `field_pris_snapshot` (decimal)
- `field_plats_text` (text)
- `field_notering` (long text)
- `field_weight` (integer)
- `field_parent_offert` (entity reference → Offert)

**Alla fält byggs via Field UI** (inte hårdkodade base fields), så att utökningar inte kräver kod-deploy.

---

## Öppna frågor inför TASK-044

1. Företagsprofil — räcker det med fält direkt på User, eller egen Profile-entity?
2. Webform-koppling — ska anonym förfrågan **också** gå igenom och kopplas till nyskapad user vid registrering?
3. Datablad — separat fråga, ej beslutad. Påverkar TASK-050.
4. Energikalkyl-formel — vilka antaganden om brinntid och elpris? Per produkt eller per offert?
5. Hostinger VPS — räcker resurserna för Entity Print (Chromium/wkhtmltopdf)? Bör testas i Fas 2.

---

## Källor

- Konversation 2026-05-19 (Stefan + Claude)
- `TritonLED_Prisverktyg_250515.xlsx` (FOB + påslag-modell)
- Befintlig konfigurator: `web/modules/custom/tritonled_configurator/`
- JSON-schemas: `/docs/product-schemas/{max,opti,srow}-configurator-schemas.json`

---

## Beslut-ändringslogg

- **2026-05-19** Initial version
- **2026-05-19** Offertrad bytt från Paragraphs till egen Custom Content Entity
- **2026-05-19** Offertgrupp tillagd som mellannivå mellan Offert och Offertrad
- **2026-05-19** Drag-och-släpp-strategi: field_weight + pilar i Fas 2, Sortable.js i Fas 2+
- **2026-05-19** Tekniska beslut låsta: revisionable=TRUE, translatable=FALSE för alla tre entiteter
- **2026-05-19** Default-grupp "Övrigt" auto-skapas vid offertskapande
