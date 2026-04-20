# Aktuell Task

**Senast uppdaterad**: 2026-04-20

---

## ⏭️ Nästa session startar här: TASK-031 fortsätter

**Läs**: `docs/tasks/task-031-site-audit.md`

### Nästa steg
1. Kör `cim` + verifiera Layout Builder för `high_mast` + `street_area` (YAML skapade)
2. Bygg Layout Builder manuellt för `floodlight` (uppgradera), `ex_hazardous`, `accessories`
3. Kontrollera översättningar per produkt (SV/EN)

### Gjort idag (2026-04-20)
- ✅ Drupal core uppdaterad 11.3.6 → 11.3.7 (CVE-2026-6365/6366/6367)
- ✅ PhotoSwipe installerad via Composer (asset-packagist + oomphinc/composer-installers-extender)
- ✅ Colorbox avinstallerad
- ✅ `media.image.splide` view mode skapad med `max_1300x1300`
- ✅ `product_responsive` uppdaterad med `max_2600x2600` för bättre mobilkvalitet

### Gjort idag (2026-04-19)
- ✅ Konfigurator-bild (MAX/OPTI/SROW) — `getUntranslated()` + loop för brutna mediareferenser
- ✅ Bildbyte vid ändstycke fungerar på alla tre produkttyper
- ✅ Case-sidor: Splide Media bildkvalitet fixad — `max_1300x1300` + `medium` thumbnail
- ✅ `Product Responsive` uppdaterad med `max_650x650` + `max_1300x1300` + korrekt `sizes`
- ✅ Colorbox lightbox aktiverad på case-sidor — bilder öppnas i fullstorlek utan beskärning
- ✅ Drupal core har 3 säkerhetsvarningar (CVE-2026-6365/6366/6367) — behöver uppdateras

### Gjort idag (2026-04-16)
- ✅ Etiketter verifierade — redan klara sedan förra session
- ✅ `feeds_item` verifierat på alla 12 bundles (produkt + variation)
- ✅ Layout Builder YAML skapad för `high_mast` + `street_area`
- ✅ Produkt 35 (DB53 Hilton linear LED) — klar

---

## Öppna tasks

| Task | Status | Beskrivning |
|------|--------|-------------|
| TASK-031 | 🔄 In Progress | Site Audit — Layout Builder Kategori C + översättningar |
| TASK-029 | ⏸️ Pausad | Fas 3: CSV-mallar & feeds (väntar på audit) |
| TASK-018 | 🔄 In Progress | Cart page layout |
| TASK-017b | 🔄 In Progress | Produktseriesidor + Views |
| TASK-013 | 🔄 In Progress | Attribut-cleanup |
| TASK-030 | ⏳ Parkerad | UX-feedback Thomas |
