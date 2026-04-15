# High Bay Fairyland 150LM/W — Produktsida

**Källa**: DE-stock list 26.4.11.xls — Germany Hamburg Stock List  
**Drupal-produkttyp**: `highbay`  
**Status**: Klar — saknar monteringsinfo och tekniska materialdetaljer

---

## HTML-text (klistra in i Drupal body-fält, Full HTML)

```html
<h2>
    <strong>Industriarmatur — En kraftfull highbay med hög verkningsgrad för krävande miljöer.</strong>
</h2>
<p>
    High Bay Fairyland 150LM/W är en robust industriarmatur med 150 lm/W verkningsgrad, 
    anpassad för höga monteringshöjder i lager, industri och sporthallar. 
    Finns i effektsteg från 100W till 200W med 4000K och 5000K, med val av 
    1-10V-dimning eller fast output.
</p>

<h3>
    Nyckelspecifikationer
</h3>
<table class="table" style="width:100%;">
    <thead>
        <tr>
            <th>Egenskap</th>
            <th>Värde</th>
        </tr>
    </thead>
    <tbody>
        <tr><td>Effekt</td><td>100W / 150W / 200W</td></tr>
        <tr><td>Ljusflöde</td><td>15 000 lm / 22 500 lm / 30 000 lm</td></tr>
        <tr><td>Verkningsgrad</td><td>150 lm/W</td></tr>
        <tr><td>Färgtemperatur (CCT)</td><td>4000K / 5000K</td></tr>
        <tr><td>CRI</td><td>&gt;80</td></tr>
        <tr><td>Strålvinkel</td><td>90°</td></tr>
        <tr><td>Drivdon</td><td>Moso (fast output) / Sosen (1-10V dimbar)</td></tr>
        <tr><td>Garanti</td><td>5 år</td></tr>
    </tbody>
</table>

<h3>
    Varianter
</h3>
<table class="table" style="width:100%;">
    <thead>
        <tr>
            <th>Modell</th>
            <th>Artikelnr</th>
            <th>Effekt</th>
            <th>CCT</th>
            <th>Drivdon</th>
            <th>Dimning</th>
            <th>Pris (€)</th>
        </tr>
    </thead>
    <tbody>
        <tr><td>HB100FE8H-PN-4KD90</td><td>10104267</td><td>100W</td><td>4000K</td><td>Moso</td><td>Nej</td><td>18,50</td></tr>
        <tr><td>HB100FE8H-PY-4KD90</td><td>10105404</td><td>100W</td><td>4000K</td><td>Sosen</td><td>1-10V</td><td>19,50</td></tr>
        <tr><td>HB150FE0H-PY-4KD90</td><td>10105405</td><td>150W</td><td>4000K</td><td>Sosen</td><td>1-10V</td><td>21,50</td></tr>
        <tr><td>HB200FE2H-PY-4KD90</td><td>10105406</td><td>200W</td><td>4000K</td><td>Sosen</td><td>1-10V</td><td>26,50</td></tr>
        <tr><td>HB100FE8H-PN-5KD90</td><td>10104268</td><td>100W</td><td>5000K</td><td>Moso</td><td>Nej</td><td>18,50</td></tr>
        <tr><td>HB100FE8H-PC-5KD90</td><td>10104193</td><td>100W</td><td>5000K</td><td>Sosen</td><td>Nej</td><td>19,50</td></tr>
        <tr><td>HB150FE0H-PN-5KD90</td><td>10104273</td><td>150W</td><td>5000K</td><td>Moso</td><td>Nej</td><td>19,50</td></tr>
        <tr><td>HB150FE0H-PC-5KD90</td><td>10103674</td><td>150W</td><td>5000K</td><td>Sosen</td><td>Nej</td><td>19,50</td></tr>
        <tr><td>HB150FE0H-PY-5KD90</td><td>10105917</td><td>150W</td><td>5000K</td><td>Sosen</td><td>1-10V</td><td>21,50</td></tr>
        <tr><td>HB200FE2H-PY-5KD90</td><td>10105918</td><td>200W</td><td>5000K</td><td>Sosen</td><td>1-10V</td><td>26,50</td></tr>
    </tbody>
</table>
```

---

## Drupal — Att göra

1. Skapa produkt under produkttypen **"highbay"**
2. Skapa 10 variationer (en per modell ovan)
3. Lägg till bilder i Media Library
4. Fyll i fält: effekt, CCT, CRI, strålvinkel, drivdon, garanti
5. Konfigurera Layout Builder för produktsidan
