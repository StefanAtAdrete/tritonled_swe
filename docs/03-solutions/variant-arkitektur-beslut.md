# Variant-arkitektur: 55 000+ varianter i Drupal Commerce

**Datum**: 2026-04-03  
**Status**: Beslutad

---

## Beslutet

Vi hanterar INTE 55 000+ varianter som DB-rader i Drupal Commerce. Varianter genereras dynamiskt via SKU-logik i frontend/API.

---

## Vad vi redan har på plats

- ✅ 12 produkter med konfigurator-logik (JSON-schema per produkt)
- ✅ SKU genereras dynamiskt i frontend (JavaScript)
- ✅ Dummy-variation per produkt för cart-flödet (SKU: `CONFIGURATOR-{product_id}`)
- ✅ `field_configurator_sku` på order item type `qoute`
- ✅ JSON:API exponerat — produktdata tillgängligt för externa appar
- ✅ `field_configurator_schema` innehåller komplett attributstruktur per produkt

---

## Vad som saknas för fullständig implementation

- ❌ Prisdata via ERP/affärssystem-koppling (realtid)
- ❌ Lagerdata via ERP/affärssystem-koppling (realtid)
- ❌ Autentisering/auktorisering för partner-API-access

---

## Arkitektur

```
Drupal Commerce
├── 12 produkter (content + konfigurator-logik)
├── JSON-schema per produkt (attribut, kombinationer)
├── Dummy-variation per produkt (cart-flödet)
└── JSON:API → exponerar produktdata

Frontend/JS
└── SKU genereras dynamiskt från valda attribut

ERP/Affärssystem (framtida)
├── Prisdata (realtid via API)
└── Lagerdata (realtid via API)

Partner-API (framtida)
└── JSON:API + autentisering → extern app-integration
```

---

## Möjligheter detta öppnar upp

- App-kopplingar mot produktdata
- Säljstruktur via webben (offert → order → faktura)
- Partner-API med prisåtkomst (autentiserade användare)
- Dialux-integration (belysningsberäkning)
- CSV/Excel-export av konfigurerade produkter

---

## Relaterade dokument

- `/docs/03-solutions/konfigurator-arkitektur-beslut.md`
- `/docs/03-solutions/commerce-ajax-solution.md`
- `/docs/product-schemas/` (MAX, OPTI, SROW JSON-scheman)
