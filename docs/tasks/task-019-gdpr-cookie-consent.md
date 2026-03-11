# TASK-019: GDPR Cookie Consent — Klaro

**Status:** TODO  
**Prioritet:** Hög (legal krav)  
**Område:** GDPR / Privacy

---

## Krav

1. Cookie-banner vid första besök
2. Integritetspolicy-sida
3. Återkallelse av samtycke (användaren kan ändra sig)
4. Rensning av consent-data efter 90 dagar

---

## Vald modul: Klaro Cookie & Consent Management

**Varför Klaro:**
- Rekommenderas av Drupal-communityt (inbyggd i Drupal CMS-stacken)
- EU Cookie Compliance har ingen aktiv maintainer längre
- Drupal 11-kompatibel, aktivt underhållen (stable 3.x)
- Self-hosted — ingen tredjepartstjänst
- Flerspråkigt SV/EN inbyggt
- Blockerar icke-nödvändiga scripts tills samtycke ges
- Återkallelse inbyggt

---

## Moment 1 — Installation

```bash
ddev composer require drupal/klaro
ddev drush en klaro -y
ddev drush cr
```

Konfigurera: `/admin/config/system/klaro`

---

## Moment 2 — Konfiguration

- Språk: SV (primär) + EN
- Samtyckesmetod: Opt-in (inget sätts förrän användaren godkänner)
- Cookie-kategorier att definiera:
  - **Nödvändiga** (alltid på, ej valbara): session, CSRF
  - **Analys** (opt-in): Google Analytics el. liknande om det används
- Banner-placering: Nederkant
- Länk till integritetspolicy i bannern

---

## Moment 3 — Integritetspolicy-sida

Skapa en Basic Page-nod:
- Titel SV: "Integritetspolicy" / EN: "Privacy Policy"
- Path: `/sv/integritetspolicy` / `/en/privacy-policy`
- Innehåll: Vad samlas in, hur länge, kontaktinfo
- Länka från cookie-bannern och footer

---

## Moment 4 — Återkallelse av samtycke

Klaro har inbyggd "Öppna inställningar"-länk.
- Lägg till länk i footer: `<a href="#klaro">Cookie-inställningar</a>`
- Eller via Klaro's block i Block Layout

---

## Moment 5 — Rensning efter 90 dagar

Klaro lagrar samtycke i en cookie (ej databas).
- Cookie-livslängd konfigureras i Klaro-admin (sätt till 90 dagar = 7776000 sekunder)
- Efter 90 dagar visas bannern automatiskt igen

---

## Implementation-ordning

1. [ ] Installera Klaro-modulen
2. [ ] Grundkonfiguration (språk, opt-in, kategorier)
3. [ ] Skapa integritetspolicy-sida (SV + EN)
4. [ ] Sätt cookie-livslängd till 90 dagar
5. [ ] Lägg till "Cookie-inställningar"-länk i footer
6. [ ] Testa återkallelse
7. [ ] Verifiera på mobil

---

## Kopplat till

- Footer (länk till integritetspolicy)
- TASK-016: Navigation (ev. länk i topbar)
