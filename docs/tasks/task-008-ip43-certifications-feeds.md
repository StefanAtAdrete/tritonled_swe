# Task 008: Produktkatalog — IP43 varianter, Certifieringar & Feeds-automation

**Created**: 2026-02-27  
**Status**: In Progress  
**Last Updated**: 2026-02-27  
**Related Tasks**: TASK-003

---

## 1. DEFINE

### Mål
Utöka MAX-produktkatalogen med IP43 Cable Gland-varianter, skapa taxonomy för certifieringar med logotypstöd, och automatisera feeds_item-rensning efter import.

### Syfte
- IP43-varianter behövs för att täcka MAX TM-serien med kabelgenomföring
- Certifieringar som taxonomy möjliggör filtrering och visuell presentation med logotyper
- Automatisk feeds_item-rensning krävs för att undvika AJAX 500-fel i produktion

### Acceptanskriterier
- [x] 8 IP43 CG-varianter importerade till TRITON-MAX
- [x] feeds_item rensas automatiskt efter varje Feeds-import
- [x] Taxonomy vocabulary `certifications` skapad med bildfält
- [x] 6 certifieringstermer skapade (CE, RoHS, ENEC, B2L ready, Dimmable, Flicker Free)
- [x] `field_certifications` på commerce_product (default)
- [ ] `field_certifications` mappad i Feeds-importern
- [ ] CSV uppdaterad med certifieringskolumn
- [ ] Certifieringslogotyper uppladdade per term

**Godkänt av Stefan**: ✅ Godkänd

---

## 2. PLAN

### Beslutsträd
**Fil**: `/docs/DRUPAL-DECISION-TREE.md`  
**Steg**: Config → Contrib (Feeds) → Custom code (event subscriber)

### Vald lösning
**Approach**: Config + Contrib Module + Custom Code  
**Specifik lösning**:
- CSV utökas med IP43-rader
- Feeds-import via separata feeds per produktserie
- Event subscriber i `tritonled_compat` för feeds_item-rensning
- Taxonomy med Media-bildfält för certifieringslogotyper

### Motivering
Feeds hanterar CSV-import. Taxonomy är Drupal-standard för klassificering. Event subscriber är minsta möjliga custom kod för automatisering.

**Godkänt av Stefan**: ✅ Godkänd

---

## 3. IMPLEMENT

### Steg

1. **IP43 varianter i CSV**
   - 8 rader tillagda i `max-ip20.csv` (4 längder × 2 drivers, endast 4K)
   - SKU-suffix `-IP43` för unika nycklar
   - IP-attributvärde `43` tillagt i Drupal
   - Git commit: `[tritonled_compat] Add FeedsImportSubscriber...`

2. **Feeds private-mapp synk**
   - Problem: Feeds använder cached kopia i `/var/www/html/private/feeds/`
   - Lösning: `ddev exec cp` för att synka uppdaterad CSV till containern
   - Lärdomar: Feeds lagrar uppladdade filer i private-mapp, inte direkt från projektet

3. **FeedsImportSubscriber i tritonled_compat**
   - Första version: laddade alla entiteter (ej skalbar)
   - Andra version: filtrerar på `feeds_item.target_id` per feed, processar i chunks om 50
   - services.yml skapad
   - Git commits: 2 commits

4. **Taxonomy certifications**
   - Vocabulary skapad via Drush php:eval
   - Bildfält `field_certification_logo` (Media) tillagt via admin UI
   - Entity reference `field_certifications` på commerce_product default
   - 6 termer skapade via Drush: CE, RoHS, ENEC, B2L ready, Dimmable, Flicker Free
   - Config exporterad efter varje steg

### Hinder/Problem
- Feeds hash-cache: måste rensas manuellt (`UPDATE ... SET feeds_item_hash = ''`) när CSV uppdateras utanför Feeds UI
- SKU-kollision: IP43-varianter hade samma SKU som befintliga IP20 CG-varianter — löst med `-IP43` suffix
- Feeds private-mapp: uppladdade filer cachas i containern, inte synkade automatiskt från projektet

---

## 4. VERIFY

### Testresultat
**Testad**: 2026-02-27  
**Testmiljö**: DDEV lokal

- [x] 8 IP43-varianter i databasen (TM-05-29-4K-CG-AP-IP43 etc.)
- [x] IP43 visas i attributväljaren när rätt kombination väljs
- [x] FeedsImportSubscriber loggar korrekt antal rensade entiteter
- [x] Chunks om 50 — skalbar för 15 000+ varianter
- [x] Taxonomy terms skapade
- [ ] Certifieringslogotyper uppladdade
- [ ] CSV-mappning för certifieringar

---

## 5. COMPLETION

### Status: 🔄 In Progress

### Lärdomar
- Feeds cachas i private-mapp i containern — synka med `ddev exec cp` vid uppdatering
- SKU måste vara globalt unik — IP-klass i SKU är nödvändigt
- `loadMultiple()` utan filter är ej skalbart — använd alltid entity query med filter + chunk-processing
- Taxonomy terms med Media-fält fungerar direkt i Drupal utan custom kod
- Config-entiteter (vocabulary) skapas alltid via Drush/UI, aldrig manuell YAML

### Nästa steg
1. Lägg till `field_certifications` kolumn i CSV med `|`-separator
2. Konfigurera Feeds-mappning för certifieringar (term-namn som target)
3. Ladda upp certifieringslogotyper per term
4. Skapa separata Feeds-importers per produktserie (MAX, OPTI, SROW)
5. Testa cron-baserad import
