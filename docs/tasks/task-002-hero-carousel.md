# TASK-002: Hero Carousel

**Created**: 2026-02-17
**Status**: ✅ KLAR
**Last Updated**: 2026-02-26
**Related Tasks**: TASK-001

---

## 🧠 METODNOTERING: Huvuduppgifter delas alltid upp

En huvuduppgift som "Hero Carousel" innehåller ofta flera underuppgifter som måste lösas
i rätt ordning innan vi kan sätta ihop slutresultatet. Identifiera alltid dessa tidigt.

**Generellt mönster för frontend-sektioner i Drupal:**
1. **Content type / media type** – Vad ska hanteras?
2. **Image styles** – Rätt bildformat per breakpoint
3. **View modes** – Hur renderas innehållet (teaser, hero, card...)
4. **Views** – Samlar och strukturerar innehållet (block, page)
5. **Block / Layout Builder** – Placerar resultatet på sidan
6. **SDC / Template** – Sista utväg om core+views inte räcker

---

## SUB-TASKS

### A–E: ✅ Klara (se tidigare dokumentation)

### SUB-TASK F: Video och remote video i hero ✅ Klar 2026-02-26

---

## 🔍 Felsökning: Remote video (YouTube) svart i hero

### Problem
Slide med `media--type-remote-video` (YouTube) visade svart bakgrund.

### Diagnos steg för steg

**Steg 1 – Nätverkstest**
```bash
ddev exec curl -v "https://www.youtube.com"
```
→ TLS-handshake lyckades. DDEV kan nå YouTube. Inte ett nätverksproblem.

**Steg 2 – oEmbed-test från Drupal**
```bash
ddev drush php:eval "
\$url = 'https://youtu.be/BYSH7D0woMw';
\$resource_url = 'https://www.youtube.com/oembed?url=' . urlencode(\$url) . '&format=json';
\$response = \Drupal::httpClient()->get(\$resource_url);
echo \$response->getStatusCode();
"
```
→ 200 OK. Drupal kan hämta oEmbed-data.

**Steg 3 – Blazy-inställning**
```bash
ddev drush php:eval "print_r(\Drupal::config('blazy.settings')->getRawData());"
```
→ `use_oembed` var **avstängt**! Aktiverades:
```bash
ddev drush php:eval "
\Drupal::service('config.factory')->getEditable('blazy.settings')
  ->set('use_oembed', 1)->save();
"
```
→ Blazy börjar rendera thumbnail + play-knapp.

**Steg 4 – Fortfarande svart**
DOM-inspektion visade:
- `.media--blazy` höjd: 837px ✅
- `.carousel-inner` höjd: 279px ← klipper innehållet

`hero.css` saknade `.media--type-remote-video` i positioineringsregeln.

**Steg 5 – CSS-fix i hero.css**
```css
.view-hero .carousel-item .media--type-video,
.view-hero .carousel-item .media--type-image,
.view-hero .carousel-item .media--type-remote-video {  /* ← tillagd */
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
}
```

Plus tillägg i `media.css` för att Blazy-containers ska fylla carousel-item:
```css
.view-hero .carousel-item .media--type-remote-video .blazy,
.view-hero .carousel-item .media--type-remote-video .field__item,
.view-hero .carousel-item .media--type-remote-video .media--blazy {
  position: absolute !important;
  inset: 0 !important;
  width: 100% !important;
  height: 100% !important;
  padding-bottom: 0 !important;
}
```

---

## 🎬 Blazy Media Player-beteende

Blazy renderar remote video som **thumbnail + play-knapp** (inte autoplay iframe).
- Play-knapp: `.media__icon--play`
- Close-knapp: `.media__icon--close` (X-ikon)
- Klick på play → Blazy byter till iframe + lägger till klass `is-playing` på `.media--player`
- Klick på close → tar bort `is-playing`, visar thumbnail igen

### Lärdomar
- Blazy's `is-playing`-klass triggas via klassändring på `.media--player`
- MutationObserver är rätt verktyg för att lyssna på detta
- `carousel-control-prev/next` måste döljas vid uppspelning (annars syns pilar ovanpå videon)
- Slide-klick-navigering måste undanta `.media--player` klick

---

## JS: global.js tillägg

### 1. Pausa karusell + dölja pilar vid videouppspelning
```javascript
Drupal.behaviors.tritonledCarouselVideoPause = {
  attach: function (context, settings) {
    once('carousel-video-pause', '.view-hero .carousel', context).forEach(function (carouselEl) {
      var observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
          if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
            var target = mutation.target;
            var controls = carouselEl.querySelectorAll('.carousel-control-prev, .carousel-control-next');
            if (target.classList.contains('is-playing')) {
              bootstrap.Carousel.getInstance(carouselEl).pause();
              controls.forEach(function(c) { c.style.display = 'none'; });
            } else {
              bootstrap.Carousel.getInstance(carouselEl).cycle();
              controls.forEach(function(c) { c.style.display = ''; });
            }
          }
        });
      });
      carouselEl.querySelectorAll('.media--player').forEach(function (player) {
        observer.observe(player, { attributes: true, attributeFilter: ['class'] });
      });
    });
  }
};
```

### 2. Undanta media--player från slide-klick-navigering
```javascript
if (e.target.closest('.carousel-control-prev, .carousel-control-next, .carousel-indicators, .media--player')) return;
```

---

## CSS: Blazy close-knapp position

```css
/* Flytta close-knapp till nedre högra hörnet */
.media--player .media__icon--close {
  top: auto !important;
  bottom: 1rem !important;
  right: 1rem !important;
  left: auto !important;
  transform: none !important;
}
```

---

## 📝 Samlade lärdomar

1. **DDEV kan nå externa tjänster** – utgående trafik är inte blockerad
2. **Blazy `use_oembed` måste aktiveras** för att remote video ska renderas
3. **Blazy-ratio via padding-bottom krockar med aspect-ratio CSS** – använd aldrig `aspect-ratio` på `.media--blazy`-element
4. **Alla media-typer måste täckas i hero.css** – glöm inte `media--type-remote-video`
5. **Blazy Media Player = thumbnail + play, inte autoplay** – rätt beteende för hero
6. **MutationObserver på `is-playing`-klassen** är rätt sätt att detektera videouppspelning
7. **Slide-klick-navigering måste undanta media player** annars startar inte videon

---

**Version**: 2.0
**Uppdaterad**: 2026-02-26
**Författare**: Claude + Stefan
