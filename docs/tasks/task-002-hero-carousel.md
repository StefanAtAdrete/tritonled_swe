# TASK-002: Hero Carousel

**Created**: 2026-02-17
**Status**: In Progress
**Last Updated**: 2026-02-17
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

**Verktyg att verifiera per sub-task:**
- Config och admin UI → alltid FÖRSTA steget
- Contrib-moduler → sök innan du kodar
- Layout Builder → för placering
- Preprocess hooks → kräver godkännande
- Templates / SDC → kräver explicit godkännande

---

## 1. DEFINE

### Mål
Skapa en hero carousel-sektion för startsidan som visar media (bild/video),
rubrik, sammanfattningstext och CTA-knapp. Sektionen ska vara 100% bred,
4:1 ratio på desktop och 1:1 ratio på mobil.

### Syfte
- Ge besökare en visuell och säljande ingång till TritonLED's produkter
- Möjliggöra redaktionell kontroll via Drupal admin (lägg till/ta bort slides)
- Responsiv och performant (rätt bildstorlek per breakpoint)

### Design (referensbild: LumaIndustrial)
- Fullbredd hero med bakgrundsbild
- Overlay-text: tagline (liten grön text), rubrik (stor), brödtext, CTA-länk
- Carousel-indikatorer (streck/prickar) längst ner höger
- Mörk overlay på bilden för läsbarhet

### Acceptanskriterier
- [ ] Slides hanteras som eget content type (ej produkter) med titel, text, media, CTA
- [ ] Responsiva bildformat: 4:1 desktop, 1:1 mobil
- [ ] Carousel fungerar med Bootstrap (views_bootstrap redan installerat)
- [ ] Visas som block i Layout Builder på startsidan
- [ ] Minst 2 testslides skapade via admin
- [ ] Fungerar på desktop (≥768px) och mobil (<768px)
- [ ] Inga JS-errors i console

**Godkänt av Stefan**: ⏳ Väntar

---

## 2. SUB-TASKS (i ordning)

### SUB-TASK A: Verifiera befintligt innehåll
**Mål**: Bekräfta att befintliga Commerce-produkter räcker som datakälla för hero  
**Approach**: Kolla vilka fält produkter redan har (title, media, short description, CTA via URL)

**Befintliga fält att verifiera**:
- `title` – Produktnamn (rubrik)
- `field_product_media` – Media (bild/video)
- `field_short_description` – Sammanfattningstext
- Produktens URL som CTA

**Om fält saknas**: Lägg till på Commerce product type via admin UI  
**Verktyg**: Admin UI, `ddev drush cex`  
**Kräver kod?**: NEJ  
**Status**: ✅ Klar

---

### SUB-TASK B: Image styles för hero
**Mål**: Rätt bildstorlek per breakpoint (undvik att ladda 4000px-bild på mobil)  
**Approach**: Admin → Structure → Image styles  
**Styles som behövs**:
- `hero_desktop` – 1600×400px (4:1), Scale and crop
- `hero_mobile` – 600×600px (1:1), Scale and crop
- `hero_tablet` – 1024×256px (4:1), Scale and crop

**Responsive image style**: `hero_responsive` som mappar breakpoints → styles  
**Breakpoints**: Använder `tritonled_radix.breakpoints.yml`

**Verktyg**: Admin UI + config export  
**Kräver kod?**: NEJ – ren config  
**Status**: ✅ Klar — alla styles verifierade i databas

---

### SUB-TASK C: View mode "Hero" för Hero Slide
**Mål**: Definiera hur en Hero Slide renderas i carousel-kontexten  
**Approach**: Structure → Content types → Hero Slide → Manage display  
**View mode**: Skapa "Hero" view mode med rätt fältordning och formatters  
**Formatters**:
- `field_hero_media` → Responsive image (hero_responsive)
- `field_hero_tagline` → Plain text
- `field_hero_title` → Default
- `field_hero_body` → Plain text (trimmed)
- `field_hero_cta_text` + `field_hero_cta_url` → Länkfält

**Verktyg**: Admin UI  
**Kräver kod?**: NEJ  
**Status**: ✅ Klar — view modes skapade för commerce_product och media

---

### SUB-TASK D: View "Hero Carousel" (block)
**Mål**: Samla Hero Slides och rendera dem som Bootstrap carousel  
**Approach**: Admin → Structure → Views  
**Verktyg**: Admin UI + views_bootstrap  
**Kräver kod?**: Minimal JS för Bootstrap 4→5 kompatibilitet  
**Status**: ✅ Klar

**Lärdomar**:
- views_bootstrap genererar Bootstrap 4-attribut (data-ride, data-slide)
- Bootstrap 5 kräver data-bs-* prefix
- Lösning: Bootstrap compat behavior i global.js
- Image styles genereras inte automatiskt on-demand i DDEV/nginx – kör manuellt vid behov:
```bash
ddev drush php:eval "
\$styles = ['hero_desktop', 'hero_tablet', 'hero_mobile'];
foreach (\$styles as \$style_id) {
  \$style = \Drupal\image\Entity\ImageStyle::load(\$style_id);
  \$destination = \$style->buildUri('public://[PATH/TO/FILE]');
  \$result = \$style->createDerivative('public://[PATH/TO/FILE]', \$destination);
  echo \$style_id . ': ' . (\$result ? 'OK' : 'FAIL') . '\n';
}
"
```

---

### SUB-TASK E: Placering i Layout Builder
**Mål**: Lägga carousel-blocket i startsidans layout  
**Approach**: Layout Builder på startsidan (Basic page eller custom frontpage)  
**Obs**: Blocket ska vara fullbredd – kräver rätt section-inställning i Layout Builder

**Verktyg**: Layout Builder admin UI  
**Kräver kod?**: Möjligen minimal CSS för fullbredd + overlay  
**Status**: ⏳ Ej påbörjad

---

### SUB-TASK F: Styling (overlay, text, responsiv ratio)
**Mål**: Matcha designen – mörk overlay, vit text, rätt proportioner  
**Approach**: Bootstrap klasser FÖRST, sedan minimal custom CSS om nödvändigt  
**Att lösa**:
- 4:1 ratio desktop, 1:1 mobil → CSS aspect-ratio på container
- Mörk overlay → Bootstrap `bg-dark bg-opacity-50` eller CSS
- Textplacering → Bootstrap positioning classes

**Verktyg**: Bootstrap utility classes + eventuellt minimal CSS i `css/components/`  
**Kräver kod?**: Möjligen minimal CSS – kräver godkännande  
**Status**: ⏳ Ej påbörjad

---

## 3. PLAN

**Beslutsträd**: `/docs/DRUPAL-DECISION-TREE.md`

**Ordning**:
A → B → C → D → E → F

**Godkänt av Stefan**: ⏳ Väntar

---

## 4. IMPLEMENT

*(Fylls i per sub-task efter godkännande)*

---

## 5. VERIFY

*(Fylls i efter implementation)*

---

## 📝 Lärdomar att ta med

- Dela alltid upp huvuduppgifter i sub-tasks innan implementation
- Verifiera alltid om content type / media type behöver skapas INNAN views
- Image styles måste finnas INNAN view modes konfigureras
- views_bootstrap finns och är aktiverat – använd det innan custom carousel
- Befintlig `views.view.hero.yml` visar produkter (inte slides) – behöver ny approach

---

**Version**: 1.0
**Skapad**: 2026-02-17
**Författare**: Claude + Stefan
