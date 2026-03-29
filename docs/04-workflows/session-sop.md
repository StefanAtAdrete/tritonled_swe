# SOP: Sessionshantering

**Skapad**: 2026-03-29
**Syfte**: Standard Operating Procedure för hur vi strukturerar och dokumenterar varje session

---

## DEL 1 — SESSIONSSTART

### Claude gör:
1. Läser `/docs/00-START-HERE.md`
2. Läser `/docs/CURRENT-TASK.md`
3. Presenterar kort: var vi är, öppna tasks, förslag på vad vi tar tag i

### Stefan kontrollerar:
```bash
ddev start
ddev drush status
```

### Gemensam bekräftelse:
- Vilken task arbetar vi med idag?
- Finns task-fil? Om inte → skapa från `TASK-TEMPLATE.md`

---

## DEL 2 — MITT I SESSION (Checkpoint)

### När triggas ett checkpoint?
- ✅ En task markeras som **klar**
- ✅ En lång session (>1h) utan naturligt avbrott
- ✅ Innan vi byter inriktning/task

### Claude påminner aktivt:
När en task markeras som klar säger Claude:
> *"✅ [TASK-NNN] klar. Dags för checkpoint — vill du köra det nu?"*

### Checkpoint-steg (i ordning):

**1. Exportera config**
```bash
ddev drush cex -y
```

**2. Git commit**
```bash
git add -A
git commit -m "[TASK-NNN] Checkpoint: beskrivning av vad som är klart"
```

**3. Uppdatera CURRENT-TASK.md**
- Markera avklarade tasks som ✅
- Notera vad som återstår
- Lägg till relevant kontext för nästa session

**4. Backup & Migrate snapshot**
- Admin → Content → Backup and Migrate
- Ta en ny backup och spara lokalt

**5. Bekräftelse**
Claude bekräftar: *"Checkpoint klar. Fortsätter vi eller avslutar vi sessionen?"*

---

## DEL 3 — SESSIONSSLUT

### Steg (i ordning):

**1. Exportera config (om inte nyligen gjort)**
```bash
ddev drush cex -y
```

**2. Uppdatera dokumentation**
- `CURRENT-TASK.md` — status på alla öppna tasks
- `00-START-HERE.md` — om nya viktiga beslut tagits
- Ev. ny fil i `/docs/03-solutions/` om vi löst något nytt

**3. Git commit + push**
```bash
git add -A
git commit -m "[TASK-NNN] Session slut: sammanfattning"
git push origin main
```

**4. Backup & Migrate snapshot (om inte nyligen gjort)**

**5. Deploy till produktion (om redo)**
```bash
# På produktionsservern:
cd /home/tritonled/htdocs/tritonled.se
git pull
vendor/bin/drush cim --partial -y
vendor/bin/drush cr
```

---

## Snabbreferens: Vad dokumenteras var?

| Vad | Var |
|-----|-----|
| Pågående task, status | `CURRENT-TASK.md` |
| Viktiga beslut (senaste) | `00-START-HERE.md` → Senaste Viktiga Beslut |
| Lösningar/arkitektur | `/docs/03-solutions/[ämne].md` |
| Ny SOP/workflow | `/docs/04-workflows/[ämne].md` |
| Task-historik | `/docs/tasks/task-NNN-*.md` |

---

## Checklista — Sessionsslut

- [ ] `ddev drush cex -y` kört
- [ ] `CURRENT-TASK.md` uppdaterad
- [ ] Git commit + push
- [ ] Backup & Migrate snapshot tagen
- [ ] Ev. deploy till produktion

---

**Version**: 1.0
**Skapad**: 2026-03-29
**Författare**: Stefan + Claude
