# TASK-035 — Hero Video & Article Slide Support

## Status: COMPLETE

## Vad vi byggde

Utökade hero-karusellen (`hero_case`-viewen) på startsidan så att den stödjer:
- **Direktvideo (mp4)** via Drupal Media `video`-bundle
- **Artiklar** som hero-slides (inte bara `customer_cases`)
- **Klickbara slides** med stretched-link
- **Fallback** — befintliga customer_cases med `field_media` fortsätter fungera

---

## Arkitektur

### Views: `hero_case`
- Style bytt från `views_bootstrap_carousel` → `default` (unformatted)
- Bundle-filter utökad: `customer_cases` + `article`
- Fält tillagda: `field_hero_media` (view mode: hero), `view_node` (URL as plain text)
- `field_media` behålls för bakåtkompatibilitet med befintliga cases

### Templates (tritonled_radix)
- `views-view-unformatted--hero-case.html.twig` — Bootstrap 5 karusell-struktur
- `views-view-fields--hero-case.html.twig` — slide-rendering med media + caption + stretched-link
- `field--field-hero-media--hero-case.html.twig` — video-rendering (HTML5 `<video>` för video-bundle)

### Media rendering (fallback-kedja)
```
field_hero_media (video) → <video autoplay muted loop>
field_hero_media (image) → {{ item.content }}
field_media (fallback)   → {{ item.content }}
```

### Caption
- `text-start ps-5`, `max-width: 60%` — vänsterjusterad med marginal
- Lång titel bryts automatiskt på två rader
- Hela sliden klickbar via Bootstrap `stretched-link`

---

## Innehållsstruktur för video-slide

1. Skapa media: `/media/add/video` → ladda upp `.mp4`
2. Skapa artikel: `/node/add/article`
   - Titel (visas i hero)
   - `field_hero_media` → välj mp4-media
3. Artikeln plockas upp automatiskt av viewen (sorterad på `created DESC`)

---

## Filer skapade/ändrade

| Fil | Typ | Ändring |
|-----|-----|---------|
| `config/sync/views.view.hero_case.yml` | Config | Style, fält, bundle-filter |
| `templates/views/views-view-unformatted--hero-case.html.twig` | Ny | Karusell-struktur |
| `templates/views/views-view-fields--hero-case.html.twig` | Ny | Slide-rendering |
| `templates/field/field--field-hero-media--hero-case.html.twig` | Ny | Video-rendering |

---

## Git commit
`[TASK-035] Hero carousel: video support via article + field_hero_media`

---

## Kvarstående / nästa steg
- [ ] Exportera views-config: `ddev drush cex -y` och committa
- [ ] Twig debug inaktiveras innan deploy: ta bort `twig.config` från `development.services.yml`
- [ ] TASK-035 block (youtube_video custom block type) kan städas bort om ej behövs
