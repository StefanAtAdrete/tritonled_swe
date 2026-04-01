# Task 027: Statiskt produktspecifikations-block (StaticSpecsBlock)

**Created**: 2026-04-01  
**Status**: Not Started  
**Last Updated**: 2026-04-01  
**Related Tasks**: TASK-024, TASK-026, TASK-028

---

## 1. DEFINE

### Mål
Skapa ett nytt Layout Builder-block `StaticSpecsBlock` som visar statiska produktspecifikationer (material, livslängd, IK-klass, certifieringar, driftstemperatur) hämtade från `staticSpecs` i JSON-schemat. Blocket placeras nedanför `ConfiguratorSpecsBlock` på produktsidan.

### Syfte
Separation av konfigurerbar data (varierar per val) och statisk produktdata (alltid samma per modell) ger bättre UX och tydligare datastruktur. Statiska specs behöver inte uppdateras av JS — de renderas direkt från schemat.

### Acceptanskriterier
- [ ] Nytt block plugin `tritonled_configurator_static_specs_block` skapar
- [ ] Blocket läser `staticSpecs` från `field_configurator_schema` på produkten
- [ ] Renderar en tabell med fält: lifetime, temperature, material, ik, certifications
- [ ] Blocket är placerbart i Layout Builder
- [ ] På utskriften visas statiska specs i en **separat tabell** under den konfigurerbara tabellen
- [ ] Fältetiketter är översättningsbara via `$this->t()`
- [ ] Testat på MAX, OPTI och SROW

**Godkänt av Stefan**: ⏳ Väntar

---

## 2. PLAN

### Beslutsträd
Följer `DRUPAL-DECISION-TREE.md`:
- Config → nej (custom data)
- Contrib → nej (inget modul hanterar detta)
- Layout Builder block → **JA** — samma mönster som `ConfiguratorSpecsBlock`

### Vald lösning
Nytt Block Plugin i `tritonled_configurator`-modulen. Samma arkitektur som `ConfiguratorSpecsBlock` men:
- Ingen JS-dependency (statiska värden)
- Renderas direkt från schema i PHP
- Print-CSS lägger till en andra tabell under den konfigurerbara

### Struktur `staticSpecs` i JSON-schemat
```json
"staticSpecs": {
  "lifetime": "72.000h/@49C",
  "temperature": "15-30°C",
  "material": "6061 Anodized Alloy",
  "ik": "IK08",
  "certifications": "CE (EMC, LVD, RoHS), EUR1"
}
```

### Filer att skapa/ändra
1. `src/Plugin/Block/StaticSpecsBlock.php` — nytt block
2. `css/components/configurator-print.css` — print-styling för andra tabellen
3. JSON-scheman — `staticSpecs` läggs till per modell (TASK-028)

**Godkänt av Stefan**: ⏳ Väntar

---

## 3. IMPLEMENT

*Startar när plan godkänts och TASK-028 (staticSpecs i JSON) är klar.*

### Steg
1. Skapa `StaticSpecsBlock.php`
2. Uppdatera `tritonled_configurator.module` om hook behövs
3. Rensa cache + verifiera att blocket syns i Layout Builder
4. Placera blocket på produktsidorna (MAX, OPTI, SROW)
5. Uppdatera print-CSS med andra tabellen
6. Git commit: `[TASK-027] Add StaticSpecsBlock for static product specifications`

---

## 4. VERIFY

*Utförs efter implementation.*

---

## 5. COMPLETION

### Nästa steg
- TASK-028 måste vara klar (staticSpecs i JSON-scheman) innan detta kan implementeras
