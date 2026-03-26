# Task 021: Syskonprodukter-block på produktsidan

**Created**: 2026-03-25  
**Status**: In Progress  
**Last Updated**: 2026-03-25  
**Related Tasks**: TASK-015, TASK-024  
**Arkitekturdokument**: `/docs/03-solutions/konfigurator-arkitektur-beslut.md`

---

## 1. DEFINE

### Mål
Ett Views-block per serie (MAX/OPTI/SROW) som visar alla syskonmodeller som
klickbara badges. Nuvarande modell markeras som aktiv (fylld knapp).
Placeras i Layout Builder på varje produktsida.

### Syfte
Användaren ska enkelt kunna navigera mellan modeller i samma serie
(t.ex. från OPTI Base till OPTI-S Sensor) utan att behöva gå tillbaka
till produktöversikten.

### Acceptanskriterier
- [ ] Tre Views-block skapade: `Syskonprodukter: MAX`, `Syskonprodukter: OPTI`, `Syskonprodukter: SROW`
- [ ] Alla modeller i serien visas som badges/knappar med länk
- [ ] Nuvarande modell markeras visuellt (aktiv badge)
- [ ] Blocken är placerade i Layout Builder på respektive produktsidor
- [ ] Fungerar på alla 12 produktsidor

**Godkänt av Stefan**: ✅ Godkänd

---

## 2. PLAN

### Beslutsträd
**Fil**: `/docs/DRUPAL-DECISION-TREE.md`  
**Steg**: Views (ingen custom kod behövs)

### Vald lösning — Alternativ C
**Approach**: Views block-displayer med hårdkodad HTML (Global: Custom text)  
**Aktiv markering**: CSS-klass `btn-primary` på aktuell modell via separata block per produktsida

### Motivering
- Enklast möjliga lösning — Views + Bootstrap-klasser
- Ingen custom kod eller preprocess hooks
- Flexibel via Layout Builder
- Se arkitekturdokument för varför Alternativ B (inline modellval) valts bort för nu

### Alternativ övervägda
Se `/docs/03-solutions/konfigurator-arkitektur-beslut.md` för fullständig analys
av Alternativ A, B och C.

**Godkänt av Stefan**: ✅ Godkänd

---

## 3. IMPLEMENT

### Steg 1 — Skapa block-displayer i Views UI

Gå till: Admin → Structure → Views → Featured Products → Edit

Skapa tre nya Block-displayer med **Global: Custom text** som enda fält.
Inga filter, pager satt till 1. Se HTML nedan per serie.

#### Block: Syskonprodukter MAX
**Block name**: `Syskonprodukter: MAX`

```html
<div class="d-flex flex-wrap gap-2 align-items-center py-2">
  <span class="text-muted small me-1">MAX-serien:</span>
  <a href="/en/product/triton-max" class="btn btn-outline-secondary btn-sm">MAX</a>
  <a href="/en/product/triton-max-pro" class="btn btn-outline-secondary btn-sm">MAX-PRO</a>
  <a href="/en/product/triton-max-s-gen-3-sensor" class="btn btn-outline-secondary btn-sm">MAX-S Sensor</a>
  <a href="/en/product/triton-max-e-gen-3-emergency" class="btn btn-outline-secondary btn-sm">MAX-E Emergency</a>
  <a href="/en/product/triton-max-ed-daylight-gen-3" class="btn btn-outline-secondary btn-sm">MAX-ED Emergency+Daylight</a>
</div>
```

**OBS:** Per produktsida byts `btn-outline-secondary` till `btn-primary` på
den aktuella modellens knapp via en separat display eller manuellt i Layout Builder.

#### Block: Syskonprodukter OPTI
**Block name**: `Syskonprodukter: OPTI`

```html
<div class="d-flex flex-wrap gap-2 align-items-center py-2">
  <span class="text-muted small me-1">OPTI-serien:</span>
  <a href="/en/product/triton-opti" class="btn btn-outline-secondary btn-sm">OPTI</a>
  <a href="/en/product/triton-opti-s-gen-4-sensor" class="btn btn-outline-secondary btn-sm">OPTI-S Sensor</a>
  <a href="/en/product/triton-opti-e-gen-4-emergency" class="btn btn-outline-secondary btn-sm">OPTI-E Emergency</a>
  <a href="/en/product/triton-opti-ed-daylight-gen-4" class="btn btn-outline-secondary btn-sm">OPTI-ED Emergency+Daylight</a>
</div>
```

#### Block: Syskonprodukter SROW
**Block name**: `Syskonprodukter: SROW`

```html
<div class="d-flex flex-wrap gap-2 align-items-center py-2">
  <span class="text-muted small me-1">SROW-serien:</span>
  <a href="/en/product/triton-srow-ip54ip65" class="btn btn-outline-secondary btn-sm">SROW</a>
  <a href="/en/product/triton-srow-e-gen-3-emergency" class="btn btn-outline-secondary btn-sm">SROW-E Emergency</a>
  <a href="/en/product/triton-srow-ed-gen-3-emergency-daylight" class="btn btn-outline-secondary btn-sm">SROW-ED Emergency+Daylight</a>
</div>
```

### Steg 2 — Aktiv markering
Enklaste lösning: varje produktsida får sin egen Layout Builder-konfiguration
där rätt knapp har `btn-primary`. Eftersom HTML är statisk i Views-blocket
skapas en display per produktsida vid behov, eller hanteras via CSS:

```css
/* Markera aktiv länk baserat på current path */
.syskon-block a[href*="{{ current_path }}"]:not([href*="?"]) {
  /* Detta fungerar ej i ren CSS — se alternativ nedan */
}
```

**Bättre alternativ**: Låt JS markera aktiv knapp:
```javascript
document.querySelectorAll('.syskon-block a').forEach(function(a) {
  if (a.getAttribute('href') === window.location.pathname) {
    a.classList.remove('btn-outline-secondary');
    a.classList.add('btn-primary');
  }
});
```

Detta läggs till i `configurator.js` eller ett separat litet script.

### Steg 3 — Placera i Layout Builder
- MAX-block på alla 5 MAX-produktsidor
- OPTI-block på alla 4 OPTI-produktsidor  
- SROW-block på alla 3 SROW-produktsidor

### Git commit
```bash
git add -A
git commit -m "[TASK-021] Add sibling product badges blocks (Alternativ C)"
```

---

## 4. VERIFY

### Testresultat
- [ ] Tre blocks syns i Layout Builder block-lista
- [ ] Alla syskon visas som badges med rätt länkar
- [ ] Aktiv modell är markerad (btn-primary)
- [ ] Fungerar på alla 12 produktsidor
- [ ] Responsiv (flex-wrap fungerar på mobil)

---

## 5. COMPLETION

### Status: 🔄 In Progress

### Nästa steg efter denna task
- Fas 2 i roadmap: Feeds-import av 55 000+ varianter
- Se `/docs/03-solutions/konfigurator-arkitektur-beslut.md`
