# Aktuell Task

**Task**: TASK-015 (Planning)
**Status**: Planning — väntar på JSON:API-undersökning
**Senast uppdaterad**: 2026-03-07
**Fil**: `/docs/tasks/task-015-variant-konfigurator.md`

---

## Bakgrund (varför vi är här)

Commerce's Add to Cart-formulär matchar varianter hierarkiskt. För produkter med oberoende attribut (OPTI/MAX/SROW) innebär det att väljer man Connection=EN och byter Längd → återgår Connection till CG. Problemet är fundamentalt i Commerce och kan inte konfigureras bort.

**Beslut**: Bygga en custom variant-konfigurator baserad på JSON:API som kringgår Commerce's attributformulär men behåller hela Commerce-arkitekturen (cart, order, priser etc.).

---

## Nästa steg (nästa session)

### 1. Stefan kör dessa kommandon för att undersöka JSON:API

```bash
# Kontrollera vilka variation resource types som finns
ddev drush php-eval "print_r(array_keys(\Drupal::service('jsonapi.resource_type.repository')->all()));" | grep variation
```

```bash
# Testa att variantdata är tillgänglig
ddev drush php-eval "
\$storage = \Drupal::entityTypeManager()->getStorage('commerce_product_variation');
\$ids = \$storage->getQuery()->condition('product_id', 5)->range(0, 3)->execute();
foreach (\$ids as \$id) {
  \$v = \$storage->load(\$id);
  print \$v->getSku() . ' | connection: ' . \$v->get('attribute_connection')->target_id . ' | length: ' . \$v->get('attribute_length')->target_id . PHP_EOL;
}
"
```

### 2. Med output från kommandon ovan → bygga konfiguratorn

---

## Öppna tasks (lägre prioritet, väntar)

| Task | Status | Fil |
|------|--------|-----|
| TASK-013 | In Progress — väntar på TASK-014 | task-013-attribut-cleanup.md |
| TASK-014 | In Progress | task-014-reimport-varianter.md |
| TASK-016 | Planned | task-016-optik-line-item-fields.md |

---

## Produktstatus (nuläge)

- **MAX** (ID:8): 488 varianter ✓
- **SROW** (ID:5): 795 varianter ✓
- **OPTI** (ID:13): 260 varianter ✓
- Alla kritiska produkter inlagda

---

## Lärdomar från förra sessionen (2026-03-07)

- `hook_page_attachments` i `.theme`-filer anropas INTE tillförlitligt i Drupal 11 — lägg alltid kritiska page attachments i en `.module`-fil (t.ex. `tritonled_compat`)
- MutationObserver + Blazy.load() löste inte bildproblemet — rotorsaken var Commerce's attributordning, inte Blazy
- Dokumentation: `/docs/03-solutions/product-ajax-library-attach.md`
