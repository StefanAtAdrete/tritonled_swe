# CURRENT TASK

## Deploy- och LLM-standardisering

**Status**: Pågår
**Branch**: `codex/deploy-standardization`

### Mål

- GitHub `main` ska vara kodens sanningskälla.
- Produktion ska deployas manuellt och spårbart via GitHub Actions.
- Olika LLM-agenter ska följa samma instruktioner och lämna samma typ av handoff.

### Genomfört

- Gemensam `AGENTS.md` samt tunna Claude/Gemini-ingångar.
- Append-only `docs/WORKLOG.md` och `docs/DECISIONS.md`.
- Lokal/CI-validering i `scripts/validate-project.sh`.
- Manuell produktionsworkflow med backup, spärrar, smoketest och rollback.

### Kvar

1. Testa och merga PR.
2. Skapa GitHub Environment `production` och lägga in dedikerade SSH-secrets.
3. Köra första manuella deployen.
4. Reconcila Drupal config-drift i en separat, backupad uppgift.

---

## Parallella spår

### Offertsystem (TASK-043 master)
- **TASK-044** — Kundkonton + företagsprofil: DEFINE klar, 5 frågor obesvarade

### Category C Expansion (TASK-056 master)
- **TASK-057** — wall_light ← AKTIV
- **TASK-058** — bollard (efter 057)
- **TASK-059** — linear_led / Batten DB218
- **TASK-060** — Övriga UpShine

---

## Backlog
- TASK-040 — Robot/AI-agentoptimering
- TASK-041 — SEO och innehåll
- TASK-042 — Devel/config_split-fix

---

## Skills skapade idag (2026-05-20)
- `/docs/skills/category-c-setup/SKILL.md` — ny produkttyp steg-för-steg
- `/docs/skills/category-c-product-import/SKILL.md` — PDF → CSV → feeds → bilder
