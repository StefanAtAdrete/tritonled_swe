# TritonLED – gemensamma agentregler

Den här filen är projektets gemensamma instruktion för alla LLM-agenter. Verktygsspecifika filer får komplettera, men inte motsäga, dessa regler.

## Starta alltid här

1. Läs `AGENTS.md`.
2. Läs `docs/CURRENT-TASK.md` och senaste posten i `docs/WORKLOG.md`.
3. Kontrollera `git status --short`, aktuell branch och commit.
4. Arbeta aldrig i en smutsig checkout utan att först skilja användarens ändringar från uppgiften. Använd en ren worktree vid osäkerhet.
5. Kontrollera Drupal-status innan configarbete: `ddev drush status` och `ddev drush config:status`.

## Sanningskällor

- GitHub `main`: kod, Composer-låsning, Drupal YAML-config och dokumentation.
- Produktionsdatabasen: innehåll, översättningar, Layout Builder-overrides, orderdata och blockinnehåll.
- `web/sites/default/files`: uppladdade filer; dessa ligger inte i Git.
- `docs/CURRENT-TASK.md`: vad som pågår nu.
- `docs/WORKLOG.md`: append-only historik över verifierat arbete.
- `docs/DECISIONS.md`: beslut som ska överleva byte av agent eller modell.

## Ändringsregler

- Gör små, avgränsade commits på branch. Ändra inte direkt på `main`.
- Rör inte hemligheter, produktionsdata eller användarens orelaterade ändringar.
- Kör aldrig generell `drush config:import` när `config:status` visar oförklarad drift.
- Feed 21 (`tritonled_linled_products`) ska vara inaktiv tills importen har korrigerats och verifierats.
- Produktionsdeploy sker endast via den manuella GitHub Actions-workflowen och endast från `main`.
- Deployworkflowen får inte kringgå spärren för `config/sync`.
- Destruktiva databasåtgärder kräver aktuell backup och uttryckligt godkännande.

## Dokumentation före handoff

Varje agent ska innan handoff:

1. Uppdatera `docs/CURRENT-TASK.md` med läge och nästa konkreta steg.
2. Lägga till en post i `docs/WORKLOG.md` med datum, agent/modell, commit, miljö, utfört och verifiering.
3. Uppdatera `docs/DECISIONS.md` om ett bestående tekniskt eller redaktionellt beslut tagits.
4. Redovisa tydligt: utfört, verifierat, återstående och ej verifierat.

## Minimiverifiering

- `composer validate --strict`
- `composer audit --locked`
- `bash scripts/validate-project.sh`
- Relevanta funktions- och språkflöden testas lokalt.
- Efter deploy: HTTP 200 på startsida, svensk produktsida, engelsk produktsida, kontaktformulär och login.

## Språk

- Frontend och projektdokumentation: svenska.
- Kod, variabelnamn och kodkommentarer: engelska.
- Admin/backend: Drupal-standard på engelska.
