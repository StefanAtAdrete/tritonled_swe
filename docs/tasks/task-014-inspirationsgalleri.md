# TASK-014: Inspirationsgalleri / Lösningssida

**Status:** Not Started  
**Prioritet:** Låg  
**Beroenden:** Grundläggande webbstruktur klar

## Syfte
Skapa ett visuellt inspirationsgalleri som visar produkter i verkliga installationer och lösningar. Instagram-känsla med masonry grid av bilder.

## Bakgrund
Produktansvarig vill visa upp lösningar och installationer — bilder från verkliga projekt som inspirerar kunder och visar vad produkterna kan användas till.

## Krav
- Masonry/grid-layout med bilder (Instagram-känsla)
- Bilder klickbara → länk till produkt eller projekt
- Enkel administration via Drupal (ingen kodkunskap krävs)
- Mobilanpassad

## Föreslagen lösning (utreds)
- Content type: "Lösning" / "Installation" med bild, titel, koppling till produkt
- Views med masonry-layout via Bootstrap eller contrib
- Alternativ: Media-baserat galleri

## Att utreda
- Finns contrib-modul för masonry/gallery i Drupal 11?
- Ska det vara en egen sida eller del av startsidan?
- Ska bilder kunna taggas med produktkategori/serie?
