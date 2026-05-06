# TASK-036 — Kontaktformulär fungerar inte

**Status: TODO**
**Prioritet: Hög**

## Problem
`/sv/form/contact` fungerar inte.

## Att undersöka
- Är Contact-modulen aktiverad?
- Finns formuläret konfigurerat under `/admin/structure/contact`?
- Finns routing/behörighetsproblem för `/sv/`-prefixet?
- Skickas mail korrekt från DDEV/prod (SMTP)?

## Acceptanskriterier
- Formuläret laddas utan fel
- Meddelande kan skickas och bekräftelse visas
- Mail levereras på prod
