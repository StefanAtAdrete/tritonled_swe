# TritonLED Compatibility Fixes (tritonled_compat)

**Status**: Aktiv — krävs i produktion  
**Syfte**: Samlar workarounds och B2B-logik som inte kan lösas via config eller contrib-moduler.

---

## ⚠️ Viktigt

Denna modul är avsiktligt enkel. Innan ny kod läggs till här ska beslutsträdet
i `/docs/DRUPAL-DECISION-TREE.md` följas. Kod läggs till som **sista utväg**.

---

## Innehåll

### 1. Feeds Item Bug Fix
**Hook**: `hook_field_info_alter()`  
**Fil**: `src/Plugin/Field/FieldType/SafeFeedsItem.php`

**Problem**: Feeds modul 8.x-3.2 — fältet `feeds_item` kraschar Layout Builder
vid generering av exempelvärden. Felet: `"fid" key not found in cleanIds()`.

**Lösning**: Overrida `generateSampleValue()` för `feeds_item` field type
så att den returnerar tom array istället för att krascha.

**När tas detta bort?**: När Feeds-modulen fixar buggen i en stabil release.  
**Mer info**: `/docs/03-solutions/feeds-item-ajax-bug.md`

---

### 2. Commerce Price Access Control
**Hook**: `hook_entity_field_access()`  
**Fil**: `tritonled_compat.module`

**Problem**: Commerce `price` är ett base field och stödjer inte
`field_permissions`-modulen (fältet är låst).

**Affärsregel**: TritonLED är ett B2B-system där kunder måste verifieras
manuellt av personalen innan de får se priser. Ingen ska kunna se priser
bara genom att registrera ett konto.

**Logik**:
- `anonymous` → ser **inga** priser
- `authenticated` → ser **inga** priser (ej verifierad ännu)
- `elektriker` → ser priser (listpris, manuellt tilldelad av TritonLED)
- `partner_silver` → ser priser + 5% rabatt via Commerce Promotion
- `partner_gold` → ser priser + 10% rabatt via Commerce Promotion
- `administrator` → ser alltid priser

**Berörda fält**: `price`, `list_price`, `unit_price`, `total_price`

**Cache**: `cachePerUser()` — säkerställer korrekt caching per användare.

**När tas detta bort?**: Om Commerce får inbyggt stöd för rollbaserad
fältsynlighet på base fields, eller om `field_permissions` börjar stödja
Commerce base fields.  
**Mer info**: `/docs/tasks/task-003-product-page.md`

---

## Versionshistorik

| Datum | Ändring |
|-------|---------|
| 2026-02-21 | Modul skapad — Feeds Item Bug Fix |
| 2026-02-24 | Lade till Commerce Price Access Control |
