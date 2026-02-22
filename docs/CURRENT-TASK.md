# Aktuell Task

**Task**: TASK-005B  
**Fil**: `/docs/tasks/task-005b-remote-video-styling.md`  
**Status**: ✅ Löst 2026-02-22  
**Senast uppdaterad**: 2026-02-22

## Vad som gjordes idag (2026-02-22)

### TASK-005B — Remote video ratio i produktgalleri ✅
- Root cause: CSS redigerades i fel tema (`tritonled` istället för `tritonled_radix`)
- Root cause: Blazy kunde inte hämta oEmbed-ratio offline → 150px hårdkodad höjd
- Root cause: Splide optionset hade `autoHeight: false` och `heightRatio: 0.75`
- Lösning: CSS-filer skapade i `tritonled_radix`, länkade i `libraries.yml`
- Lösning: `aspect-ratio: 16/9` direkt på `.media--youtube` (ej beroende av Blazy API)
- Lösning: Splide optionset `autoHeight: true`, `heightRatio: 0`
- Dokumenterat i `/docs/03-solutions/active-theme-css-checklist.md`

## Startpunkt nästa session

Produktgalleriet fungerar: bilder och remote video visas med korrekt ratio, autoHeight på Splide.

**Nästa steg (att prioritera):**
1. Hero-karusellen — styling, layout, text-overlay, ratio
2. Featured Products-korten — styling
3. Kontrollera bildladdning (lazy-loading i Splide)

## Kvarstående städning
- CSS-aggregering är AV under dev — slå PÅ igen inför produktion:
  `ddev drush config-set system.performance css.preprocess 1 -y`
