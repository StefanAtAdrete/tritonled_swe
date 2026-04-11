# Aktuell Task

**Senast uppdaterad**: 2026-04-11

---

## ⏭️ Nästa session startar här: TASK-029 Fas 1

**Läs**: `docs/tasks/task-029-produkttyper-faltstruktur.md`  
**Läs**: `docs/skills/drupal-product-type/SKILL.md`  

**Gör:**
1. Skapa taxonomy `product_category` med 8 termer (admin UI)
2. Skapa `field_product_category` (entity_reference → taxonomy)
3. Skapa 8 gemensamma fält via `drush php:eval`
4. Tilldela `field_product_category` på befintliga typer: max, opti, srow, surge_protection
5. `ddev drush cex -y` + commit

**Inget mer behöver laddas in** — task-filen och skill-filen räcker.

---

## Senast gjort: Session 2026-04-11

### Prod cache-problem löst ✅
- `twig_debug: 'true'` borttagen från `system.performance` i prod DB
- `development.services.yml` borttagen från prod (innehöll felaktig twig_debug-nyckel)
- `system.performance` lagd i Config Split `local` complete_list — deployas aldrig till prod
- `config/sync/system.performance.yml` lagd i `.gitignore`

### Private-mapp fixad på prod ✅
- Skapad på `/home/tritonled/htdocs/tritonled.se/private`
- `settings.php` på prod uppdaterad till `$app_root . '/../private'`

### Drupal core + moduler uppdaterade ✅
- Drupal core 11.3.5 → 11.3.6
- Commerce 3.3.3 → 3.3.4
- Klaro 3.0.8 → 3.0.9
- AI 1.2.10 → 1.3.2 (avinstallerad tillfälligt på prod pga rättighetsproblem)
- 45 paket totalt uppdaterade

### Rättighetsproblem på prod lösta ✅
- Root: `chown -R tritonled:tritonled /home/tritonled/htdocs/tritonled.se/`
- Root: `chmod -R g+w /home/tritonled/htdocs/tritonled.se/web/modules/contrib/`
- `core`-mappen ägdes av `tritonswe_ssh` — nu fixat

### tool_* moduler städade från DB ✅
- `tool_content`, `tool_content_translation`, `tool_entity`, `tool_system` raderade från `system.schema`

### Honeypot installerat ✅
- Installerat lokalt och på prod
- Konfigurerat: Protect all forms, 5s time limit, logging aktiverat

### Surge Protection feeds + CSV skapade ✅
- `feeds.feed_type.tritonled_surge_products` och `tritonled_surge_variations` skapade
- `feeds_item` + `field_product_sku` lagda till på surge_protection product/variation type
- CSV-filer: `data/surge_products.csv` (8 produkter) + `data/surge_variations.csv` (25 varianter)
- Importerade på prod via admin/content/feeds
- **OBS**: dump-skript ligger kvar i `scripts/` — bör städas bort

### Media-entiteter skapade på prod ✅
- MID 123-130 (SURGYS-bilder) skapade på prod via `drush php:eval`
- Kopplade till produkter 27-34 via `field_product_media`
- Filer rsynkade med `--no-perms --no-times` — uppdaterat i SKILL.md

### TASK-018 Cart page — påbörjad 🔄
- Problem identifierat: `surge_protection` variation type använder `orderItemType: default`
- Övriga produkter använder `quote` order type
- Fix: Ändra till `quote` på `admin/commerce/config/product-variation-types/surge_protection/edit`
- Dubbla knappar (Update cart + Quote Request) beror på två separata orders
- Fortsätt nästa session

---

## Senast gjort: Session 2026-04-01

### Prod-fel åtgärdade ✅
- **Trusted host**: Lagt till `trusted_host_patterns` i `settings.php` på prod
  - `^tritonled\.se$`, `^www\.tritonled\.se$`, `^preview\.affarsfabriken\.se$`
- **Private directory**: Fixat via Emergency mode — `chown -R 1006:1006` på `/mnt/sdb1/.../private`
- **Config Split**: Entity installerad + schema satt till 8003 + status=false via `drush php:eval`

### SSH-access etablerad ✅
- SSH fungerar på port **2222** (inte 22)
- IP: `168.231.108.87`
- Alias konfigurerat: `ssh tritonled`
- SSH-nyckel: `~/.ssh/id_tritonled_new` (ed25519, ingen passphrase)
- UFW uppdaterad att tillåta port 2222
- Dokumenterat i `/docs/skills/server-management/SKILL.md`

### server-management SKILL.md skapad ✅
- Sökväg: `/docs/skills/server-management/SKILL.md`
- Innehåller: SSH-access, rsync media-filer, deploy-flöde, felproblem, settings.php

---

## Senast gjort: Session 2026-03-29

### Session SOP
- Skapade `/docs/04-workflows/session-sop.md`
- Lade till `## 🔄 Sessionsstruktur` i `00-START-HERE.md` (v2.5)

### MCP-moduler avinstallerade
- Avinstallerade lokalt och på prod via `cim --partial`
- `mcp_tools.settings` och `mcp_tools_servers.settings` raderade från DB

### TASK-019 — Klaro GDPR ✅ Klar
- `klaro.texts.yml` återställd till engelska källtexter
- Svenska texter sparas via `getLanguageConfigOverride('sv', $name)->setData($data)->save()`
- Alla `klaro.klaro_app.*` och `klaro.klaro_purpose.*` översatta till svenska
- Footer template override skapad i `tritonled_radix`
- Ny region `footer_bottom` tillagd i `tritonled_radix.info.yml`
- `footer.css` skapad för footer-styling
- **OBS**: Language overrides måste köras via `drush php:eval` — `cim` importerar inte `language/sv/`-filer automatiskt

---

## Öppna tasks

| Task | Status | Beskrivning |
|------|--------|-------------|
| TASK-028 | ✅ Klar | staticSpecs i JSON-scheman + Drupal-import, verifierad alla 12 produkter |
| TASK-027 | ✅ Klar | StaticSpecsBlock + PrintButtonBlock — placerbara Layout Builder-block |
| TASK-025 | ✅ Klar | Konfigurator dropdown-layout: watt/optic breddad, btn-sm |
| TASK-022 | ✅ Klar | Översättning SV/EN |
| TASK-017b | 🔄 In Progress | Produktseriesidor + Views |
| TASK-018 | 🔄 In Progress | Cart page layout |
| TASK-029 | 🔄 In Progress | Produkttyper & fältstruktur för utökad produktkatalog |
| TASK-013 | 🔄 In Progress | Attribut-cleanup |

---

## Media-entiteter per produkt (konfigurator)

| Product | ID | MIDs |
|---------|-----|------|
| MAX BASE | 15 | 41(TM-C), 42(TM-E), 43(TM-V), 44(TM-B), 45(TM-W), 67(TM-default) |
| MAX-PRO | 16 | 116(TMP-C), 117(TMP-E), 118(TMP-B), 119(TMP-W), 120(TMP-default) |
| MAX-E | 18 | 81(TME-E), 82(TME-V), 83(TME-B), 84(TME-W), 101(TME-default) |
| MAX-ED | 19 | 112(TMED-E), 113(TMED-V), 114(TMED-B), 115(TMED-W), 102(TMED-default) |
