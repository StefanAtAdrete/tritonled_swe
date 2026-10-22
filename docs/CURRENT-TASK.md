# CURRENT TASK

## Akut PHP- och Drupal-säkerhetsuppdatering

**Status**: Slutförd och verifierad i produktion 2026-10-22
**Branch**: `codex/security-2026-10-22`

### Genomfört

- VPS-snapshot skapad före ändring.
- PHP 8.4 uppdaterad till 8.4.27 och PHP 8.3 till 8.3.36 på VPS:en.
- Drupal core låst till 11.4.11.
- `media_bulk_upload` låst till 3.0.6 och `simple_oauth` till 6.1.3.
- Composer-audit och projektvalidering godkända utan säkerhetsråd.

### Verifierat

- Produktionscommit `e6ace3b5abace494bc53549bda1cc30eff41c862` och ren Git-status.
- Drupal 11.4.11, PHP 8.4.27, databas ansluten och inga väntande databasuppdateringar.
- Composer-audit utan säkerhetsråd.
- Startsida, svenska och engelska produktsidor, kontaktformulär och login svarar HTTP 200.
- Underhållsläge av och ingen omstart krävs.

## Tjänstevallgrav och masterlistor

**Status**: Strategiförslag dokumenterat 2026-10-19
**Dokument**: `docs/strategy/tritonled-service-moat.md`

### Nästa beslut

1. Välj första kundsegment för en manuell pilot.
2. Välj fem verkliga kundförfrågningar och ett begränsat godkänt produktsortiment.
3. Fastställ vem som godkänner teknisk data, prisbok och publicering.
4. Testa Match → Verify → Ready manuellt innan mer automation byggs.

### Inte beslutat

- Slutligt tjänstenamn, prissättning eller garantivillkor.
- Automatisk publicering från Excel/masterlista.
- QuickCalc som slutlig verifieringsmetod.

---

## Deploy- och LLM-standardisering

**Status**: Slutförd 2026-10-18
**Produktionscommit**: `2d91ffc61076b668e0ff632c5980702430889758`

### Mål

- GitHub `main` ska vara kodens sanningskälla.
- Produktion ska deployas manuellt och spårbart via GitHub Actions.
- Olika LLM-agenter ska följa samma instruktioner och lämna samma typ av handoff.

### Genomfört

- Gemensam `AGENTS.md` samt tunna Claude/Gemini-ingångar.
- Append-only `docs/WORKLOG.md` och `docs/DECISIONS.md`.
- Lokal/CI-validering i `scripts/validate-project.sh`.
- Manuell produktionsworkflow med backup, spärrar, smoketest och rollback.

### Verifierat

- PR #4 och #5 mergade med grön CI.
- GitHub Environment och command-begränsad deploynyckel aktiva.
- Första manuella deployen lyckades.
- Produktionsbackup validerad och live-smoketest godkänt.

### Nästa prioritet

1. Reconcila Drupal config-drift i en separat, backupad uppgift.
2. Genomför fördjupad säkerhetsgranskning av deploykedjan.
3. Testa rollback kontrollerat innan deployen får startas automatiskt vid merge.

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
