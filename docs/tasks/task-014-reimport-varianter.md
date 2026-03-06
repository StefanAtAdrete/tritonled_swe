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
- [ ] SROW: gamla varianter borttagna, nya importerade med korrekt struktur
- [ ] MAX: gamla varianter borttagna, nya importerade med korrekt struktur
- [x] Ingen `attribute_accessories` i någon variant
- [x] Media (bilder) korrekt mappade per variant (OPTI)
- [x] Produktsidorna fungerar med AJAX-variantbyte (OPTI verifierad)

**Godkänt av Stefan**: ⏳ Väntar på SROW och MAX

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

### SROW ⏳ Återstår

**Produkt-ID**: 5  
Behöver:
- `srow-products-v1.csv`
- `srow-variations-v1.csv` med korrekta connection-namn och IP-format

### MAX ⏳ Återstår

**Produkt-ID**: 8  
Behöver:
- `max-products-v1.csv`
- `max-variations-v1.csv` med korrekta connection-namn och IP-format

---

## 4. VERIFY

### OPTI ✅
- [x] 260 varianter importerade
- [x] Store-koppling på plats (commerce_product__stores entity_id=13, stores_target_id=1)
- [x] feeds_item rensat automatiskt (logg: "Cleared feeds_item on 260 entities")
- [x] AJAX-variantbyte fungerar på produktsidan
- [ ] Svenska översättningar (TASK-016 / separat uppgift)

### SROW ⏳
### MAX ⏳

---

## 5. COMPLETION

⏳ Väntar på SROW och MAX

### Lärdomar att dokumentera i /docs/03-solutions/
- `feeds-store-koppling.md` — products-feed måste köras för store-koppling
- `feeds-import-ordning.md` — products alltid före variations
