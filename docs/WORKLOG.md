# Arbetslogg

Append-only logg för arbete som faktiskt har genomförts. Planer och idéer hör hemma i `CURRENT-TASK.md`; bestående beslut hör hemma i `DECISIONS.md`.

## Postformat

```text
## YYYY-MM-DD – Kort rubrik
- Agent/modell:
- Task/PR:
- Commit:
- Miljö: lokal | staging | produktion
- Utfört:
- Verifierat:
- Återstår:
- Ej verifierat:
```

## 2026-10-18 – Produktionsrepo reconcilerat

- Agent/modell: Codex
- Task/PR: PR #2 och #3
- Commit: `8872391404cf965a3a619e83dfb89b5713f8b9a8`
- Miljö: GitHub och produktion
- Utfört: GitHub `main` och produktionscheckout synkroniserades. Composer-genererade Drupal-beroenden slutade versionshanteras. En exponerad historisk token ersattes med redigerad text.
- Verifierat: ren Git-status på produktion, samma HEAD som `origin/main`, Drupal 11.4.10, inga väntande databasuppdateringar, inga Composer-advisories och fem centrala URL:er med HTTP 200.
- Återstår: standardiserad deploykedja samt reconciliation av Drupal config-drift.
- Ej verifierat: automatiserad återställning i skarp incident.

## 2026-10-18 – Deploy- och LLM-standard påbörjad

- Agent/modell: Codex
- Task/PR: deploy-standardisering
- Commit: se associerad PR för `codex/deploy-standardization`
- Miljö: ren worktree från GitHub `main`
- Utfört: gemensamma agentregler, arbetslogg, beslutslogg, projektvalidering och manuell produktionsworkflow skapades.
- Verifierat: fylls i efter test.
- Återstår: konfigurera GitHub Environment och SSH-hemligheter; verifiera första manuella deployen.
- Ej verifierat: deployworkflowen mot produktion.

## 2026-10-18 – Första standardiserade produktionsdeployen verifierad

- Agent/modell: Codex
- Task/PR: PR #4, PR #5, Actions run `39129837094`
- Commit: `2d91ffc61076b668e0ff632c5980702430889758`
- Miljö: GitHub Actions och produktion
- Utfört: GitHub Environment `production` konfigurerades med en dedikerad SSH-nyckel. Nyckeln begränsades till `deploy <commit-SHA>` och commits som ingår i `origin/main`. Första manuella deployen kördes.
- Verifierat: Actions-resultat success; produktions-HEAD matchar commit; Git-status ren; underhållsläge av; inga väntande databasuppdateringar; backupens Git-bundle och databasdump godkända; fem centrala URL:er svarar HTTP 200; DB218 har en produkt och fem skyddade variationer; feed 21 är inaktiv.
- Återstår: reconcila Drupal config-drift i en separat, backupad uppgift och senare utföra fördjupad säkerhetsgranskning.
- Ej verifierat: skarp rollback efter ett verkligt deployfel; endast rollbacklogik och backupunderlag är verifierade.

## 2026-10-19 – Tjänstevallgrav och masterlistestrategi dokumenterad

- Agent/modell: Codex
- Task/PR: strategidokumentation
- Commit: se associerad PR för `codex/document-service-moat`
- Miljö: dokumentation
- Utfört: affärspositionering, tjänstepaketet Match/Verify/Ready/Care, masterlistans datalager, webbkonsekvenser, manuell pilot, mätetal och öppna beslut dokumenterades.
- Verifierat: omvärldspremisser kontrollerades mot Elsäkerhetsverket, Naturvårdsverket, Upphandlingsmyndigheten och EU-kommissionen 2026-10-19.
- Återstår: verksamhetsbeslut om målgrupp, erbjudande, ansvar, prissättning och pilotfall.
- Ej verifierat: kundernas betalningsvilja, leveranslöften, serviceekonomi och faktisk konvertering.
