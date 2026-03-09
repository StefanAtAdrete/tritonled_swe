# Task 014: Reimportera OPTI, SROW, MAX med korrekta CSV:er

**Created**: 2026-03-05  
**Status**: In Progress — OPTI klar, SROW och MAX återstår
**Last Updated**: 2026-03-06
**Related Tasks**: TASK-012, TASK-013, TASK-015

---

## 1. DEFINE

### Mål
Reimportera alla varianter för OPTI, SROW och MAX med korrekta CSV:er baserade på beslutad produktstruktur. Accessories-kolumnen tas bort, connection hanteras via `attribute_connection`.

### Syfte
Befintliga varianter är importerade med felaktig attributstruktur (accessories istället för connection). Nya CSV:er ska spegla den beslutade strukturen för alla Triton-produkter.

### Standardattribut per variant (gäller alla Triton-produkter)
- Längd (mm, heltal)
- Watt (heltal)
- CCT (heltal: 3000, 4000, 5000)
- CRI (Ra80, Ra90)
- IP-rating (heltal: 20, 43, 54)
- Connection — fullständiga namn med förkortning:
  - `Cable Gland (CG)`
  - `Wago (W1)`
  - `Wago DALI (W2)`
  - `Wago Black Infinity (W3)`
  - `EnstoNet (EN)`
- Model (Standard, Sensor, Emergency)

### Acceptanskriterier
- [x] OPTI: gamla varianter borttagna, nya importerade med korrekt struktur
- [x] SROW: gamla varianter borttagna, nya importerade med korrekt struktur
- [x] MAX: gamla varianter borttagna, nya importerade med korrekt struktur
- [x] Ingen `attribute_accessories` i någon variant
- [x] Media (bilder) korrekt mappade per variant (OPTI)
- [x] Produktsidorna fungerar med AJAX-variantbyte (OPTI verifierad)

**Godkänt av Stefan**: SROW och MAX klara. Återstår: TASK-010b (AJAX bildbytet) för alla tre serier.

---

## 2. PLAN

### Vald lösning
**Approach**: Feeds CSV-import — SEPARATA feeds för produkt och variationer  
**Specifik lösning**:
1. Kör produktfeed med products-CSV → sätter store-koppling
2. Kör variationsfeed med variations-CSV → importerar varianter
3. `tritonled_compat` FeedsImportSubscriber rensar feeds_item automatiskt
4. Verifiera AJAX-variantbyte

### Viktiga Feeds-regler (lärdomar från TASK-012 och TASK-014)

#### Importordning — KRITISKT
1. **Products-feed ALLTID FÖRST** → skapar produkten med store-koppling
2. **Variations-feed SEDAN** → importerar variationerna kopplade till produkten

Om produkten skapades manuellt (utan products-feed) saknar den store-koppling.
Symptom: `Exception: The given entity is not assigned to any store` (500-fel vid AJAX).

#### Store-koppling
- Mappas via `reference_by: name` med store-namnet `TritonLED Sweden AB`
- Om produkten saknar store-koppling: kör products-feeden — den uppdaterar produkten korrekt
- Verifiera: `SELECT entity_id, stores_target_id FROM commerce_product__stores WHERE entity_id = X;`

#### feeds_item-rensning — AUTOMATISK
- `tritonled_compat` FeedsImportSubscriber lyssnar på `feeds.import_finished`
- Rensar automatiskt `feeds_item` på alla variationer importerade av feeden
- Verifieras via: `ddev drush watchdog:show --count=10 --type=tritonled_compat`
- Tabellnamn (ej `feeds_item`): `commerce_product_variation__feeds_item`

#### Media-mappning
- Använd `reference_by: mid` + `autocreate: 0` TILLSAMMANS
- Media-ID:n för OPTI (verifierade 2026-03-06):
  - 26: TO_CG(s).png — Standard/Cable Gland (CG)
  - 27: TO_Ensto(s).png — Standard/EnstoNet (EN)
  - 28: TO_WagoW1(s).png — Standard/Wago (W1)
  - 29: TO-W2(s).png — Standard/Wago DALI (W2)
  - 30: TOEEN(s-).png — Emergency/EnstoNet (EN)
  - 31: TOEW1(s-).png — Emergency/Wago (W1)
  - 32: TOEW2(s-).png — Emergency/Wago DALI (W2)
  - 33: TOEW3(s-).png — Emergency/Wago Black Infinity (W3)
  - 34: TOSCG(s-).png — Sensor/Cable Gland (CG)
  - 35: TOSEN(s-).png — Sensor/EnstoNet (EN)
  - 36: TOSW1(s-).png — Sensor/Wago (W1)
  - 37: TOSW2(s-).png — Sensor/Wago DALI (W2)
  - 38: TOSW3(s-).png — Sensor/Wago Black Infinity (W3)

#### CSV parser
- `line_limit: 100` (inte 0 — orsakar seek-fel)
- Language: `en` i alla mappningar (inte `und`)

#### Flerspråkighet
- Products-CSV: en rad på engelska som bas
- Svenska översättningar görs via Drupal GUI (Content → Translate)
- Variationer importeras alltid på engelska

**Godkänt av Stefan**: ✅

---

## 3. IMPLEMENT

### OPTI ✅ Klar (2026-03-06)

**Produkt-ID**: 13  
**Antal varianter**: 260

**Filer**:
- `private/feeds/opti-products-v1.csv` — produktfeed (1 rad, engelska)
- `private/feeds/opti-variations-v2.csv` — variationsfeed (260 rader)

**Steg genomförda**:
1. Gamla variationer borttagna (Drupal admin)
2. `commerce_product_variation__feeds_item` rensat manuellt (feeds_item-tabell heter detta, inte `feeds_item`)
3. `opti-variations-v2.csv` skapad med korrekta connection-namn och IP-format
4. Products-feed kördes med `opti-products-v1.csv` → store-koppling satt
5. Variations-feed kördes med `opti-variations-v2.csv` → 260 varianter importerade
6. `tritonled_compat` rensade feeds_item automatiskt (260 entiteter)
7. AJAX-variantbyte verifierat — fungerar

**Ändringar vs gamla CSV**:
- `CG` → `Cable Gland (CG)`, ip_rating `IP43` → `43`
- `EN` → `EnstoNet (EN)`, ip_rating `IP20` → `20`
- `W1` → `Wago (W1)`, ip_rating `IP20` → `20`
- `W2` → `Wago DALI (W2)`, ip_rating `IP20` → `20`
- `W3` → `Wago Black Infinity (W3)`, ip_rating `IP20` → `20`

### SROW ✅ Klar (2026-03-07)

**Produkt-ID**: 5  
**Antal varianter**: 120

**Modeller**: Standard, Sensor, Emergency, Emergency Daylight

**Filer**:
- `private/feeds/srow-products-v1.csv`
- `private/feeds/srow-variations-v1.csv`
- `config/sync/feeds.feed_type.tritonled_srow_products.yml`
- `config/sync/feeds.feed_type.tritonled_srow_variations.yml`

**Variantstruktur**:
- Standard (TS): 600/1200/1740mm × 3/3/5 watt × 5 connections = 55 varianter
- Sensor (SS): 800/1400/1900mm × 3/3/3 watt × 5 connections = 45 varianter
- Emergency (SE): 800mm × 3W × 5 connections = 5 varianter
- Emergency Daylight: 800mm × 16/23/33W × 5 connections = 15 varianter

**Media entity-ID:n**:
- Standard CG=56, EN=57, W1=58, W2=59, W3=55
- Sensor CG=63, EN=64, W1=65, W2=66, W3=55
- Emergency/ED CG=55, EN=55, W1=60, W2=61, W3=62

**Viktigt**: Bildbytet på frontend är TASK-010b (AJAX) — inte ett importproblem. Bilder verifierade via php:eval.

### MAX ✅ Klar (2026-03-06)

**Produkt-ID**: 8  
**Antal varianter**: 150

**Modeller**: Standard, Sensor, Emergency, Emergency Daylight

**Filer**:
- `private/feeds/max-products-v1.csv` — produktfeed (1 rad, engelska)
- `private/feeds/max-variations-v1.csv` — variationsfeed (150 rader)
- `config/sync/feeds.feed_type.tritonled_max_products.yml`
- `config/sync/feeds.feed_type.tritonled_max_variations.yml`

**Variantstruktur**:
- Standard: 4 längder (500/1000/1500/2000mm) × 5 connections × 3-5 watt = 75 varianter
- Sensor (TMS): 3 längder (1200/1700/2200mm) × 5 connections × 3-4 watt = 55 varianter
- Emergency (TME): 700mm × 5 connections × 1 watt (3W) = 5 varianter
- Emergency Daylight: 700mm × 5 connections × 3 watt (14/20/29W) = 15 varianter

**Media entity-ID:n** (entity_id, INTE fid):
- Standard CG: 41, EN: 42, W1: 43, W2: 44, W3: 45
- Sensor CG: 50, EN: 51, W1: 52, W2: 53, W3: 54
- Emergency/Emergency Daylight CG: 55 (Missing image), EN: 46, W1: 47, W2: 48, W3: 49

**Steg genomförda**:
1. Variantstruktur fastställd från PDF-datasheets
2. SKU-format: `TM{modell}{längdkod}{watt}K0-{connection}` (följer OPTI-mönster)
3. `Emergency Daylight` attributvärde skapat via drush eval
4. max-products-v1.csv skapad och importerad → store-koppling satt
5. max-variations-v1.csv skapad med korrekta media entity-ID:n (41-55)
6. Variations-feed kördes → 150 varianter importerade
7. Bilder verifierade i admin-UI

**Viktiga lärdomar**:
- Media entity-ID (mid) ≠ file-ID (fid) — måste kartlägga via `media__field_media_image`
- Feeds cached-fil byter namn (`_0`, `_0_0` etc) — kopiera rätt fil med `ddev exec cp`
- `Emergency Daylight` måste skapas som attributvärde INNAN import (`autocreate: false`)
- Nya feed type-instanser kräver `ddev drush cim --partial -y` + `ddev drush cr` för att aktiveras

---

## 4. VERIFY

### OPTI ✅
- [x] 260 varianter importerade
- [x] Store-koppling på plats (commerce_product__stores entity_id=13, stores_target_id=1)
- [x] feeds_item rensat automatiskt (logg: "Cleared feeds_item on 260 entities")
- [x] AJAX-variantbyte fungerar på produktsidan
- [ ] Svenska översättningar (TASK-016 / separat uppgift)

### SROW ✅
- [x] 120 varianter importerade
- [x] Bilder korrekt kopplade (verifierat via php:eval)
- [x] Required-fält fixade (accessories, beam_angle etc avmarkerade)
- [ ] AJAX-variantbyte (TASK-010b)
- [ ] Svenska översättningar
### MAX ✅
- [x] 150 varianter importerade
- [x] Store-koppling på plats (product_id=8)
- [x] Bilder verifierade i admin-UI
- [ ] feeds_item rensning verifierad
- [ ] AJAX-variantbyte verifierat på produktsidan
- [ ] Svenska översättningar (separat uppgift)

---

## 5. COMPLETION

⏳ Väntar på SROW och MAX

### Lärdomar att dokumentera i /docs/03-solutions/
- `feeds-store-koppling.md` — products-feed måste köras för store-koppling
- `feeds-import-ordning.md` — products alltid före variations
