# Task 030: UX-förbättringar baserade på Thomas feedback

**Created**: 2026-04-15  
**Status**: Not Started  
**Last Updated**: 2026-04-15  
**Källa**: E-post från Thomas Lundberg (thomaslundberg.vbg@gmail.com), 2026-04-14  
**Gmail Thread ID**: 19d8ddbbfb068b0e  
**Related Tasks**: TASK-017b, TASK-019 (GDPR/Cookie)

---

## Bakgrund

Thomas Lundberg (delägare/kund) skickade en genomarbetad UX-analys av sajten 2026-04-14.
Feedbacken gäller den svenska layouten och ska implementeras när sajten är funktionellt klar.
Detta är värdefull input från kundens perspektiv.

---

## Underuppgifter

### 030-A: Startsida – struktur och navigation

- [ ] Ta bort armaturer från förstasidan (produkterna ska ligga under sin kategori)
- [ ] Döp om "Industriarmaturer" till "Produkter" i navigationen
- [ ] Skapa sektioner för MAX, OPTI och SROW med bild + säljtext per serie (användningsområde + kort teknisk data)
- [ ] Lägg till sektion för Sensor med bild + säljtext
- [ ] Lägg till sektion "Övriga" för övriga armaturer/utrustning
- [ ] Hero: Använd film som visar armaturer/sensorer. Fallback: stilla bilder på MAX-OPTI-SROW-Sensor

### 030-B: Konfigurator

- [ ] Flytta konfiguratorn till en egen flik
- [ ] Lägg till kort förklarande text om hur konfiguratorn fungerar
- [ ] Utred möjlighet till inloggning för att komma åt konfiguratorn (lead tracking)

### 030-C: Navigation – nya flikar/sidor

- [ ] "Kontakt" som egen flik/sida
- [ ] "Customer Cases" som egen flik/sida
- [ ] "Leveransvillkor" som egen flik/sida
- [ ] Lägg till: Kvalitetsmål / Kvalitetspolicy
- [ ] Lägg till: Miljömål / Miljöpolicy
- [ ] Lägg till: Nyheter

### 030-D: Kontaktsida

- [ ] Ändra rubrik "Försäljning (Offerter/förfrågan)" → "Försäljning" (alternativt "Sales")
- [ ] Kontaktpersoner:
  - Försäljning: Christer Svantesson, +46 733 863 025, christer@tritonled.se
  - Teknisk support: Laurits Eriksen, +46703314976, laurits@tritonled.se
  - Leverantörer/Fakturor: invoice@tritonled.se
- [ ] Ta bort telefonnummer från Integritetspolicy (räcker med info@-mailadressen)

### 030-E: Footer

- [ ] Säkerställ att företagsinfo finns i footer:
  - TritonLED Sweden AB
  - Söderlundsvägen 15, SE-653 50 Karlstad. Sweden
  - info@tritonled.se
  - Org nr: 559443-9282

### 030-F: GDPR / Cookie-meddelande

- [ ] Samtyckesinställningar ska vara på svenska (se även TASK-019)
- [ ] Cookie-popup med val: "Acceptera" / "Endast nödvändiga"

### 030-G: Färgsättning (förslag från Thomas)

Thomas föreslår följande färgpalett som underlag för vidare diskussion:

| Roll | Namn | Hex | Signal |
|---|---|---|---|
| Primär | Mörk/teknisk blå | #0A2A43 – #123A5A | Pålitlighet, teknik, precision |
| Sekundär | Energigrön | #4CAF50 / #3E8E41 | Energieffektivitet, hållbarhet |
| Accent | Elektrisk gul | #F2C94C / #FFD84D | Ljus, innovation, belysning |
| Neutral mörk | Grafit | #1A1A1A | Text |
| Neutral ljus | Ljusgrå | #F5F5F5 | Bakgrund |
| Neutral mellan | Mellangrå | #D9D9D9 | Dividers, borders |

- [ ] Diskutera och besluta färgpalett med Laurits/Christer/Thomas
- [ ] Implementera godkänd palett i temat

### 030-H: "Om oss" – textrevision

Thomas föreslog kortare, mer säljdrivet innehåll. Se mejlet för föreslagen text.
Nuvarande text är för lång enligt Thomas.

- [ ] Revidera "Om oss"-texten utifrån Thomas förslag
- [ ] Utforma som "bubblor" med scrolleffekt och färg (Thomas förslag på layout)

---

## Prioritering

Dessa ändringar implementeras **efter** att sajten är funktionellt klar (produkter, konfigurator, GDPR).
Förslag på prioriteringsordning:

1. Navigation + startsidestruktur (030-A, 030-C)
2. Kontaktsida (030-D)
3. Cookie/GDPR (030-F → TASK-019)
4. Footer (030-E)
5. Konfigurator UX (030-B)
6. Färgpalett (030-G)
7. Texter (030-H)

---

## Noteringar

- Feedbacken gäller **svenska layouten** – kontrollera att ändringar även speglas i engelska
- Thomas nämner `preview.affarsfabriken.se` som URL – han har tittat på en preview-miljö, inte tritonled.se direkt
