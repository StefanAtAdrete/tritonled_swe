# TASK-019: GDPR Cookie Consent — Klaro

**Status:** ✅ COMPLETED (2026-03-11)
**Prioritet:** Hög (legal krav)
**Område:** GDPR / Privacy

---

## Krav

1. ✅ Cookie-banner vid första besök
2. ✅ Integritetspolicy-sida
3. ✅ Återkallelse av samtycke (användaren kan ändra sig)
4. ✅ Rensning av consent-data efter 90 dagar

---

## Vald modul: Klaro Cookie & Consent Management

**Varför Klaro:**
- Rekommenderas av Drupal-communityt
- EU Cookie Compliance har ingen aktiv maintainer längre
- Drupal 11-kompatibel, aktivt underhållen (stable 3.x)
- Self-hosted — ingen tredjepartstjänst
- Flerspråkigt SV/EN inbyggt
- Återkallelse inbyggt

---

## Implementation

### Moment 1 — Installation ✅
```bash
ddev composer require drupal/klaro
ddev drush en klaro -y
```

### Moment 2 — Konfiguration ✅
- Dialog Mode: Notice dialog
- Samtyckesmetod: Opt-in
- Cookie-livslängd: 90 dagar
- Anonymous-rollen: "use klaro"-behörighet
- Admin: `/en/admin/config/user-interface/klaro`

### Moment 3 — Integritetspolicy-sidor ✅
- EN: nid 7, path `/privacy-policy`
- SV: nid 8, path `/integritetspolicy`
- Klaro privacy policy URL (SV): `internal:/integritetspolicy`

### Moment 4 — Svenska översättningar ✅
- Översatt via Translate klaro!-fliken
- Titel: "Användning av personuppgifter och cookies"
- Knappar: "Avböj", "Godkänn valda", "Spara", "Anpassa"
- Notice-text: "Vi använder cookies och behandlar personuppgifter..."

### Moment 5 — Footer-länkar ✅
- SV: Custom block "Cookie-inställningar footer" (nid 2, SV-översättning)
  - Synlig: SV-sidor
  - HTML: `<a href="#" onclick="klaro.show(); return false;">Cookie-inställningar</a>`
- EN: Custom block "Cookie-settings footer" (nid 2)
  - Synlig: EN-sidor
  - HTML: `<a href="#" onclick="klaro.show(); return false;">Cookie settings</a>`

### Moment 6 — Återkallelse verifierad ✅
- Footer-länk öppnar Klaro consent-dialog
- `klaro.show()` fungerar via JavaScript

---

## Lärdomar

- Klistra ALDRIG in HTML från chattkodblock direkt i CKEditor — det inkluderar `<pre><code>`-wrappers
- Klicka "Källa/Source" i CKEditor och sätt innehåll via JavaScript (`textarea.value = ...`) för säker HTML-inmatning
- Klaro visar inte bannern för inloggade admins — testa via `klaro.show()` i konsolen
- Privacy policy URL per språk sätts i "Translate klaro!" → Swedish → Privacy Policy → URL

---

## Commits
```bash
ddev drush cex -y
git add -A
git commit -m "[TASK-019] GDPR Cookie Consent: Klaro setup, privacy policy pages, footer links"
```
