# Image Style Auto-generation Fix

**Datum**: 2026-02-22  
**Task**: TASK-002 (Hero Carousel)  
**Status**: ✅ Löst

---

## Problem

Hero-karusellens bilder visade 404 och genererades aldrig automatiskt via HTTP-request.
Problemet återkom efter varje `image:flush`.

## Root Cause

Modulen `imageapi_optimize_webp` (v2.1.0) hade tagit över Drupal core's
`ImageStyleDownloadController` via routing. Modulens controller hade en bugg:

```php
$target = $request->query->get('file');
if (!$target) {
    throw new NotFoundHttpException();
}
```

Den krävde en `?file=` query parameter — men Drupal core skickar filsökvägen
som del av URL:en. Alla `.webp`-filer fick därför `NotFoundHttpException` direkt,
utan att derivat genererades.

## Lösning

Avinstallera `imageapi_optimize_webp` och `imageapi_optimize_webp_responsive`:

```bash
ddev drush pm:uninstall imageapi_optimize_webp imageapi_optimize_webp_responsive -y
ddev drush cr
```

Vi använder istället `image_convert_avif`-effekten direkt i image styles
(Admin → Configuration → Media → Image styles → lägg till Convert-effekt).

## AVIF-optimering på Hero-styles

Lägg till Convert (image_convert_avif) som effect weight 2 på:
- `hero_desktop` (1920x480)
- `hero_tablet` (1024x256)  
- `hero_mobile` (600x600)

Resulterar i 15-27KB AVIF-filer — utmärkt för hero-bilder.

## Viktigt att veta

- `imageapi_optimize_webp` ska ALDRIG återinstalleras utan att buggen är fixad
- Källfiler är redan `.webp` — derivat blir `.webp.avif` (dubbel extension är normalt)
- Auto-generering fungerar nu korrekt via nginx → Drupal core controller

## Felsökning

Om bilderna slutar fungera igen:

```bash
# Kontrollera vilken controller som hanterar image styles
ddev drush php:eval "
\$router = \Drupal::service('router.route_provider');
\$route = reset(\$router->getRoutesByNames(['image.style_public']));
echo \$route->getDefault('_controller');
"
# Ska vara: Drupal\image\Controller\ImageStyleDownloadController::deliver
# INTE: Drupal\imageapi_optimize_webp\Controller\...

# Manuell generering vid behov
ddev drush image:flush hero_desktop
ddev drush image:flush hero_tablet
ddev drush image:flush hero_mobile
```
