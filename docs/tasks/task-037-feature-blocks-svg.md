# TASK-037 — Feature-block med SVG-ikoner

**Status: TODO**
**Prioritet: Hög**

## Beskrivning
3–4 block som lyfter fram TritonLEDs viktigaste produktegenskaper med minimala, snygga SVG-ikoner.
Innehållet hämtas/inspireras från node/12 (artikeltexten om MAX-serien).

## Exempel på features att lyfta
1. Belysning med hög kvalitet (DALI-2, upp till 6500K)
2. Modulära armaturer med utbytbara delar
3. Håller över tid (IP20–IP43, robust)
4. Enkel och snabb installation (Wago, monteringsfäste ingår)

## Approach (beslutsträd)
- Custom Block type med fält: ikon (SVG/media), rubrik, brödtext
- Alternativt: hårdkodade block i Layout Builder med Bootstrap-ikoner eller inline SVG
- Placering: Layout Builder-sektion på node/12 eller som återanvändbart block

## Att besluta
- Ska ikonerna vara Bootstraps egna (Bootstrap Icons) eller custom SVG?
- Ska blocken vara redigerbara i Drupal UI eller hårdkodade?
- Ska de återanvändas på flera sidor?

## Acceptanskriterier
- 3–4 feature-block visas på node/12
- Minimala, moderna SVG-ikoner
- Responsiv layout (Bootstrap grid)
- Redigerbart i Drupal UI
