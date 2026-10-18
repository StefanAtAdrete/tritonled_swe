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
