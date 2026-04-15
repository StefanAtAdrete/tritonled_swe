# Aktuell Task

**Senast uppdaterad**: 2026-04-15

---

## ⏭️ Nästa session startar här: TASK-031 fortsätter — steg 3

**Läs**: `docs/tasks/task-031-site-audit.md`

### Nästa steg
1. Bygg Layout Builder-layout för produkt 35 (DB53 Hilton linear LED) i UI
2. Bygg default Layout Builder-layout för `highbay` i UI
3. Kontrollera översättningar per produkt (SV/EN)
4. Komplettera Kategori C — `feeds_item` + Layout Builder

### Gjort idag (2026-04-15)
- ✅ DDEV migrerad MariaDB → MySQL 8.0
- ✅ Lokal DB + bilder synkade från prod
- ✅ TASK-030 skapad (UX-feedback Thomas)
- ✅ TASK-031 skapad och påbörjad
- ✅ 031-A systemstatus klar
- ✅ 031-B fältmatris produkt + variation klar
- ✅ 031-D alla dubblettbeslut fattade
- ✅ `field_product_category` borttaget (alla bundles)
- ✅ `field_product_media_files` migrerad och borttagen
- ✅ Dubbletter i media rensade (produkt 35, 36)
- ✅ Config-synk löst (79 otrackade filer committade)
- ✅ 62 fältetiketter uppdaterade via YAML (EN) + cim
- ✅ `field_product_category` + `field_product_media_files` borttagna från DB och config/sync
- ✅ Social media channels block återskapat (UUID-alignment)
- ✅ `views.view.assorted_products` importerad + fixad (status true, field_product_media, block plugins)
- ✅ Config dependencies rensade (14 display-filer)
- ✅ Startsidan fungerar — DB53 och High Bay Fairyland visas med bild
- ⚠️ Layout Builder per-produkt kan inte synkas via config — måste byggas manuellt per miljö
- ⚠️ Översättningar (SV/EN) behöver kontrolleras per produkt

---

## Öppna tasks

| Task | Status | Beskrivning |
|------|--------|-------------|
| TASK-031 | 🔄 In Progress | Site Audit — nästa: config-synk + etiketter + layout |
| TASK-029 | ⏸️ Pausad | Fas 3: CSV-mallar & feeds (väntar på audit) |
| TASK-018 | 🔄 In Progress | Cart page layout |
| TASK-017b | 🔄 In Progress | Produktseriesidor + Views |
| TASK-013 | 🔄 In Progress | Attribut-cleanup |
| TASK-030 | ⏳ Parkerad | UX-feedback Thomas |
