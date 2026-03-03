# TASK-013: Add-on / Tillbehörssystem

**Status:** Not Started  
**Prioritet:** Låg  
**Beroenden:** Grundläggande produktkatalog klar

## Syfte
Möjliggöra koppling mellan produktvarianter och tillhörande tillbehörsprodukter (t.ex. brackets, sensorer) så att rätt tillbehör föreslås på produktsidan.

## Bakgrund
Produktansvarig beslutade att brackets och tillbehör ska vara egna produkter, inte attribut. De säljs separat och anpassas vid projektgenomgång. På sikt ska de visas som förslag ("add-on") på produktsidan per variant.

## Krav
- Brackets och tillbehör är egna Commerce-produkter
- En variant ska kunna ha en eller flera kopplade tillbehörsprodukter
- Tillbehören visas som förslag på produktsidan ("Passar även med...")
- Kunden kan lägga till tillbehör separat i offerten

## Föreslagen lösning (utreds)
- Drupal field: entity reference från variation → tillbehörsprodukt
- Alternativ: Commerce product kit / related products
- Visning via Layout Builder block eller View

## Att utreda
- Finns contrib-modul för "related products" / "add-on products" i Commerce?
- Hur hanteras relationen i CSV-import?
