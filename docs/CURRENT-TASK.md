# Aktuell Task

**Task**: Config Split installation
**Status**: ✅ Klar
**Senast uppdaterad**: 2026-03-26

---

## Senast gjort: TASK-022 ✅ (delvis)
- PHP och JS översatt med t()/Drupal.t()
- .po-fil skapad och importerad
- Views-block översatta SV/EN
- core.extension.yml rensad från tool_* moduler
- Pre-commit hook skapad

## Nästa session: Config Split

**Varför:** `tool_content`, `tool_entity` etc. (lokala MCP-moduler) hamnar i
`core.extension.yml` vid `cex` och kraschar produktions-deploy.

**Lösning:** `drupal/config_split` — separerar lokal config från produktionsconfig.

### Plan
1. `ddev composer require drupal/config_split`
2. `ddev drush en config_split -y`
3. Skapa `local`-split via admin UI
4. Flytta lokala moduler dit: `tool`, `tool_content`, `tool_content_translation`,
   `tool_entity`, `tool_system`, `mcp_tools` och submoduler, `ai`, `ai_provider_ollama`
5. Uppdatera `settings.php` i DDEV: `$config['config_split.config_split.local']['status'] = TRUE`
6. Uppdatera `settings.php` på produktion: `...['status'] = FALSE`
7. Verifiera: `cex` lokalt → tool_* hamnar i `config/split/local/` inte `config/sync/`
8. Deploy och verifiera produktion

### Känd fallgrop (dokumenterad 2026-03-26)
`ddev drush cex` exporterar ALLA aktiva moduler inkl. lokala MCP-moduler.
Utan Config Split måste `core.extension.yml` rensas manuellt innan varje deploy.
Pre-commit hook finns som temporär lösning: `.git/hooks/pre-commit`

---

## Öppna tasks

| Task | Status | Beskrivning |
|------|--------|-------------|
| Config Split | ✅ Klar | Separera lokal/prod config |
| TASK-022 | 🔄 Delvis klar | Översättning — Views syskon-block kvar |
| TASK-023 | Planned | Konfigurator mobiloptimering |
| TASK-017b | 🔄 In Progress | Produktseriesidor + Views |
| TASK-017 | Planned | Cart block styling |
| TASK-018 | In Progress | Cart page layout |
| TASK-013 | In Progress | Attribut-cleanup |
| TASK-019 | In Progress | Klaro GDPR cookie consent |
