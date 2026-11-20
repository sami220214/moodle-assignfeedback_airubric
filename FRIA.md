# Perusoikeusvaikutusten arviointi (FRIA): tekoälyn arviointimatriisipalaute

**Arvioinnin kohde:** Moodle-lisäosa `assignfeedback_airubric` ja sen käyttö palautteenannon tukena.  
**Päiväys:** 17.11.2026.  
**Tila:** lähdekoodiin perustuva selvitysluonnos, joka täydennetään organisaation käyttöympäristöä koskevilla tiedoilla ennen hyväksymistä. Tämä asiakirja ei sellaisenaan osoita vaatimustenmukaisuutta tai hyväksy käyttöönottoa.

## 1. Vastuut ja arvioinnin rajaus

Käyttöönottava organisaatio: **[täydennä]**. Toiminnasta ja käyttöönotosta vastaava henkilö: **[täydennä]**. Rekisterinpitäjä ja tietosuojavastaavan yhteystiedot: **[täydennä]**. Tekninen vastuuhenkilö: **[täydennä]**. Arvioinnin laatijat, kuullut tahot ja hyväksyjä: **[täydennä]**.

Arviointi koskee sanallisen palautteen luonnostelua Moodle-tehtäväaktiviteetin arviointimatriisin ja opiskelijan palautuksen perusteella. Arvioinnin teknisenä perustana ovat tämän hakemiston lähdekoodi ja dokumentaatio. Organisaation Azure-ympäristöä, sopimuksia, käytössä olevaa mallia, tietojen tosiasiallisia säilytysaikoja tai opetuskäytäntöjä ei ole tarkastettu. Myöskään mallin palautteen laatua, yhdenvertaisuutta tai käyttöliittymän saavutettavuutta ei ole tässä arvioinnissa testattu.

## 2. Käyttötarkoitus ja toiminta

Lisäosan tarkoitus on tukea opettajaa arviointikriteereihin sidotun rakentavan palautteen kirjoittamisessa. Opettaja käynnistää toiminnon opiskelijan arviointinäkymästä. Lisäosa kokoaa arviointimatriisin sekä opiskelijan online-tekstipalautuksen ja tuettujen Word- tai PDF-tiedostojen tekstisisällön ja lähettää ne määritettyyn Azure OpenAI -päätepisteeseen. Palvelu palauttaa sanallisen palauteluonnoksen, jonka opettaja voi muokata ja kopioida.

Nykyinen toiminto ei aseta arvosanaa eikä tallenna luonnosta automaattisesti opiskelijan arviointipalautteeksi. Tämä vähentää suoran automaattisen ratkaisun riskiä. Luonnos voi silti vaikuttaa opettajan käsitykseen opiskelijan osaamisesta ja lopulliseen arviointiin. Ihmisen muodollinen osallistuminen ei yksin takaa riippumatonta arviointia.

Tässä selvityksessä ehdotettu käyttörajaus on palautteen luonnostelu opettajan tarkastettavaksi. Opiskelijoiden luokittelu, paremmuusjärjestykseen asettaminen, vilpin toteaminen ja opiskeluoikeuteen vaikuttavat ratkaisut eivät kuulu tähän rajaukseen. Käytön laajentaminen edellyttää uutta arviointia.

Käyttöympäristö ja koulutusaste: **[täydennä]**. Mukana olevat kurssit ja tehtävätyypit: **[täydennä]**. Käytön alkamisajankohta, kesto ja arvioitu käyttötiheys: **[täydennä]**. Opiskelijoiden, käyttäjien ja vuosittaisten pyyntöjen määrät: **[täydennä]**.

## 3. Tekoälysäädöksen soveltuvuus

EU:n tekoälysäädöksen liitteen III kohdan 3 b mukaan oppimistulosten arviointiin tarkoitetut järjestelmät voivat kuulua suuririskisiin järjestelmiin. Tämän lisäosan palauteluonnos liittyy oppimistulosten arviointiin, joten luokittelu on selvitettävä todellisen käyttötarkoituksen perusteella. Opettajan osallistuminen ei yksin ratkaise luokittelua. Mahdollisen 6 artiklan 3 kohdan poikkeuksen edellytykset ja perustelut on dokumentoitava erikseen. Tämä selvitys ei vahvista poikkeuksen soveltumista. [Lähde: tekoälysäädös, 6 artikla ja liite III](https://eur-lex.europa.eu/eli/reg/2024/1689/oj?locale=fi).

Säädöksen 27 artikla velvoittaa siinä määritellyt käyttöönottajat tekemään perusoikeusvaikutusten arvioinnin ennen sen piiriin kuuluvan suuririskisen järjestelmän käyttöönottoa. Arviointia on tarvittaessa päivitettävä. Soveltuva ilmoitusmenettely markkinavalvontaviranomaiselle on selvitettävä ja toteutettava; FRIA-asiakirjan laatiminen ei korvaa sitä. Tietosuojaa koskeva vaikutustenarviointi voi täydentää FRIAa. [Lähde: tekoälysäädös, 27 artikla](https://eur-lex.europa.eu/eli/reg/2024/1689/oj?locale=fi).

Organisaation dokumentoitava ratkaisu: **[järjestelmän luokittelu, tarjoajan ja käyttöönottajan roolit, sovellettavat velvoitteet ja soveltamisajankohdat sekä vastuuhenkilö]**.

## 4. Vaikutusten kohteena olevat henkilöt

Ensisijaisia vaikutusten kohteita ovat opiskelijat, joiden suorituksia käsitellään. Opettajiin vaikutukset kohdistuvat työn tekemisen, vastuun ja ammatillisen harkinnan kautta. Palautuksissa voi lisäksi esiintyä muiden opiskelijoiden, asiakkaiden, potilaiden, työelämän yhteistyökumppaneiden tai tutkimukseen osallistuneiden tietoja.

Erityistä huomiota tulee kiinnittää alaikäisiin, eri kielitaustoista tuleviin opiskelijoihin, vammaisiin opiskelijoihin sekä opiskelijoihin, joilla on oppimisvaikeuksia tai yksilöllisiä järjestelyjä. Kirjoitustapa, kielitaito tai tiedoston tekninen muoto eivät saa perusteettomasti vaikuttaa osaamisesta annettuun palautteeseen. Organisaatio täydentää todelliset opiskelijaryhmät ja erityiset riskit: **[täydennä]**.

## 5. Tiedonkulku ja henkilötietojen käsittely

Palvelulle lähetetään järjestelmäohje, tekstiksi muunnettu arviointimatriisi ja palautuksesta poimittu teksti. Kutsu ei lisää erillistä opiskelijan nimeä tai käyttäjätunnusta viestikentäksi, mutta nimiä, tunnisteita ja muita henkilötietoja voi olla itse aineistossa. HTML-merkintöjen poistaminen ei anonymisoi sisältöä. Automaattista henkilötietojen tunnistamista tai poistamista ei ole toteutettu.

DOCX-poiminta lukee myös esimerkiksi ylä- ja alatunnisteita, alaviitteitä, loppuviitteitä ja kommentteja. Siksi lähetettävä sisältö voi olla laajempi kuin opiskelijan varsinainen vastausteksti. DOC- ja PDF-poiminta voi olla puutteellista. Skannattujen PDF-tiedostojen tekstintunnistusta ei ole toteutettu. Kuviin ja asetteluun sisältyvä tieto voi jäädä palautteenannon ulkopuolelle.

Lisäosa ei tallenna palautustekstiä tai generoitua palautetta omiin tietokantatauluihinsa. Se kirjaa kuitenkin Moodlen tapahtumalokiin pyynnön käynnistymisen sekä onnistumisen tai epäonnistumisen. Tapahtumiin liittyy opiskelijan ja käyttäjän tunnisteita, tehtävä- ja kontekstitietoja, palvelun isäntänimi sekä pyynnön yhdistävä tunniste. Tapahtumiin ei lisätä palautuksen tai luonnoksen sisältöä. Lokit ovat silti henkilötietojen käsittelyä ja mahdollistavat käsittelytapahtumien yhdistämisen henkilöihin.

Opettajan kopioima ja tallentama lopullinen palaute kuuluu sen Moodle-toiminnon käsittelyyn, johon se tallennetaan. Lisäksi on huomioitava selain, leikepöytä, palvelimen väliaikaistiedostot, varmuuskopiot sekä mahdolliset virhe- ja infrastruktuurilokit. Palvelun virhevastauksia voidaan välittää käyttöliittymään; niiden mahdollinen arkaluonteinen sisältö tulee tarkistaa.

Organisaation on ennen oikean opiskelija-aineiston käsittelyä dokumentoitava käsittelyn tarkoitus ja oikeusperuste, käsittelijät ja alikäsittelijät, sopimukset, käsittelyalueet, mahdolliset kansainväliset siirrot sekä poisto- ja säilytyskäytännöt. Azure-palvelun tietojen käyttöä, valvontaa tai säilytystä koskevia ehtoja ei voi päätellä lisäosan koodista. Myös tietosuojaa koskevan vaikutustenarvioinnin tarve on ratkaistava. Viittaus organisaation tietosuojadokumentaatioon: **[täydennä]**.

## 6. Perusoikeuksiin kohdistuvat riskit

Seuraavat arviot ovat laadullisia ennakkoarvioita. Merkittävä riski tarkoittaa mahdollisuutta vaikuttaa olennaisesti opiskelijan oikeuksiin tai paljastaa suojattavaa tietoa. Todennäköisyyksiä ja jäännösriskejä ei ole vahvistettu käyttötesteillä.

| Oikeus tai etu | Mahdollinen haitta | Tarvittava hallinta |
| --- | --- | --- |
| Yhdenvertaisuus ja syrjimättömyys | Malli tulkitsee kielitaidon, murteen, kirjoitustavan tai vamman vaikutuksen osaamispuutteeksi. | Vertailu eri kielillä ja kirjoitustavoilla; opettajan tarkastus; yksilöllisten järjestelyjen huomiointi ilman tarpeettomien arkaluonteisten tietojen lähettämistä. |
| Oikeus koulutukseen ja asianmukaiseen arviointiin | Virheellinen tai keksitty palaute ohjaa oppimista väärin tai vaikuttaa arvosanaan. | Jokaisen väitteen vertailu alkuperäiseen palautukseen ja kriteereihin; opettajan itsenäinen arvio; mahdollisuus uuteen ihmisen tekemään tarkastukseen. |
| Yksityisyys ja henkilötietojen suoja | Opiskelijan tai sivullisen tietoja siirtyy tarpeettomasti ulkopuoliseen palveluun. | Aineiston minimointi, sallittujen tehtävätyyppien rajaus, sopimusten ja palveluasetusten tarkastus sekä lokien käyttöoikeuksien ja säilytysaikojen määrittely. |
| Ihmisarvo ja hyvinvointi | Luonnos sisältää leimaavaa, vähättelevää tai henkilöön kohdistuvaa palautetta. | Opettaja poistaa henkilöä koskevat päätelmät ja varmistaa palautteen asiallisuuden ennen jakamista. |
| Saavutettavuus ja yhdenvertainen osallistuminen | Tekstin poiminta ohittaa kuvia, taulukoita tai erityisiä esitystapoja; käyttöliittymä ei toimi apuvälineellä. | Edustavien tiedostojen ja apuvälineiden testaus; alkuperäisen palautuksen tarkastus; toimiva palautteenanto ilman lisäosaa. |
| Oikeus saada asia tarkasteltavaksi | Opiskelija ei tiedä tekoälyn osuudesta tai ei pysty kyseenalaistamaan virhettä. | Ennakkoinformointi, nimetty yhteyshenkilö, selkeä korjaus- ja oikaisumenettely sekä ihmisen tekemä uudelleenarviointi. |
| Opettajan harkinta ja vastuu | Sujuvasti kirjoitettu luonnos ankkuroidaan lopulliseksi palautteeksi ilman kriittistä lukemista. | Koulutus, riittävä tarkastusaika ja käytäntö, jossa opettaja perustaa arvionsa alkuperäiseen aineistoon. |

Virheellisen palautteen, syrjivien vaikutusten ja tietojen tarpeettoman luovutuksen mahdolliset seuraukset arvioidaan tässä merkittäviksi. Puutteellinen tekstinpoiminta voi voimistaa näitä riskejä. Palautukseen sisällytetyt ohjeet voivat myös pyrkiä muuttamaan mallin toimintaa eli aiheuttaa kehoteinjektion. Luonnos on tämänkin vuoksi tarkastettava, vaikka järjestelmäohje kieltää sisällön keksimisen.

## 7. Toteutetut suojaukset ja niiden rajat

Lähdekoodissa on kirjautumisen, istuntoavaimen, kontekstin ja käyttöoikeuksien tarkistukset. Palautteen generointi edellyttää sekä tehtävän arviointioikeutta että lisäosan generointioikeutta. Generointioikeus on oletuksena muokkaavalla opettajalla ja managerilla. Kelpoisuustarkistus edellyttää arviointimatriisia ja luettavaa palautussisältöä.

Palauteluonnos näytetään opettajalle muokattavassa kentässä. Sen käyttö arviointipalautteena edellyttää erillistä kopiointia ja tallennusta. Järjestelmäohje pyrkii rajaamaan palautteen arviointikriteereihin ja estämään keksityn sisällön. Pyyntöjen tapahtumakirjaus tukee käsittelyn jälkikäteistä selvittämistä.

Nämä mekanismit eivät varmista palautteen oikeellisuutta tai opettajan tosiasiallista tarkastusta. Käyttöliittymä ei vaadi tarkastuksen kuittausta. Tapahtumaloki ei tallenna käytettyä luonnosta, malliversiota tai opettajan tekemää tarkastusta, joten se ei yksin mahdollista palautteen sisällön jälkikäteistä todentamista. Tokeniraja on tekninen kokoraja, ei tietojen minimointikeino tai laadun tae. Asetettavan päätepisteen turvallisuus ja tarkoituksenmukaisuus on varmistettava erikseen.

## 8. Ihmisen valvonta ja opiskelijan oikeussuojakeinot

Ehdotettu toimintamalli edellyttää, että opettaja lukee alkuperäisen palautuksen ja tarkistaa luonnoksen jokaisen arviointikriteerin osalta. Opettaja vastaa lopullisesta palautteesta ja arvioinnista, voi hylätä luonnoksen kokonaan ja antaa palautteen ilman tekoälyä. Virheellistä tai epäasiallista luonnosta ei välitetä opiskelijalle.

Opiskelijalle kerrotaan ennen käsittelyä tekoälyn käyttötarkoitus, lähetettävän aineiston sisältö, ulkopuolinen palvelu, opettajan rooli sekä yhteys- ja korjauskanavat. Organisaation tulee määritellä menettely tilanteisiin, joissa opiskelija esittää perustellun huolen käsittelystä tai tarvitsee vaihtoehtoisen toteutustavan. Lisäosa ei itsessään toteuta opiskelijan informointia tai tällaista menettelyä.

Virheelliseksi koettua palautetta koskeva pyyntö osoitetaan **[yhteyshenkilö ja kanava]**. Arvioinnin oikaisu käsitellään organisaation menettelyn mukaisesti: **[menettely ja määräajat]**. Tietosuojaa koskevat pyynnöt ohjataan **[yhteystiedot]**. Vastuuhenkilö tarkistaa asian alkuperäisestä aineistosta ja dokumentoi korjauksen tarpeellisessa laajuudessa.

## 9. Ennen käyttöönottoa toteutettavat toimenpiteet

Seuraavat ovat tämän arvioinnin ehdottamia hyväksymisehtoja; niiden toteutumisesta ei ole tässä selvityksessä näyttöä.

1. Nimetään vastuuhenkilöt ja dokumentoidaan käyttötarkoitus, kohderyhmät, käyttömäärät ja tekoälysäädöksen mukainen luokittelu.
2. Hyväksytään tietosuojan ja tietoturvan järjestelyt, Azure-päätepiste, käytetty malli ja sen versio, käsittelyalueet, sopimukset sekä säilytysajat. Varmistetaan suojattu tiedonsiirto ja API-avaimen asianmukainen hallinta.
3. Rajataan aineisto niin, ettei palveluun lähetetä tarpeettomia henkilötietoja, salassa pidettäviä tietoja tai asiakas- ja potilasaineistoja. Henkilötietojen poistamista ei oleteta automaattiseksi.
4. Testataan aluksi synteettisellä aineistolla palautteen oikeellisuutta, eri kieliä, yksilöllisiä esitystapoja, DOC/DOCX/PDF-poimintaa, kehoteinjektioita ja saavutettavuutta. Määritellään hyväksyttävän laadun kriteerit ja dokumentoidaan tulokset.
5. Koulutetaan opettajat tarkastamaan luonnokset, tunnistamaan vinoumat ja toimimaan virhetilanteissa. Julkaistaan opiskelijainformointi ja korjausmenettely.
6. Määritellään poikkeamien ilmoittaminen, vastuuhenkilöt, käytön keskeyttämisen kriteerit ja palautteenannon jatkaminen ilman lisäosaa. Sovitaan, mitä näyttöä poikkeamasta säilytetään ja kuinka henkilötietojen tarpeeton tallentaminen estetään.

Toimenpiteiden vastuuhenkilöt, määräajat ja todentavat asiakirjat: **[täydennä]**.

## 10. Seuranta, jäännösriskit ja hyväksyminen

Pilotissa seurataan palauteluonnosten asiavirheitä, opettajien tekemiä olennaisia korjauksia, tiedostojen poimintavirheitä, saavutettavuusongelmia, opiskelijoiden yhteydenottoja ja tietosuojapoikkeamia. Seurannan laajuus ja otanta päätetään etukäteen, ja seurannassa vältetään tarpeettoman opiskelija-aineiston keräämistä. Opiskelijoiden ja opettajien kokemuksia käytetään arvioinnin täydentämiseen.

Arviointi päivitetään, kun malli, järjestelmäohje, lisäosan toiminta, tietojen käsittelyalue, kohderyhmä tai käyttötarkoitus muuttuu olennaisesti tai ilmenee uusi haitta. Seuraavan tarkistuksen ajankohta: **[täydennä]**. Toistuvat vakavat asiavirheet, syrjivät vaikutukset tai epäselvyys tietojen käsittelyn sallittavuudesta ovat peruste keskeyttää käyttö asian selvittämisen ajaksi.

Jäännösriskeiksi jäävät ainakin mallin ennakoimattomat virheet, vaikeasti havaittavat vinoumat, opettajan ankkuroituminen sekä ulkoiseen palveluun liittyvät riippuvuudet. Näiden hyväksyttävyyttä ei voida vahvistaa pelkän lähdekoodin perusteella. Organisaatio kirjaa kunkin keskeisen riskin osalta toteutetun hallinnan, testinäytön, jäljelle jäävän riskin ja hyväksymisperusteen: **[täydennä]**.

**Arvioinnin johtopäätös:** lisäosalla on mahdollisuus tukea palautteenannon laatua ja sujuvuutta, mutta toteutuneista hyödyistä ei ole tässä selvityksessä näyttöä. Käyttöönoton hyväksymisen edellytyksenä on organisaatiokohtaisten tietojen täydentäminen, edellä kuvattujen toimenpiteiden todentaminen ja jäljelle jäävien riskien arviointi. Nykyinen työnkulku tukee ihmisen valvontaa, mutta valvonnan toteutuminen on varmistettava käytännössä.

**Päätös:** [hyväksytään / hyväksytään rajattuun pilottiin / ei hyväksytä].  
**Sallittu laajuus ja ehdot:** [täydennä].  
**Hyväksyjä ja päiväys:** [täydennä].  
**Mahdollinen viranomaisilmoitus ja sen viite:** [täydennä].

## 11. Tekninen lähdeaineisto

- `classes/external/generate_feedback.php`: tietojen kokoaminen ja käyttöoikeustarkistukset.
- `classes/service/azure_feedback_service.php`: palvelukutsu ja lähetettävä viestirakenne.
- `classes/service/feedback_generator.php` ja `classes/event/`: tapahtumaloki.
- `classes/local/submission_text.php` ja PDF-tekstinpoiminnan luokat: käsiteltävä aineisto ja poiminnan rajoitteet.
- `classes/privacy/provider.php`: ulkoiseen palveluun siirtyvien tietojen metatiedot.
- `db/access.php`, `settings.php`, `lang/fi/assignfeedback_airubric.php`, `templates/modal.mustache` ja `amd/src/feedback_button.js`: roolit, asetukset, järjestelmäohje ja käyttöliittymä.

Arvioitavan tuotantoversion tai commitin tunniste: **[täydennä käyttöönoton yhteydessä]**.
