# SOP: Sessionshantering

**Version**: 2.0
**Uppdaterad**: 2026-04-21
**Syfte**: Säker arbetsrutin för Drupal-sessioner med tydliga guardrails

---

## Varför detta dokument finns

Drupal har **två sanningskällor** som måste hållas i synk:

| Sanningskälla | Innehåller | Verktyg |
|---------------|------------|---------|
| **Databas** | Content, translations, Layout Builder-overrides, Block-content, orders | Backup & Migrate / snapshot |
| **Config-filer** | Fältdefinitioner, views, block-config, tema-config, roles, image styles | `cex`/`cim` + git |

Om de hamnar ur synk kan `cex` radera hundratals config-filer. Det har hänt. Det är smärtsamt.

---

## DEL 0 — INVENTERING (ALLTID FÖRST)

Innan någon kod skrivs, något ändras eller något importeras — **inventera läget**.

### Steg 1: Starta miljön
```bash
ddev start
ddev drush status
```

### Steg 2: Kontrollera config-delta
```bash
ddev drush config:status
```

**Tolka resultatet:**

| Status | Betydelse | Åtgärd |
|--------|-----------|--------|
| `No differences` | DB och YAML är i synk | ✅ Säkert att arbeta |
| `Only in sync dir` | YAML har config som DB saknar | ⚠️ Se nedan |
| `Only in active` | DB har config som YAML saknar | ⚠️ Se nedan |
| `Different` | Konflikter mellan DB och YAML | ⚠️ Se nedan |

### Steg 3: Kategorisera planerat arbete

Bestäm INNAN du börjar vad dagens arbete berör:

| Typ | Exempel | Risk |
|-----|---------|------|
| **Ren DB-ändring** | Lägga till content, redigera produkter, Layout Builder-layout | Låg — påverkar inte config-filer |
| **Ren config-ändring** | Nytt fält, ny view, ändra image style | Medel — kräver `cex` efteråt |
| **Blandat** | Skapa fält (config) + importera innehåll (DB) | Hög — kräver tydlig ordning |

### Steg 4: Hantera delta innan arbete

**Om `Only in sync dir` finns:**
```bash
# Alternativ A: Importera det som saknas i DB (vanligast rätt)
ddev drush cim --partial -y

# Alternativ B: Ta bort filen från YAML (om den är föråldrad)
git rm config/sync/[filnamn].yml
```
⚠️ **KÖR ALDRIG `cex` om `Only in sync dir` finns utan att förstå varför.**

**Om `Only in active` finns:**
```bash
# DB har config som saknas i YAML — exportera det
ddev drush cex -y
```

**Om `Different` finns:**
```bash
# Granska skillnaderna
ddev drush config:status --state=Different
# Besluta per fil: vad är rätt version? DB eller YAML?
```

---

## DEL 1 — SESSIONSSTART

### Claude gör:
1. Läser `/docs/00-START-HERE.md`
2. Läser `/docs/CURRENT-TASK.md`
3. Presenterar: var vi är, öppna tasks, förslag på vad vi tar tag i

### Gemensam bekräftelse:
- Vilken task arbetar vi med idag?
- Vilken typ av arbete är det? (DB / Config / Blandat)
- Finns task-fil? Om inte → skapa från `TASK-TEMPLATE.md`
- Behövs backup innan vi börjar?

---

## DEL 2 — EXECUTION (Regler under arbetet)

### Ordningsregler

1. **Config-ändringar exporteras ALLTID sist** — aldrig mitt i ett arbetsflöde
2. **Blanda aldrig** DB-arbete och config-arbete i samma steg utan checkpoint emellan
3. **Snapshot INNAN** destructiv DB-manipulation (källspråksbyte, content-radering, feeds-import)
4. **`cex` alltid EFTER** `config:status` visar `No differences` eller `Only in active`

### Fel att undvika

| ❌ Gör inte | ✅ Gör istället |
|-------------|----------------|
| `cex` direkt efter prod-DB-import | `config:status` → `cim --partial` → sedan `cex` |
| Blanda fältbygge och content-import i samma steg | Fält först + `cex` → sedan content-import |
| Anta att config är ren efter `cr` | Kör alltid `config:status` för att verifiera |
| Skapa YAML-filer manuellt utan DB-motsvarighet | Skapa via `drush php:eval` → sedan `cex` |

### Godkännandekedja (kräver Stefans OK)

```
Inventering → Plan → Stefan OK → Implementation → Checkpoint → Stefan OK → Nästa steg
```

Claude presenterar alltid **plan** och **typ av ändring** innan något görs.

---

## DEL 3 — CHECKPOINT (mitt i session)

### När triggas checkpoint?
- ✅ En task markeras som klar
- ✅ Vi byter inriktning/task
- ✅ Innan destructiv operation
- ⏱️ Lång session (>1h) utan naturligt avbrott

Claude säger: *"✅ [TASK-NNN] klar. Dags för checkpoint — kör vi det nu?"*

### Checkpoint-steg (i exakt ordning):

**1. Verifiera config-läge**
```bash
ddev drush config:status
```
Om inte `No differences` eller `Only in active` → lös delta INNAN export.

**2. Exportera config**
```bash
ddev drush cex -y
```

**3. Granska vad som ändrades**
```bash
git diff --stat
git diff config/sync/
```
Ser du oväntade raderingar? → Stoppa, undersök innan commit.

**4. Git commit**
```bash
git add -A
git commit -m "[TASK-NNN] Checkpoint: vad som är klart"
```

**5. Uppdatera CURRENT-TASK.md**

**6. Backup & Migrate snapshot** (om DB-ändringar gjorts)

---

## DEL 4 — SESSIONSSLUT

**1. Inventera läget**
```bash
ddev drush config:status
```

**2. Exportera config**
```bash
ddev drush cex -y
```

**3. Granska diff**
```bash
git diff --stat
```

**4. Uppdatera dokumentation**
- `CURRENT-TASK.md` — status, vad återstår, lärdomar
- `00-START-HERE.md` — om nya viktiga beslut tagits
- Ev. ny fil i `/docs/03-solutions/` om nytt problem lösts

**5. Commit + push**
```bash
git add -A
git commit -m "[TASK-NNN] Session slut: sammanfattning"
git push origin main
```

**6. Snapshot**

**7. Deploy till prod (om redo)**
```bash
cd /home/tritonled/htdocs/tritonled.se
git pull
vendor/bin/drush config:status
vendor/bin/drush cim --partial -y
vendor/bin/drush cr
```

---

## Återställning: om `cex` raderade filer

```bash
# Återställ alla raderade filer från föregående commit
git diff HEAD~1 --diff-filter=D --name-only | xargs git checkout HEAD~1 --
git commit -m "[TASK-NNN] Restore accidentally deleted config files from cex"
git push
```

---

## Snabbreferens: Vad dokumenteras var?

| Vad | Var |
|-----|-----|
| Pågående task, status | `CURRENT-TASK.md` |
| Kritiska regler | `00-START-HERE.md` |
| Lösningar/arkitektur | `/docs/03-solutions/[ämne].md` |
| Ny SOP/workflow | `/docs/04-workflows/[ämne].md` |
| Task-historik | `/docs/tasks/task-NNN-*.md` |

---

## Sessionsslut-checklista

- [ ] `ddev drush config:status` — inga oväntade deltan
- [ ] `ddev drush cex -y` kört
- [ ] `git diff --stat` granskad — inga oväntade raderingar
- [ ] `CURRENT-TASK.md` uppdaterad
- [ ] Git commit + push
- [ ] Snapshot tagen (om DB-ändringar gjorts)
- [ ] Deploy till prod (om redo)

---

**Skapad**: 2026-03-29
**Uppdaterad**: 2026-04-21 (v2.0 — inventering + guardrails tillagda)
**Författare**: Stefan + Claude
