# Aktuell Task

**Task**: TASK-002 (Hero Carousel)  
**Status**: ✅ Hero-bilder fungerar och auto-genereras  
**Senast uppdaterad**: 2026-02-22

## Vad som gjordes idag (2026-02-22)

### Hero-bilder auto-generering ✅
- Root cause: `imageapi_optimize_webp` (v2.1.0) hade bugg i controller
- Lösning: Avinstallerade modulen, använder `image_convert_avif` direkt i image styles
- Hero-styles producerar nu AVIF (15-27KB) automatiskt vid HTTP-request
- Se: `03-solutions/image-style-auto-generation.md`

## Startpunkt nästa session

Hero-karusellen fungerar med auto-genererade AVIF-bilder.

**Nästa steg (att prioritera):**
1. Hero-karusellen — styling (overlay, text-position, ratio, header-gap)
2. Featured Products-korten — styling
3. Kontrollera bildladdning (lazy-loading i Splide)

## Kvarstående städning
- CSS-aggregering är AV under dev — slå PÅ igen inför produktion:
  `ddev drush config-set system.performance css.preprocess 1 -y`
