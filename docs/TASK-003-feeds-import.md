# TASK-003: Commerce Feeds CSV Import

## Status: KLAR (basnivå)

### Vad som fungerar
- Products feed (ID 1): 3 produkter importerade (Triton MAX, OPTI, SROW)
- Variations feed (ID 3): 9 varianter importerade med korrekt pris (SEK)
- Attributreferenser fungerar (watt, cct, ip_rating, beam_angle, color, length)
- `skip_validation: true` krävs för att bypass:a store reference-validering

### Kvarstående varningar (ej blockerande)
- `voltage`-attributet matchas men `220-240` behöver finnas som attributvärde
- Attributreferenser som saknas skapas inte automatiskt (autocreate: false)
- `product_id` mappning mot produkter via title är inte verifierad

### Feed-konfiguration
- Fetcher: `upload` med absolut sökväg `/var/www/html/data/import/products-example.csv`
- Parser: `csv`, `line_limit: 100`
- Processor: `entity:commerce_product_variation`
- `langcode: en` (KRITISKT - `und` ger fel)
- Price: `number: price` + `currency_code: SEK` (hårdkodad, inte från CSV)

---

## Lärdomar

### 1. DDEV/Docker filsynk på macOS
**Problem:** macOS Docker Desktop cacbar volymerna — ändringar i Mac-filer syns inte omedelbart i containern.  
**Lösning:** Skriv alltid filer DIREKT i containern via `ddev exec python3 -c "..."` för CSV-manipulation. Använd aldrig sed/touch — de triggar inte sync.  
**Verifiering:** Kör alltid `ddev exec python3 -c "import csv; ..."` för att bekräfta att containern ser rätt data.

### 2. Feeds Upload Fetcher
- Kräver absolut sökväg ELLER managed file entity
- `public://feeds/path.csv` fungerar INTE programmatiskt utan korrekt file entity
- Absolut sökväg `/var/www/html/...` fungerar bäst för lokal dev
- Feed instances skapade programmatiskt saknar fetcher-state som UI-skapade har

### 3. Commerce Price Target (commerce_feeds)
- Exponerar ENDAST `number` som property
- `currency_code` är alltid hårdkodad i settings, ALDRIG från CSV-kolumn
- Mappning: `map: {number: price}` + `settings: {currency_code: SEK}`
- INTE `map: {value: price}` (det ger fel entity som price-nummer)

### 4. Commerce Attributvärden
- Är INTE taxonomy terms — de är `commerce_product_attribute_value` entities
- Feeds söker via `name`-fältet med `reference_by: name`
- Värdena måste existera INNAN import (autocreate: false rekommenderas)
- CSV-värden måste matcha exakt med `name`-fältet (case-sensitive?)
- Enhetssuffix ska INTE vara i attributvärdet — hantera i display-lagret

### 5. Language/langcode
- `langcode: und` i mappningar ger "Invalid translation language"-fel
- Sätt alltid `language: en` i alla mapping settings
- Processorns `langcode: en` är separat från mappningarnas language-settings

### 6. CSV-struktur
- Håll CSV-kolumner strikt separerade — `|`-separerade features-fält kan förskjuta kolumner
- Verifiera alltid CSV med DictReader-output innan import
- Producera CSV-filer med Filesystem-verktyg på Mac, synka sedan till container

---

## Nästa steg för produktimport
1. Verifiera att varianter är länkade till produkter (product_id mappning)
2. Rensa gamla test-varianter (COMET-*, TritonLED Sweden AB)
3. Testa products feed med uppdaterad CSV
4. Lägg till `autocreate: true` för attribut ELLER skapa attributvärden i förväg
5. Verifiera attributreferenser på varianter i UI

---

## CSV-filens plats
- Mac: `/Users/steffes/Projekt/tritonled/data/import/products-example.csv`
- Container: `/var/www/html/data/import/products-example.csv`
- OBS: Dessa är INTE alltid synkade på macOS — verifiera alltid i container
