# Lösning: MCP Tools Setup för Claude Desktop

**Datum**: 2026-02-21
**Modul**: mcp_tools 1.0.0-beta2
**Status**: ✅ Fungerar

---

## Syfte

Koppla Claude Desktop direkt till Drupal via MCP (Model Context Protocol) för att
kunna manipulera Drupal-entiteter, config och cache utan att gå via admin UI eller Drush.

---

## Installerade moduler

```bash
ddev drush en mcp_tools mcp_tools_content mcp_tools_config mcp_tools_cache \
  mcp_tools_structure mcp_tools_views mcp_tools_media mcp_tools_layout_builder \
  mcp_tools_image_styles mcp_tools_stdio mcp_tools_remote -y
```

**Aktiverade sub-moduler (selekterat för TritonLED):**
- `mcp_tools` — huvud
- `mcp_tools_content` — nodes/produkter
- `mcp_tools_config` — config management
- `mcp_tools_cache` — cache clear
- `mcp_tools_structure` — content types, fields
- `mcp_tools_views` — views
- `mcp_tools_media` — mediafält
- `mcp_tools_layout_builder` — layout
- `mcp_tools_image_styles` — bildformat
- `mcp_tools_stdio` — STDIO transport
- `mcp_tools_remote` — HTTP transport

**Ej aktiverade** (inte relevant för TritonLED just nu):
paragraphs, webform, metatag, sitemap, scheduler, migration, moderation,
ultimate_cron, search_api, recipes, observability, redirect, pathauto, jsonapi,
entity_clone, batch, analysis, templates, theme, users, menus, blocks, cron

---

## Transport: HTTP (valt över STDIO)

STDIO kräver att `drush` finns globalt på Mac — det gör det inte i DDEV-miljö.
HTTP transport via `mcp_tools_remote` fungerar med DDEV.

---

## Konfiguration av HTTP endpoint

### Steg 1: Sätt upp execution user via Drush
```bash
ddev drush mcp-tools:remote-setup
```
Skapar en dedikerad `mcp_tools_remote`-användare (uid 2) med rätt roller.

### Steg 2: Aktivera HTTP endpoint via config
```bash
ddev drush config:set mcp_tools_remote.settings enabled 1 -y
ddev drush config:set mcp_tools_remote.settings allow_uid1 1 -y
ddev drush cr
```

### Steg 3: Skapa API-nyckel
```bash
ddev drush mcp-tools:remote-key-create --label="Claude" --scopes=read,write
```
Spara nyckeln — visas bara en gång!

---

## Claude Desktop config

Fil: `~/Library/Application Support/Claude/claude_desktop_config.json`

**OBS:** Claude Desktop stöder inte `type: "http"` direkt — kräver `npx mcp-remote`
som mellanhand med `--allow-http` för lokala HTTP-URLs.

```json
{
  "mcpServers": {
    "drupal": {
      "command": "npx",
      "args": [
        "mcp-remote",
        "http://tritonled.ddev.site/_mcp_tools",
        "--allow-http",
        "--header",
        "Authorization: Bearer mcp_tools.f2e4e9833b8f.FWC6D-cFcklxxIRr5CtBznpEaTjXgPBxHUt8fEBQJBA",
        "--header",
        "Accept: application/json, text/event-stream"
      ]
    }
  },
  "preferences": {
    "sidebarMode": "chat",
    "coworkScheduledTasksEnabled": false
  }
}
```

---

## Verifiera att det fungerar

Testa endpointen manuellt:
```bash
curl -X POST \
  -H "Authorization: Bearer mcp_tools.f2e4e9833b8f.FWC6D-cFcklxxIRr5CtBznpEaTjXgPBxHUt8fEBQJBA" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json, text/event-stream" \
  -d '{"jsonrpc":"2.0","method":"initialize","id":1,"params":{"protocolVersion":"2024-11-05","capabilities":{},"clientInfo":{"name":"test","version":"1.0"}}}' \
  http://tritonled.ddev.site/_mcp_tools
```

Ska returnera JSON med `protocolVersion` och `serverInfo`.

---

## Lärdomar

1. **Claude Desktop ≠ Claude Code** — `type: "http"` fungerar bara i Claude Code
2. **mcp-remote krävs** för HTTP i Claude Desktop
3. **`--allow-http` krävs** för lokala HTTP-URLs (ej HTTPS)
4. **STDIO fungerar inte** med DDEV eftersom `drush` inte finns globalt på Mac
5. **UI-buggar i mcp_tools_remote** — använd Drush för konfiguration, inte admin UI
6. **`mcp-tools:remote-setup`** skapar automatiskt en säker execution user
7. **Endpoint-aktivering** måste göras via `drush config:set`, inte UI

---

## Byta sub-moduler

När projektet behöver andra verktyg (t.ex. SEO):
```bash
# Aktivera
ddev drush en mcp_tools_analysis mcp_tools_metatag mcp_tools_redirect -y

# Inaktivera
ddev drush pm:uninstall mcp_tools_[namn] -y
```

---

**Version**: 1.0
**Skapad**: 2026-02-21
**Författare**: Claude + Stefan
