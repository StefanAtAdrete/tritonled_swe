# TASK-040 — Robot/AI-agentoptimering

**Status: TODO**
**Prioritet: Medel**

## Beskrivning
Optimera sajten för sökmotorer och AI-agenter (LLM:er som Perplexity, ChatGPT, Claude).

## Deluppgifter

### Sökmotorer
- `robots.txt` — korrekt konfigurerad
- XML Sitemap — installera `simple_sitemap` eller liknande
- Verifiera Google Search Console

### Structured Data / Schema.org
- `Product` schema på produktsidor (namn, beskrivning, bild, kategori)
- `Organization` schema i footer
- `BreadcrumbList` schema
- Modul: `schema_metatag` (contrib)

### AI-agenter (AEO — Answer Engine Optimization)
- `llms.txt` — en konventionsfil för AI-agenter (beskriver sajten, API-endpoints)
- JSON:API redan aktiverat → dokumentera publika endpoints
- TASK-034 (REST API) kopplas hit
- Tydliga `meta description` per sida

### Tekniskt
- Canonical URLs
- Hreflang för SV/EN
- Open Graph-taggar (sociala medier + AI-förhandsvisningar)

## Acceptanskriterier
- XML Sitemap tillgänglig och submittad
- Schema.org Product på Commerce-produkter
- `llms.txt` på rotnivå
- Hreflang korrekt för SV/EN
