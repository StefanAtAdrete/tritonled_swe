# Tasks Directory

Alla uppgifter dokumenteras här enligt Task-Driven Workflow.

## Task Naming Convention

```
task-NNN-kort-beskrivning.md
```

**Exempel:**
- `task-001-hero-carousel.md`
- `task-002-product-listing-view.md`
- `task-003-browse-by-application.md`

## Sub-tasks

Om en task genererar sub-tasks, använd:
```
task-NNN-NN-beskrivning.md
```

**Exempel:**
- `task-001-01-hero-image-configuration.md` (sub-task till task-001)

## Task Lifecycle

1. **Not Started** - Task skapad men inte påbörjad
2. **In Progress** - DEFINE och PLAN godkända, IMPLEMENT pågår
3. **Blocked** - Väntar på något externt (beslut, data, etc)
4. **Completed** - Alla acceptanskriterier uppfyllda, dokumentation klar
5. **Failed** - Task kunde inte slutföras (dokumentera varför)

## CURRENT-TASK.md

Symlänk i `/docs/` som pekar på aktiv task. Claude läser denna automatiskt efter `00-START-HERE.md`.

**Uppdatera symlänk:**
```bash
cd /Users/steffes/Projekt/tritonled/docs
ln -sf tasks/task-NNN-beskrivning.md CURRENT-TASK.md
```

## Git Commit Format

**Huvudtask:**
```bash
git commit -m "[TASK-NNN] Beskrivning"
```

**Sub-task:**
```bash
git commit -m "[TASK-NNN-NN] Beskrivning"
```

## Template

Använd `TASK-TEMPLATE.md` som utgångspunkt för alla nya tasks.

```bash
cp /docs/tasks/TASK-TEMPLATE.md /docs/tasks/task-NNN-beskrivning.md
```

Fyll sedan i metadata och alla sektioner steg för steg.
