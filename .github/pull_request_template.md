## Syfte

Beskriv kort vilket problem PR:n löser.

## Omfattning

- [ ] Kod
- [ ] Drupal config
- [ ] Databas/innehåll
- [ ] Dokumentation
- [ ] Infrastruktur/deploy

## Verifiering

- [ ] `bash scripts/validate-project.sh`
- [ ] Relevant funktion testad lokalt
- [ ] Svenska och engelska flöden kontrollerade när de påverkas
- [ ] Inga hemligheter eller genererade beroenden har lagts till

## Drupal config

- Resultat från `ddev drush config:status`:
- Om `config/sync` ändras: beskriv separat, backupad produktionsplan. Den vanliga deployworkflowen stoppar dessa ändringar.

## Handoff

- [ ] `docs/CURRENT-TASK.md` uppdaterad
- [ ] Post tillagd i `docs/WORKLOG.md`
- [ ] `docs/DECISIONS.md` uppdaterad om ett bestående beslut togs

## Produktionsrisk och rollback

- Risk:
- Förväntad påverkan:
- Rollback:
