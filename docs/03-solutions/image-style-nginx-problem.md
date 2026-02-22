# Image Style Generering — Nginx On-Demand Problem

**Discovered**: 2026-02-22  
**Status**: Workaround känd, root cause ej löst  
**Påverkar**: Alla responsive image styles (hero_desktop, hero_tablet, hero_mobile, etc.)

---

## 🔴 Problemet

Drupal genererar INTE image style-derivat on-demand via nginx när en bild saknas i styles-mappen.

### Symptom
- Brutna bilder på Hero-karusellen för nya produktbilder
- 404 på `sites/default/files/styles/hero_desktop/public/...`
- Styles-mappen (`hero_desktop/`) skapas aldrig
- Problemet uppstår för filer importerade via Feeds

### Vad som FUNGERAR
- `createDerivative()` via Drush/PHP fungerar perfekt
- Bilden genereras korrekt om man triggar det manuellt

### Vad som INTE fungerar
- Nginx `try_files` → `@rewrite` → `/index.php` triggar inte Drupal's image style delivery
- Nya bilder importerade via Feeds får aldrig sina derivat automatiskt

---

## 🔍 Root Cause (misstänkt)

DDEV kör **nginx-fpm** med mutagen (perf mode). Nginx-konfigurationen för image styles:

```nginx
location ~ ^/sites/.*/files/styles/ {
    try_files $uri @rewrite;
}

location @rewrite {
    rewrite ^ /index.php;
}
```

Problemet är att `@rewrite` skriver om till `/index.php` **utan query string**. Drupal's image style delivery (`image_style_deliver()`) behöver hela den ursprungliga URI:n för att veta vilken style och fil som ska genereras.

Korrekt nginx-konfiguration borde vara:
```nginx
location @rewrite {
    rewrite ^/(.*)$ /index.php?q=$1;
}
```

Men detta är DDEV's auto-genererade config (`#ddev-generated`) och bör inte ändras manuellt utan att ta bort den taggen.

---

## ✅ Workaround — Manuell generering via Drush

Generera derivat för specifika filer och styles:

```bash
ddev drush php-eval "
foreach (['hero_desktop','hero_tablet','hero_mobile'] as \$s) {
  \$style = \Drupal\image\Entity\ImageStyle::load(\$s);
  foreach (['public://2026-02/opti.webp','public://2026-02/srow.webp'] as \$src) {
    \$dst = \$style->buildUri(\$src);
    echo \$s . ' ' . basename(\$src) . ': ' . (\$style->createDerivative(\$src, \$dst) ? 'OK' : 'FAIL') . PHP_EOL;
  }
}
"
```

### Generera ALLA derivat för ALLA hero styles

```bash
ddev drush php-eval "
\$styles = ['hero_desktop', 'hero_tablet', 'hero_mobile'];
\$images = \Drupal::entityQuery('file')
  ->accessCheck(FALSE)
  ->condition('uri', 'public://2026-02/%', 'LIKE')
  ->execute();
foreach (\$styles as \$s) {
  \$style = \Drupal\image\Entity\ImageStyle::load(\$s);
  foreach (\$images as \$fid) {
    \$file = \Drupal\file\Entity\File::load(\$fid);
    \$src = \$file->getFileUri();
    \$dst = \$style->buildUri(\$src);
    if (!file_exists(\Drupal::service('file_system')->realpath(\$dst))) {
      \$result = \$style->createDerivative(\$src, \$dst);
      echo \$s . ' ' . basename(\$src) . ': ' . (\$result ? 'OK' : 'FAIL') . PHP_EOL;
    }
  }
}
"
```

---

## 📋 När ska detta köras?

Kör workaround-kommandot efter:
- Import av nya produktbilder via Feeds
- `ddev drush image-flush --all`
- Ny DDEV-miljö sätts upp
- Nya image styles skapas

---

## ✅ Permanent lösning (IMPLEMENTERAD 2026-02-22)

### Vald strategi: Nginx (LEMP)
Produktionsserver (Hostinger VPS) kör LEMP-stack med nginx. DDEV matchar detta med `nginx-fpm`.

### Ändring i nginx-config
Tog bort `#ddev-generated` från `.ddev/nginx_full/nginx-site.conf` och fixade `@rewrite`:

```nginx
location @rewrite {
    # Fixed: pass full URI so Drupal can generate image style derivatives on-demand.
    rewrite ^/(.*)$ /index.php?q=$1 last;
}
```

**Varför**: Den ursprungliga `rewrite ^ /index.php` skickade inte med URI:n, vilket gjorde att Drupal inte visste vilken image style som skulle genereras.

**Konsekvens**: DDEV-uppgraderingar skriver inte längre över nginx-filen automatiskt. Vid DDEV-uppgradering: kontrollera om DDEV's standard nginx-config för drupal11 har ändrats och merga manuellt om nödvändigt.

### Aktivering
```bash
ddev restart
```

### Borttagna alternativ
- Apache: Valdes bort — produktion kör nginx, vi vill matcha
- Custom hook: Valdes bort — för komplex, löser inte root cause

---

## 🔗 Relaterade filer
- Nginx-config: `.ddev/nginx_full/nginx-site.conf`
- DDEV config: `.ddev/config.yaml`
- Image styles: `/admin/config/media/image-styles`
- Responsive image styles: `/admin/config/media/responsive-image-style`

---

**Version**: 1.0  
**Skapad**: 2026-02-22  
**Författare**: Stefan + Claude
