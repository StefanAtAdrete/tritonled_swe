# TASK-056 — Category C Expansion (Master)

**Created**: 2026-05-20
**Status**: In Progress (umbrella)
**Last Updated**: 2026-05-20
**Related Tasks**: TASK-029 (Produkttyper & fältstruktur), TASK-057, TASK-058, TASK-059, TASK-060

---

## Syfte

Utöka Category C-katalogen med nya produkttyper och produkter från externa leverantörer.
Leverantör läggs i `field_producer` per produkt — inte som kategori.

Denna task innehåller ingen egen IMPLEMENT-fas. Allt arbete sker i sub-tasks.

---

## Kontext

- TASK-029 Fas 3 (feeds + CSV-mallar) är **inte klar** — löses parallellt i sub-tasks
- Befintliga produkttyper: `linear_led`, `highbay`, `floodlight`, `high_mast`, `street_area`, `ex_hazardous`, `accessories`
- Nya produkttyper att skapa: `wall_light`, `bollard`
- `batten` = samma som `linear_led` — inga nya typer behövs
- Bilder hanteras manuellt som sista steg i varje sub-task

## Arbetsflöde per sub-task

Se skill: `/docs/skills/category-c-setup/SKILL.md`

1. Taxonomy term (EN + SV)
2. Commerce product type + variation type (kopiera fältstruktur från befintlig typ)
3. CSV-mall (se skill: `/docs/skills/category-c-product-import/SKILL.md`)
4. Feeds (products-feed + variations-feed)
5. Datainmatning från PDF → CSV
6. Testimport (1–2 produkter)
7. Full import
8. Bilder — manuellt sist (media_bulk_upload + VBO)

---

## Sub-tasks

| Task | Typ | Produktserier | Status |
|---|---|---|---|
| TASK-057 | `wall_light` (ny) | WL183, WL195, WL199, WL186A/B | Not Started |
| TASK-058 | `bollard` (ny) | WL203A/B, NL203-50/100, WL196, NL196-600/900 | Not Started |
| TASK-059 | `linear_led` (befintlig) | DB218 Batten | Not Started |
| TASK-060 | Övriga UpShine | AL228 (Ceiling), ML49 (Vanity), KL11 (Cabinet) | Not Started |

---

## Leverantör: UpShine (via Ivan Wang)

- Prisfil: `Up-Ivan price 260518.xlsx` (inpriser, ex frakt/moms)
- Katalog: `UPSHINE new product collection Q1 2026.pdf`
- Prisformel rek. utpris: `(Inpris × 1,18 + 5% omkostn) × 1,9`
- Rabatt elektriker/installatör: 20–35% beroende på produkt och antal
- Filer ligger i SharePoint: Container list PDF + Priser (länk i mail från Laurits 2026-05-19)

---

## Loggbok

- **2026-05-20** Master-task skapad. Produktinfo från Laurits mail 2026-05-19. Startar TASK-057.
