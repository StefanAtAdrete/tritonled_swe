# Aktuell Task

**Senast uppdaterad**: 2026-03-29

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
| TASK-022 | ✅ Klar | Översättning SV/EN |
| TASK-017b | 🔄 In Progress | Produktseriesidor + Views |
| TASK-018 | In Progress | Cart page layout |
| TASK-013 | In Progress | Attribut-cleanup |

---

## Media-entiteter per produkt (konfigurator)

| Product | ID | MIDs |
|---------|-----|------|
| MAX BASE | 15 | 41(TM-C), 42(TM-E), 43(TM-V), 44(TM-B), 45(TM-W), 67(TM-default) |
| MAX-PRO | 16 | 116(TMP-C), 117(TMP-E), 118(TMP-B), 119(TMP-W), 120(TMP-default) |
| MAX-E | 18 | 81(TME-E), 82(TME-V), 83(TME-B), 84(TME-W), 101(TME-default) |
| MAX-ED | 19 | 112(TMED-E), 113(TMED-V), 114(TMED-B), 115(TMED-W), 102(TMED-default) |
