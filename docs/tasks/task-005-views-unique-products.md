# Task 005: Visa unika produkter i Hero och Featured Products

**Created**: 2026-02-22  
**Status**: Not Started  
**Last Updated**: 2026-02-22  
**Related Tasks**: TASK-002 (hero-carousel), TASK-004 (splide)

---

## 1. DEFINE

### Mål
Hero-karusellen och Featured Products-blocket ska visa varje produkt exakt en gång, med bild hämtad från default-variation.

### Syfte
Commerce-produkter har varianter (watt, CCT etc.) med egna mediafält. Om man kopplar variation-media direkt i en view utan relationship visas produkten lika många gånger som det finns varianter med media. Vi vill ha en rad per produkt med bild från default-variationen.

### Acceptanskriterier
- [ ] Hero visar varje produkt max 1 gång
- [ ] Featured Products visar varje produkt max 1 gång
- [ ] Produktbild visas (från default-variation)
- [ ] Inga dubletter oavsett antal varianter

**Godkänt av Stefan**: ⏳ Väntar

---

## 2. PLAN

### Beslutsträd
**Fil**: `/docs/01-decision-trees/commerce-decision-tree.md`  
**Steg**: Config → Views relationships

### Vald lösning
**Approach**: Config — Views relationship  
**Specifik lösning**: Lägg till relationship **"Product variation (default)"** i Views. Den hämtar automatiskt bara default-variationen per produkt (1 rad per produkt). Sedan lägg till `field_media` från variation via den relationen.

### Motivering
Inbyggd Commerce-funktion, ingen custom kod. Löser dubblett-problemet på rätt nivå.

### Alternativ övervägda
1. **DISTINCT i query** - Tar bort dubletter men ger inte tillgång till variation-fält
2. **Aggregation** - Komplex, svår att underhålla
3. **Custom preprocess** - Onödig custom kod när Views löser det

**Godkänt av Stefan**: ⏳ Väntar

---

## 3. IMPLEMENT

### Steg

1. **Featured Products — lägg till relationship**
   - Views UI: `/admin/structure/views/view/featured_products`
   - Relationships → Add → "Product variation (default)"
   - Alias: `default_variation`
   - Lägg till fält: `field_media` via relationen (hidden)
   - Uppdatera Custom text-tokenen: `{{ field_media }}`
   - Git commit: `[TASK-005] Add default variation relationship to featured_products view`

2. **Hero — lägg till relationship**
   - Views UI: `/admin/structure/views/view/hero`
   - Relationships → Add → "Product variation (default)"
   - Uppdatera carousel image-inställning till variation-mediafältet
   - Git commit: `[TASK-005] Add default variation relationship to hero view`

3. **Export config**
   ```bash
   ddev drush cex -y
   git commit -m "[TASK-005] Export config for views relationships"
   ```

---

## 4. VERIFY

### Testresultat
- [ ] Featured Products: 3 produkter visas, inte 9+ rader
- [ ] Hero: 3 slides, inte dubletter
- [ ] Produktbild syns på varje kort/slide
- [ ] Klick på produkt leder till rätt produktsida

---

## 5. COMPLETION

### Lärdomar
- Commerce Views: Använd alltid "Product variation (default)" relationship för att undvika dubletter
- Variation-fält nås via relationship, aldrig direkt på product-tabellen

### Nästa steg
- Styling av Featured Products-korten (produktbild + titel + series)
- Hero-karusellen styling och innehåll
