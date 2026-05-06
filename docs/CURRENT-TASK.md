# CURRENT TASK

## Session 2026-05-06 — Avslutad

### Gjort denna session:
- **Thumbnail-karusellen (node/12)**: Felsökt och löst — image style saknades på tumnagelformattern på prod
- **Bildsynk prod**: `rsync` av `2026-05/`-bilder från lokal till prod + `image:flush --all`
- **Footer-färg**: Ändrad från `var(--bs-secondary)` till `#3a3a3a` i `style.css`
- **CSS deploy**: Pushad till prod via `git pull` + `drush cr`

### Innan commit (om inte gjort):
```bash
ddev drush config:status
ddev drush cex -y
git add web/themes/custom/tritonled_radix/css/style.css
git add web/themes/custom/tritonled_radix/css/components/product-gallery.css
git commit -m "Style: footer #3a3a3a, thumbnail gallery CSS"
git push origin main
```

### OBS — config:status hade skillnader:
- `field.field.node.article.field_image` — Different
- `field.storage.node.field_image` — Different
- `views.view.customer_cases` — Different
- Flera `layout_builder_styles.*` — Only in DB

Exportera med `ddev drush cex -y` och granska diff innan commit.

---

## Nästa session — Backlog (prioritetsordning)

Se task-filer:
- **TASK-036** — Kontaktformulär fungerar inte (`/sv/form/contact`)
- **TASK-037** — Feature-block med SVG-ikoner (lyfts från node/12)
- **TASK-038** — Mega menu + produktkategori-landningssidor
- **TASK-039** — Pathauto — URL-struktur SV/EN
- **TASK-040** — Robot/AI-agentoptimering (sitemap, structured data)
- **TASK-041** — SEO och innehåll
