# KRITISK REGEL: Rätt filsystem för Drupal-projekt

## ⚠️ PROBLEM
Claude har tillgång till 2 datorer och skapar ofta filer på fel ställe.

## ✅ LÖSNING: ALLTID använda rätt verktyg

### Drupal-projekt (TritonLED)
**ALLTID använd:** `Filesystem:*` verktyg (Capital F)
**Sökväg:** `/Users/steffes/Projekt/tritonled/`

### Claude's dator (uppladdade filer, temp arbete)
**Använd:** `filesystem:*` verktyg (lowercase f)
**Sökväg:** `/home/claude/` eller `/mnt/user-data/`

---

## 📋 WORKFLOW-CHECKLISTA FÖR DRUPAL-FILER

Innan jag skapar NÅGON fil i TritonLED-projektet:

### 1. STOPPA & FRÅGA:
- [ ] Ska filen in i `/Users/steffes/Projekt/tritonled/`?
- [ ] Om JA → använd `Filesystem:*` verktyg (Capital F)
- [ ] Om NEJ → använd `filesystem:*` verktyg (lowercase f)

### 2. VERIFIERA PATH:
```
RÄTT:  /Users/steffes/Projekt/tritonled/config/sync/...
FEL:   /home/claude/... eller inget path alls
```

### 3. RÄTT VERKTYG PER FILTYP:

| Filtyp | Verktyg | Path |
|--------|---------|------|
| Config YAML | `Filesystem:write_file` | `/Users/steffes/Projekt/tritonled/config/sync/` |
| Theme files | `Filesystem:write_file` | `/Users/steffes/Projekt/tritonled/web/themes/custom/tritonled/` |
| Templates | `Filesystem:write_file` | `/Users/steffes/Projekt/tritonled/web/themes/custom/tritonled/templates/` |
| Custom modules | `Filesystem:write_file` | `/Users/steffes/Projekt/tritonled/web/modules/custom/` |
| Docs | `Filesystem:write_file` | `/Users/steffes/Projekt/tritonled/docs/` |

### 4. EFTER SKAPANDE:
- [ ] Verifiera: `ls -la /Users/steffes/Projekt/tritonled/[path]`
- [ ] Om filen INTE finns → FEL verktyg använt!

---

## 🚨 VANLIGA MISSTAG

### ❌ FEL: Använder `create_file` (Claude's dator)
```
<invoke name="create_file">
<parameter name="path">/config/sync/views.view.example.yml