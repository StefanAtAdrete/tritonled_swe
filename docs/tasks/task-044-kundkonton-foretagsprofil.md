# TASK-044 — Kundkonton + företagsprofil

**Created**: 2026-05-19
**Status**: In Progress (IMPLEMENT — Steg 0)
**Last Updated**: 2026-05-19
**Parent**: TASK-043 (Offertsystem-master)
**Related**: TASK-045 (Webform-koppling), TASK-054 (Elektriker-portal)

---

## 1. DEFINE

### Mål
Elektriker kan registrera sig, logga in, och få en kopplad **företagsprofil** (Profile-entity) med organisationsuppgifter. Säljare ser och kan redigera dessa profiler från admin.

### Syfte
Grunden för hela offertsystemet. Utan konto + företagskoppling kan vi inte:
- Koppla offerter och projekt till rätt kund
- Bygga "Mina projekt"-portalen (TASK-054)
- Skicka mail till rätt mottagare med Token-fält
- Visa kundhistorik för säljare

### Omfattning
**Ingår:** Profile-typ "Företag", user-roller Elektriker + Säljare, registreringsflöde, permissions, ECA-modell för default-roll, översättningar.

**Ingår INTE:** Webform-koppling (TASK-045), kundportal (TASK-054), multi-user per företag (Plan A — Profile-modul 1:1, refactor senare).

### Initialfält (Profile-typ "Företag")
| Fält | Typ | Required |
|---|---|---|
| Företagsnamn | Text plain | Ja |
| Organisationsnummer | Text plain (fri text) | Ja |
| Fakturaadress | Address | Ja |
| Leveransadress | Address | Nej |
| Telefon | Telephone | Ja |
| Kontaktperson namn | Text plain | Nej |

### Acceptanskriterier
- [ ] Modulerna `profile`, `address`, `eca` är aktiva och i `cex`-output
- [ ] Profile-typ "Företag" finns med samtliga initialfält ovan
- [ ] User-roll "Elektriker" + "Säljare" finns
- [ ] Self-registration på, e-postverifiering AV
- [ ] ECA-modell tilldelar "Elektriker"-roll vid user create
- [ ] Säljare kan skapa user + profil från admin
- [ ] Elektriker: edit own profil; Säljare: edit any
- [ ] Registreringsformulär på svenska
- [ ] Endast företagsnamn + e-post + lösenord vid registrering
- [ ] Config exporterad och commit:ad

### Besvarade frågor

| # | Fråga | Svar |
|---|---|---|
| 1 | Self-reg eller säljare? | **Både** + mailväg för manuell skapande |
| 2 | Orgnr-validering? | **Fri text** |
| 3 | Vem redigerar profil? | **Båda** (elektriker edit own, säljare edit any) |
| 4 | E-postverifiering? | **AV** |
| 5 | Multi-user förberedelse? | **Plan A** — Profile-modul nu |
| 6 | Default-roll-mekanism? | **B (ECA)** |
| 7 | Profil-fält vid registrering? | **(i)** Bara företagsnamn + e-post + lösenord |

**DEFINE godkänd av Stefan**: ✅ 2026-05-19

---

## 2. PLAN

### Beslutsträd
**Fil**: `/docs/DRUPAL-DECISION-TREE.md` — Steg 1 (Config), 2 (Contrib). Ingen custom kod.

### Vald lösning
Contrib + Config: `profile`, `address`, `eca` + Field UI + user account settings + permissions + ECA-modell.

### Upptäckter under PLAN-fasen
- ✅ `profile`-modul redan installerad i `core.extension.yml`
- ✅ `address`-modul redan installerad
- ❌ `eca`-modul ej installerad → installeras i Steg 1
- ⚠️ Custom modul `tritonled_quote` finns redan (1 hook: ta bort unit_price från IEF-checkout). Namnkollision med planerad TASK-046. **Parkerad till TASK-046** — beslut: troligen återanvänd namnet, absorbera legacy-hook.

### Motivering
Profile-modulen är Drupal-standard för fält knutet till user men konceptuellt separat. Field UI + View-integration + edit-routes "gratis". ECA väljs över `auto_assign_role` eftersom den får dubbel användning senare (default-grupp "Övrigt" i TASK-047, status-cron, etc.).

### Alternativ övervägda
1. Fält på User-entiteten — förorenar entity, ingen View-integration. **Förkastat.**
2. Custom "Företag" content entity nu — Plan B. **Förkastat.**
3. `auto_assign_role` istället för ECA — fungerar, men ECA används senare ändå. **Förkastat.**

**PLAN godkänd av Stefan**: ✅ 2026-05-19

---

## 3. IMPLEMENT

### Steg 0: Verifiera config-status (FÖRE allt annat)
```bash
ddev start
ddev drush status
ddev drush config:status
```
**Stoppa om** `config:status` visar något annat än "No differences" eller hanterade splittor.

### Steg 1a: Aktivera redan installerade moduler
```bash
ddev drush en profile address -y
ddev drush cr
```

### Steg 1b: Installera + aktivera ECA
```bash
ddev composer require drupal/eca
ddev drush en eca eca_ui eca_user eca_base -y
ddev drush cr
```
*Submoduler: `eca_ui` (UI för modeller), `eca_user` (user-events), `eca_base` (basaktioner). Vi väljer modellerings-plugin i nästa steg.*

Git commit: `[TASK-044] Enable profile, address; add eca`

### Steg 2: User-roller (`/admin/people/roles`)
- Bekräfta "Säljare" finns (alt. skapa)
- Skapa "Elektriker"

### Steg 3: Profile-typ "Företag" (`/admin/config/people/profile-types`)
- Label: **Företag**
- Machine name: `company`
- Multiple: Nej
- Registration: PÅ (bara företagsnamn-fält visas, se Steg 4 form display)

### Steg 4: Fält via Field UI på Profile-typ "Företag"
| Maskinnamn | Typ | Required |
|---|---|---|
| `field_company_name` | Text plain | Ja |
| `field_org_number` | Text plain | Ja |
| `field_invoice_address` | Address | Ja |
| `field_delivery_address` | Address | Nej |
| `field_phone` | Telephone | Ja |
| `field_contact_person` | Text plain | Nej |

**Form display-trick för (i):** På "Register"-formuläret (om Profile har separat form mode "Register") → dölj alla utom `field_company_name`. På default → visa alla.

### Steg 5: User Account Settings (`/admin/config/people/accounts`)
- Who can register: **Visitors**
- Require email verification: **AV**
- Default role: lämnas tom (ECA tilldelar i Steg 6)

### Steg 6: ECA-modell — "On user create → add Elektriker role"
Via UI (`/admin/config/workflow/eca`):
- Modelleringsplugin: BPMN (kommer med eca_ui) eller Fallback
- Event: `User: presave` eller `User: insert`
- Condition: User is new + has no Elektriker-roll
- Action: Add role "Elektriker" to user

### Steg 7: Permissions (`/admin/people/permissions`)
| Permission | Elektriker | Säljare | Admin |
|---|---|---|---|
| View own company profile | ✅ | ✅ | ✅ |
| Edit own company profile | ✅ | ✅ | ✅ |
| View any company profile | – | ✅ | ✅ |
| Edit any company profile | – | ✅ | ✅ |
| Create any company profile | – | ✅ | ✅ |
| Delete any company profile | – | – | ✅ |
| Administer users | – | ✅ | ✅ |

### Steg 8: Översättningar
- Profil-typens label + beskrivning: sv
- Fält-labels + hjälptexter: sv via Configuration Translation
- Core registreringsstrings: Locale standard

### Steg 9: Manuell test
- Logga ut → `/user/register` → ny user → verifiera Elektriker-roll + profil med företagsnamn
- Logga in som ny user → fyll i resten av profil
- Logga in som säljare → kolla att alla profiler är synliga

### Steg 10: Exportera + commit
```bash
ddev drush config:status
ddev drush cex -y
git diff --stat
git add -A
git commit -m "[TASK-044] Profile type Företag + roles + ECA auto-assign + permissions"
```

---

## 4. VERIFY

⏸️ Påbörjas efter Steg 9.

---

## 5. COMPLETION

⏸️
