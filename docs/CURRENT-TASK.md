# Aktuell Task

**Senast uppdaterad**: 2026-04-15

---

## ⏭️ Nästa session startar här: TASK-031 fortsätter — steg 2

**Läs**: `docs/tasks/task-031-site-audit.md`

### Nästa steg i 031-E (rensning)
1. Sätt `field_product_media` formatter till obegränsat antal bilder i Layout Builder per produkttyp
2. Bygg om layout för produkt 35 (DB53 Hilton linear LED)
3. Komplettera Kategori C — `feeds_item` + Layout Builder

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
