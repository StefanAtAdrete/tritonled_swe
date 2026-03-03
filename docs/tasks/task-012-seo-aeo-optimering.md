# Task 012: SEO/AEO-optimering för produktkatalog

**Created**: 2026-03-01  
**Status**: Not Started  
**Last Updated**: 2026-03-01  
**Related Tasks**: TASK-011 (specifications tabs)

---

## 1. DEFINE

### Mål
Implementera SEO/AEO-optimering för produkter och varianter så att TritonLED
syns i både traditionella sökmotorer (Google) och AI-sökmotorer (AEO).

### Syfte
B2B-köpare söker på specifika tekniska egenskaper ("LED linjärarmatur 500mm 14W IP20").
Korrekt metadata och produkttexter ökar chansen att hitta rätt produkt via sökning.

### Beslut från resonemang (2026-03-01)

**Produkttexter:**
- Skrivs på **produktnivå** (inte per variant) — varianter delar produktsida/URL
- Kan skapas manuellt (20-30 produkter är hanterbart)
- Alternativt: AI-generering från CSV före import (~75-100 SEK för hela katalogen)
- Variant-specifik text ger marginellt SEO-värde utan unika URL:er

**URL-struktur:**
- Produktsidan är canonical — varianter är "states" av samma sida
- Unika URL:er per variant via pathauto är möjligt men ger duplicate content-risk utan unik text
- Beslut: Produktnivå-URL + korrekta meta-tags per variant via tokens

**Kostnad AI-generering:**
- Claude Sonnet: ~$7-9 (75-100 SEK) för 4000-5000 varianter
- Rationellt att göra före import om AI används

### Acceptanskriterier
- [ ] Metatag-modulen konfigurerad för commerce_product och commerce_product_variation
- [ ] Title-tag per variant via token (produktnamn + nyckelattribut)
- [ ] Meta description per produkt (manuell eller AI-genererad)
- [ ] Pathauto konfigurerad för produkter med logisk URL-struktur
- [ ] Structured data (Schema.org Product) på produktsidor
- [ ] Grundläggande AEO: tydlig H1, specs i semantisk HTML

**Godkänt av Stefan**: ⏳ Väntar

---

## 2. PLAN

### Beslutsträd
Config → Contrib modules (Metatag, Pathauto, Schema.org) → Custom

### Vald lösning
**Approach**: Contrib modules + Config  
**Specifik lösning**:
1. Metatag — meta title/description med tokens per entitetstyp
2. Pathauto — URL-alias för produkter (t.ex. `/produkter/[product:title]`)
3. Schema.org / Simple Sitemap — strukturerad data och sitemap
4. Produkttexter — manuellt eller AI-batch före import

### Moduler (redan installerade att undersöka)
- `metatag` — troligen redan installerad
- `pathauto` — troligen redan installerad
- `simple_sitemap` — kontrollera

### Alternativ övervägda
1. **Unika URL:er per variant** — möjligt via pathauto men duplicate content-risk, väljs bort
2. **AI per variant** — för kostsamt i relation till SEO-värdet, väljs bort

**Godkänt av Stefan**: ⏳ Väntar

---

## 3. IMPLEMENT

*Påbörjas efter godkännande*

---

## 4. VERIFY

*Påbörjas efter implementation*

---

## 5. COMPLETION

*Påbörjas efter verifiering*
