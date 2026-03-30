# Aktuell Task

**Task**: TASK-019 Klaro GDPR cookie consent — Översättningar
**Status**: 🔄 In Progress (svenska language overrides kvar att verifiera)
**Senast uppdaterad**: 2026-03-29

---

## Senast gjort: Session 2026-03-29

### Session SOP
- Skapade `/docs/04-workflows/session-sop.md` med tre delar: Start, Checkpoint, Slut
- Lade till `## 🔄 Sessionsstruktur` direkt i `00-START-HERE.md` (v2.5)

### MCP-moduler avinstallerade
- `mcp_tools`, `mcp_tools_views`, `mcp_tools_users`, `mcp_tools_structure`, `mcp_tools_stdio`, `mcp_tools_content`, `tool` avinstallerade via `cim --partial`
- `mcp_tools.settings` och `mcp_tools_servers.settings` raderade från DB via `php:eval`
- Samma åtgärd kördes på produktionsservern
- `config/split/local/mcp_tools.settings.yml` raderad

### TASK-019 — Klaro GDPR översättningar
- `klaro.texts.yml` återställd till engelska källtexter
- `language/sv/klaro.texts.yml` skapad med svenska texter
- Alla `klaro.klaro_app.*` återställda till engelska
- `language/sv/klaro.klaro_app.*` skapade för: cms, klaro, vimeo, youtube, bluesky, facebook, instagram, linkedin, x, tiktok, mastodon, mastodon_module, threads, ga, gtm, google_maps, google_recaptcha, matomo, matomo_cookies, posthog, leaflet, deepchat, ai_alt_text_generation, simple_popup_blocks, umami
- `language/sv/klaro.klaro_purpose.*` skapade för: cms, external_content, analytics, advertising, security, livechat, styling
- **OBS**: Language overrides måste sparas via `drush php:eval` — `cim` importerar INTE `language/sv/`-filer automatiskt för config entity translations
- Kommando för att spara alla sv-overrides finns i sessionshistoriken (stort php:eval-block)

### Viktigt lärdomar
- Klaro config entity translations sparas via `Drupal::languageManager()->getLanguageConfigOverride('sv', $name)->setData($data)->save()`
- `language/sv/*.yml`-filer i config/sync är INTE tillräckliga — måste köras via php:eval eller cim med speciell hantering

---

## Öppna tasks

| Task | Status | Beskrivning |
|------|--------|-------------|
| TASK-019 | 🔄 In Progress | Klaro — sv language overrides körs via php:eval |
| TASK-022 | 🔄 Delvis klar | Översättning — Views syskon-block kvar |
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
