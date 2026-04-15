# Aktuell Task

**Senast uppdaterad**: 2026-04-15

---

## ⏭️ Nästa session startar här: TASK-031 fortsätter

**Läs**: `docs/tasks/task-031-site-audit.md`

### ⚠️ FÖRSTA PRIORITET: config/sync vs prod-DB
`cex` kördes och raderade 300+ filer ur config/sync — återställdes med `git checkout`.
Orsak: prod-DB har annan config-state än config/sync på Mac.
Måste hanteras kontrollerat i nästa session innan vi gör fler ändringar.
Alternativ:
- Kör `ddev drush cex -y` och granska diff — committa hela skillnaden om det ser rätt ut
- Eller identifiera vad som faktiskt saknas/skiljer och hantera selektivt

### Nästa steg i 031-E (rensning)
1. Rätta etiketter i admin UI — 12 fält totalt:
   - Produktfält: `field_dimming` → Dimming, `field_driver` → Driver, `field_ip_class` → IP-klass, `field_mounting_type` → Monteringstyp, `field_cable` → Kabel, `field_product_media` → Produktbilder, `field_warranty` → Garanti (år)
   - Variationsfält: `field_cri` → CRI, `field_lumens` → Lumen, `field_mounting_type` → Monteringstyp, `field_variation_media` → Variationsbild, `field_warranty_years` → Garanti (år)
2. Sätt `field_product_media` formatter till obegränsat i Layout Builder per produkttyp
3. Bygg om layout för produkt 35 (DB53 Hilton linear LED)
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
