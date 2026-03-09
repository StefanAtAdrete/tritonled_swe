# Variation Gallery och Commerce variantmatchning

**Uppdaterad**: 2026-03-08  
**Status**: Rotorsak identifierad — lösning pending (TASK-015)

---

## Problemet

Galleriet visar fel bild när användaren byter Längd medan Connection är vald.
Exempel: EnstoNet vald → byter Längd → galleriet visar CG-bild.

## Rotorsak

**Commerce's hierarkiska variantmatchning** — inte galleriets rendering.

Attributordningen i formuläret (Manage form display) bestämmer hur Commerce
matchar varianter. Om Length ligger före Connection i ordningen:

1. Användaren väljer EnstoNet
2. Användaren byter Length
3. Commerce matchar närmaste variant med den längden — uppifrån i attributordningen
4. Hittar CG (default/första Connection) → byter till CG-varianten
5. Galleriet visar korrekt CG-bilden för den nu valda CG-varianten

**Galleriet gör rätt** — det visar bilden för den variant Commerce faktiskt valt.

## Vad som INTE löser problemet

- ❌ `gallery` view mode + ReplaceCommand — löser rendering men inte variantmatchning
- ❌ JS re-init av Splide/Blazy — galleriet renderar redan korrekt
- ❌ Attributordning Connection → Length — löser matchningen men ger dåligt UX

Se: `/docs/03-solutions/gallery-ajax-fel-spaar.md` för detaljerad felsökningshistorik.

## Temporär lösning (testad 2026-03-08)

Sätt Connection FÖRST i attributordningen (Manage form display):
1. Connection
2. Length
3. Watt
4. Driver

**Konsekvens**: Användaren måste välja Connection innan Längd — dåligt UX för elektriker.

## Verklig lösning: TASK-015 JSON:API-konfiguratorn

En custom variant-konfigurator som matchar varianter på hela kombinationen av
valda attribut — oberoende av ordningen. Commerce-arkitekturen berörs inte.

Se: `/docs/tasks/task-015-variant-konfigurator.md`

---

**Datum**: 2026-03-08  
**Författare**: Stefan + Claude
