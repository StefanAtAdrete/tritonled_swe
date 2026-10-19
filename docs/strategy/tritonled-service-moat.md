# TritonLEDs tjänstevallgrav

**Datum:** 2026-10-19
**Status:** Strategiförslag – inte verksamhetsbeslutat
**Syfte:** Definiera hur TritonLED kan bli förstavalet även när kunden kan köpa armaturer billigare direkt från tillverkare i Kina.

## 1. Utgångsläge

### Uppgivet av verksamheten

- Triton-armaturernas variantantal ska sannolikt minskas.
- Sortimentet ska kompletteras med fler produkter från andra tillverkare.
- En eller flera masterlistor, exempelvis Excel, ska bära produkt- och prisdata.
- TritonLED Sverige ska utvecklas mot återförsäljare med fokus på kunskap, produktval och service.

### Strategisk slutsats

Själva armaturen är inte en tillräcklig vallgrav. Artikelnummer, watt, lumen och inköpspris kan jämföras och kopieras. TritonLED bör i stället sälja ett verifierat resultat:

> **Rätt ljus, verifierat och förvaltat över tid.**

Kunden ska köpa minskad risk, mindre eget arbete och ett tydligt ansvar – inte bara en produktkartong.

## 2. Rekommenderat erbjudande: Triton Projektsäkrat ljus

### Triton Match

- Kunden lämnar ritning, bilder eller en beskrivning av lokalen.
- TritonLED presenterar två till tre lämpliga lösningar.
- Förslaget innehåller armaturantal, effekt, styrning, antaganden och preliminär besparing.
- Målet är ett snabbt första beslutsunderlag, inte slutlig projektering.

### Triton Verify

- Verifierad ljusprojektering och DIALux-underlag när projektet kräver det.
- LCC-jämförelse mellan befintlig anläggning och minst ett alternativ.
- Kontroll av fotometri, livslängd, flimmer, färgkvalitet, IP/IK, garanti och kompatibilitet.
- Samlat projektdossier med EU-försäkran, CE-underlag och svensk installations- och säkerhetsinformation.

### Triton Ready

- Projektspecifik märkning och paketering per zon eller installationsplats.
- Rätt drivdon, kontakt, optik, styrning och monteringsdetaljer.
- Stycklista och installationsunderlag för installatören.
- Syftet är färre felbeställningar och mindre arbete på installationsplatsen.

### Triton Care

- Digital anläggningsbok per fastighet eller projekt.
- Installerad produkt, konfiguration, placering, garanti och dokumentation sparas.
- Underhållsplan, reservdelar och verifierad ersättningsväg när en modell utgår.
- Kan erbjudas som årligt serviceavtal eller som projektbunden förvaltning.

## 3. Varför kunden inte bara ska direktimportera

Direktimport kan ge lägre styckepris men flyttar arbete och risk till kunden. TritonLEDs erbjudande ska konkret hantera:

- produktval och dimensionering,
- importörs- och dokumentationsansvar där TritonLED är importör,
- kontroll av märkning och tekniskt underlag,
- svensk installations- och säkerhetsinformation,
- garanti- och reklamationshantering,
- leveranstid, reservdelar och framtida ersättare,
- verifiering av energiprestanda och ljuskvalitet,
- drift- och underhållsplan.

Importörsansvar får inte användas som allmän skrämselargumentation. TritonLED ska endast lova ansvar som bolaget faktiskt har och kan bevisa med dokumentation.

## 4. Datavallgraven

Masterlistan ska vara en källa – inte webbplatsens datamodell och inte ensam sanningskälla.

```text
Leverantörens rådata
→ normaliserad produktpost
→ granskat och godkänt Triton-sortiment
→ publicerad produkt
→ projektspecifikt val och offert
→ utfall, avvikelse och servicehistorik
```

### Lager i modellen

1. **Källa** – originalfil, leverantör, filversion, checksumma och importdatum.
2. **Normaliserad produkt** – identitet, SKU, egenskaper, dokumentreferenser och livscykelstatus.
3. **Godkänt sortiment** – produkter som TritonLED har granskat och vill rekommendera.
4. **Publicerat sortiment** – det urval som visas på webbplatsen.
5. **Prisbok** – separat och versionerad per kundtyp, avtal och giltighetstid.
6. **Projektdata** – plats, antal, kundpris, beräkningsantaganden och valt alternativ.

### Status per uppgift

Varje betydelsefullt tekniskt värde bör märkas som:

- `supplier_claimed` – uppgivet av leverantör,
- `verified` – verifierat mot godkänt underlag,
- `derived` – härlett från annan data,
- `expired` – underlaget eller priset har gått ut,
- `conflict` – källor motsäger varandra.

Produktidentitet, pris, lagerstatus och projektspecifika val ska hållas åtskilda.

## 5. Den förstärkande vallgraven

```text
Fler projekt
→ mer data om produktval, installation och avvikelser
→ bättre godkänt sortiment
→ snabbare och säkrare offerter
→ högre kundförtroende och fler projekt
```

Det som byggs upp över tid är inte bara ett produktregister utan kunskap om:

- vilka produkter som fungerar i olika miljöer,
- verkliga leveranstider och avvikelser,
- reklamationer och felorsaker,
- kompatibla ersättare,
- faktisk projekterings- och installationstid,
- vilka lösningar som ger god ekonomi över livscykeln.

Konkurrenter kan köpa samma armatur. De har inte automatiskt samma projekthistorik eller kvalitetssäkrade beslutsprocess.

## 6. Konsekvens för webbplatsen

Webbplatsen bör inte utvecklas till en katalog med hundratals nästan identiska varianter. Primär navigation och konvertering bör utgå från behov:

- lager och industri,
- garage och fuktiga miljöer,
- kontor och arbetsplatser,
- energieffektivisering,
- utbyte av befintlig armatur,
- hjälp med produktval.

Produktkort visar möjliga lösningar. Den primära vägen bör vara:

> **Beskriv lokalen → få ett verifierbart förslag.**

Produktsidor ska visa tekniska data och underlag, men även koppla produkten till användningsfall, projekteringshjälp, dokumentation och service.

## 7. QuickCalc och DIALux

QuickCalc kan bli ett viktigt verktyg för snabb första återkoppling men är för närvarande preliminärt.

- QuickCalc används för första urval, översikt och dialog.
- Preliminära resultat märks tydligt med antaganden och begränsningar.
- DIALux eller annan verifierad metod används för slutlig kontroll när projektet kräver UGR, dagsljus, komplex geometri, hinder eller nödljus.
- QuickCalc får inte beskrivas som DIALux-ersättning innan kalibrering mot minst fem till tio representativa referensfall är godkänd.

## 8. Affärsmodell att testa

Detta är hypoteser, inte beslutade priser eller villkor:

- **Match:** kostnadsfri eller låg tröskel för kvalificerade affärer.
- **Verify:** fast projekteringspris, helt eller delvis avräknat vid order.
- **Ready:** marginal på konfiguration, märkning och projektpaketering.
- **Care:** återkommande serviceintäkt per fastighet eller anläggning.

Funktionslöften måste avgränsas till verifierade indata, godkänd installation och tydliga acceptanskriterier.

## 9. Rekommenderad manuell pilot

Automatisera inte flödet innan tjänsten fungerar manuellt. Testa fem verkliga kundförfrågningar:

1. Standardiserat kundintag.
2. Manuell matchning mot ett begränsat godkänt sortiment.
3. Preliminär QuickCalc.
4. DIALux-verifiering när det krävs.
5. LCC, offert och projektdossier.
6. Dokumentera tidsåtgång, undantag, kundens beslut och projektutfall.

### Mätetal

- tid till första produktförslag,
- tid till verifierad offert,
- konverteringsgrad,
- bruttomarginal efter projekteringstid,
- antal manuella undantag,
- andel godkända produkter med komplett dokumentation,
- reklamations- och felbeställningsgrad,
- andel projekt som tecknar Care eller återkommer.

## 10. Öppna beslut

1. Vilken kundgrupp ska piloten börja med: installatör, fastighetsägare eller industrikund?
2. Vilka fem till tio användningsfall står för störst försäljning och bäst marginal?
3. Vilken nivå av resultat eller responstid kan TritonLED faktiskt garantera?
4. Ska projekteringsavgiften avräknas helt, delvis eller inte alls vid order?
5. Vilka produkter får statusen godkänt Triton-sortiment?
6. Vem äger godkännande av teknisk data, prisbok och publicering?
7. Hur ska uppföljning efter installation samlas in utan att skapa tung administration?

## 11. Källor och verifierade omvärldspremisser

- [Elsäkerhetsverket – Importör](https://www.elsakerhetsverket.se/yrkespersoner/tillverka-och-salja-elprodukter/ansvaret-for-produktsakerheten/importor/)
- [Elsäkerhetsverket – Att tänka på som importör](https://www.elsakerhetsverket.se/yrkespersoner/tillverka-och-salja-elprodukter/verktyg-och-checklistor/att-tanka-pa-som-importor/)
- [Naturvårdsverket – Producentansvar för elutrustning](https://www.naturvardsverket.se/vagledning-och-stod/producentansvar/producentansvar-for-elutrustning/foretag/)
- [Upphandlingsmyndigheten – Upphandla energieffektiv belysning](https://www.upphandlingsmyndigheten.se/om-hallbar-upphandling/miljomassigt-hallbar-upphandling/upphandla-med-hansyn-till-klimatet/upphandla-energieffektiv-belysning/)
- [Upphandlingsmyndigheten – Drift och underhåll av belysningsinstallationer](https://www.upphandlingsmyndigheten.se/kriterier/bygg-och-fastighet/inomhusbelysning/belysningsentreprenad/drift-och-underhall-av-belysningsinstallationer/basniva/)
- [EU-kommissionen – Light sources och ekodesign](https://energy-efficient-products.ec.europa.eu/product-list/light-sources_en)

Källorna kontrollerades 2026-10-19. Regler och myndighetskrav ska verifieras på nytt innan de används i avtal eller kundlöften.
