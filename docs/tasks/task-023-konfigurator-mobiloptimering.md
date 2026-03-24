# TASK-023 — Konfigurator: Mobiloptimering

**Skapad**: 2026-03-24
**Status**: Planned
**Prioritet**: Hög

---

## Problem

Native `<select>`-dropdowns i konfiguratorn öppnar sig **uppåt** på mobil och täcker hela skärmen.
Orsak: Webbläsaren väljer riktning baserat på elementets position i viewporten — när select ligger
nära botten (eller utanför viewporten) öppnas listan uppåt. Detta kan inte styras med CSS.

Konfiguratorn har upp till 8 dropdowns (MAX/OPTI) vilket gör den mycket lång på mobil.

---

## DEFINE

### Acceptanskriterier
- [ ] Dropdowns öppnar sig nedåt på mobil
- [ ] Konfiguratorn är användbar på skärmar ≥ 320px bredd
- [ ] Väljer man ett alternativ scrollas man inte bort från konfiguratorn
- [ ] Fungerar på iOS Safari och Android Chrome
- [ ] Ingen regression på desktop

---

## PLAN

### Approach A — Bootstrap custom dropdowns (rekommenderas)
Ersätt native `<select>` med Bootstrap 5 custom dropdown-komponenter i `configurator.js`.
- Full kontroll över öppningsriktning
- Kan stylas med Bootstrap-klasser
- Kräver omskrivning av `render()`-funktionen i JS

### Approach B — Accordion/steg-för-steg på mobil
På mobil visas ett steg i taget (accordion) — användaren väljer ett värde och nästa steg öppnas.
- Bättre UX för mobil
- Mer komplex implementation
- Inspirerat av e-handels-konfiguratorer (Nike, etc.)

### Approach C — Scrollbar container
Lägg konfiguratorn i en scrollbar container med fast höjd på mobil.
- Enklare implementation
- Native dropdowns fortsätter att vara problematiska

### Rekommendation
**Approach A** för snabb fix, **Approach B** som långsiktig UX-förbättring.

---

## Tekniska detaljer

### Nuläge i configurator.js
```javascript
// render()-funktionen skapar native <select>
const select = document.createElement('select');
select.className = 'form-select';
```

### Bootstrap custom dropdown-struktur
```html
<div class="dropdown">
  <button class="btn btn-outline-secondary dropdown-toggle w-100" 
          type="button" data-bs-toggle="dropdown">
    Välj längd
  </button>
  <ul class="dropdown-menu w-100">
    <li><a class="dropdown-item" href="#">0,6m</a></li>
    ...
  </ul>
</div>
```

---

## Öppna frågor

| Fråga | Svar |
|-------|------|
| Bootstrap JS tillgängligt på produktsidan? | Verifiera |
| Ska mobilläget ha annan layout än desktop? | Ja — accordion förordas |
| Påverkar ändringen CartController? | Nej — POST-data förblir samma |
