# TASK-042 — Flytta devel.settings till config_split

**Status: TODO**
**Prioritet: Hög**

## Problem
`devel.settings` ligger i `config/sync` men Devel-modulen är inte installerad på prod.
Det gör att `vendor/bin/drush cim --partial -y` misslyckas på prod vid varje deploy.

## Lösning
Flytta `devel.settings` (och övriga devel-relaterade config) till config_split local-split
så att de bara gäller i DDEV-miljön.

## Steg
1. Kontrollera vilka devel-configs som finns i `config/sync/`:
   `ls config/sync/ | grep devel`
2. Flytta dem till `config/split/local/` (eller motsvarande split-katalog)
3. Verifiera att `devel.settings` inte längre finns i `config/sync/`
4. Kör `ddev drush cex -y` och kontrollera att split är korrekt
5. Testa `vendor/bin/drush cim --partial -y` på prod

## Acceptanskriterier
- `cim --partial` på prod körs utan fel
- Devel fungerar fortfarande lokalt i DDEV
