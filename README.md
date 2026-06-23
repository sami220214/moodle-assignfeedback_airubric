# assignfeedback_airubric: tekninen dokumentaatio

## 1. Tarkoitus
`assignfeedback_airubric` on Moodle-tehtavaaktiviteetin palautelaajennus, joka tuottaa opettajalle sanallisen palauteluonnoksen:
- arviointimatriisin (rubric) perusteella
- opiskelijan online-tekstipalautuksen, Word-dokumentin (`.docx`/`.doc`) tai tekstipohjaisen PDF-dokumentin (`.pdf`) perusteella

Laajennus ei tallenna generoituja palautteita omaan tietokantarakenteeseen.

## 2. Versio ja yhteensopivuus
- Komponentti: `assignfeedback_airubric`
- Lisäosan tyyppi: `assignfeedback`
- Tekninen nimi: `airubric`
- Frankenstyle-komponentti: `assignfeedback_airubric`
- Asennuspolku: `mod/assign/feedback/airubric/`
- Versio: `2026062300`
- Release: `1.0.0`
- Vaatii vahintaan Moodlen: `4.5` (`2024100700`)
- Kypsyys: `MATURITY_STABLE`

Lahde: `version.php`.

## 3. Korkean tason toiminta
1. Opettaja avaa opiskelijan arviointinakymaan tehtavassa.
2. `locallib.php` renderoi painikkeen ja modalin, jos opettajalla on capability `assignfeedback/airubric:generate`.
3. Kelpoisuus tarkistetaan:
   - tehtavassa on kaytossa rubric-arviointi
   - opiskelijalla on palautus
   - palautuksessa on online-teksti, luettava Word-dokumentti tai tekstipohjainen PDF
4. Opettaja painaa palautepainiketta.
5. AMD-moduuli kutsuu AJAX-endpointia `assignfeedback_airubric_generate_feedback`.
6. External API hakee rubric-sisallon, opiskelijan online-tekstin ja/tai Word-/PDF-dokumentin tekstisisallon ja kutsuu Azure OpenAI -palvelua.
7. Generoitu teksti naytetaan modalin tekstialueessa ja voidaan kopioida leikepoydalle.

## 4. Arkkitehtuuri

### 4.1 Palvelin (PHP)
- `locallib.php`
  - maarittelee `assign_feedback_airubric`-pluginin
  - tarkistaa capabilityn ja kelpoisuuden
  - renderoi UI:n (`templates/modal.mustache`)
  - lataa AMD-moduulin `assignfeedback_airubric/feedback_button`
- `classes/local/eligibility.php`
  - kapseloi kelpoisuussaannot
  - tarkistaa rubricin, palautuksen olemassaolon ja luettavan palautustekstin
- `classes/local/submission_text.php`
  - kokoaa online-tekstin ja tuettujen dokumenttitiedostojen tekstisisallon yhdeksi palautustekstiksi
  - lukee `.docx`-tiedostot OOXML-sisallosta ja `.doc`-tiedostot PHP-pohjaisena best-effort-tekstipoimintana
  - lukee tekstipohjaiset `.pdf`-tiedostot PDF:n tekstikerroksesta best-effort-tekstipoimintana; skannatut PDF:t vaativat OCR:n, eivatka kuulu nykyiseen tukeen
- `classes/external/generate_feedback.php`
  - AJAX-kutsuttava external API
  - validoi parametrit, kontekstin ja capabilityt
  - renderoi rubricin grading-controllerin kautta (fallback eri Moodle-signatuureille)
  - hakee opiskelijan palautustekstin online-tekstista ja/tai Word-/PDF-tiedostoista
  - kutsuu Azure-palvelua
- `classes/service/azure_feedback_service.php`
  - muodostaa HTTP-kutsun Azure OpenAI Chat Completions -rajapintaan
  - kasittelee cURL- ja vastemuotovirheet

### 4.2 UI (Mustache + AMD)
- `templates/modal.mustache`
  - painike, modal, status-alue, textarea ja kopiointipainike
  - nayttaa ei-kelpoisessa tilanteessa disabloidun painikkeen ja syyn
- `amd/src/feedback_button.js` (+ `amd/build/feedback_button.min.js`)
  - avaa modalin (Bootstrap 5 API, jQuery modal fallback, viimeisena DOM-fallback)
  - tekee AJAX-kutsun
  - nayttaa generointistatuksen
  - nayttaa virheet `core/notification`-poikkeuksena
  - kopioi tekstin leikepoydalle (`navigator.clipboard`, fallback `execCommand`)

## 5. Oikeudet ja rekisterointi
- AJAX-funktio: `db/services.php`
  - `assignfeedback_airubric_generate_feedback`
  - tyyppi `write`, `ajax => true`
- Capability: `db/access.php`
  - `assignfeedback/airubric:generate`
  - oletuksena sallittu rooleille `editingteacher` ja `manager`
- External API vaatii:
  - kirjautuneen käyttäjän (`require_login`)
  - voimassa olevan sesskeyn (`require_sesskey`)
  - `mod/assign:grade`
  - `assignfeedback/airubric:generate`

## 6. Asetukset
Pluginin admin-asetukset (`settings.php`):
- `assignfeedback_airubric/azureendpoint`
- `assignfeedback_airubric/azureapikey`
- `assignfeedback_airubric/azuredeployment` (oletus `gpt-5-mini`)
- `assignfeedback_airubric/azureapiversion` (oletus `2024-12-01-preview`)

## 7. Azure-integraatio
`azure_feedback_service::generate($rubric, $submission)`:
- rakentaa URL:n:
  - `{endpoint}/openai/deployments/{deployment}/chat/completions?api-version={apiversion}`
- lahettaa payloadin:
  - `messages`: `system` + `user`
  - `max_completion_tokens`: `7096`
- asettaa headerit:
  - `Content-Type: application/json`
  - `api-key: <azureapikey>`
- timeout: `45` s

Lahetettava sisalto muodostetaan external-luokassa:
- rubric: `strip_tags($rubric)`
- opiskelijan teksti: online-teksti ja/tai Word-/PDF-dokumentista luettu teksti

## 8. Virheenkasittely
Backend:
- puuttuva konfiguraatio -> `azureconfigmissing`
- cURL-virhe -> `azurecommunicationerror`
- Azure JSON error (`error.message`) -> `azurecommunicationerror`
- puutteellinen vastemuoto -> `azureinvalidresponse`
- ei-kelpoinen tapaus -> `noteligible`

Frontend:
- AJAX-virhe normalisoidaan (`toException`) ja naytetaan `Notification.exception(...)` kautta.
- Virhetta ei kirjoiteta modaliin tekstialueeseen; status-rivi tyhjennetaan virhetilassa.

## 9. Tietosuoja
`classes/privacy/provider.php` toteuttaa Moodlen Privacy API:n metadata-providerin:
- plugin ei tallenna henkilotietoja omiin tietokantatauluihinsa.
- plugin kuvaa ulkoisen sijainnin `azure_openai` Privacy API:n `add_external_location_link` -metadatalla.

Ulkoiseen Azure OpenAI -palveluun lahetetaan palauteluonnoksen luomista varten:
- opiskelijan palautusteksti, joka poimitaan online-tekstista, Word-dokumenteista tai tekstipohjaisista PDF-tiedostoista
- tehtavan arviointimatriisin kriteerit ja arviointirakenne

Huomio:
- generoitu palaute naytetaan opettajalle, mutta sita ei tallenneta automaattisesti pluginin omaan tietokantaan.
- Azure OpenAI -paatepiste maaritetaan admin-asetuksissa, joten tietosuojavaatimukset, sopimukset, alueellinen sijainti ja organisaation kaytannot on varmistettava kayttoonotossa.

## 10. Tunnetut rajoitteet
1. Tuki koskee rubric-arviointia seka online-tekstipalautusta, Word-tiedostopalautusta (`.docx`/`.doc`) tai tekstipohjaista PDF-palautusta (`.pdf`); skannatut PDF:t ja muut palautustyypit eivat kuulu nykyiseen polkuun.
2. Generoitu palaute ei tallennu automaattisesti arviointipalautteeksi, vaan opettaja kopioi sen manuaalisesti.
3. Prompti ei ole erillisena admin-asetuksena, vaan kielijonossa (`systemprompt`).
4. Plugin-hakemistossa ei ole varsinaista automatisoitua testipakettia (unit/integration).

## 11. Kayttoonoton tarkistuslista
1. Asenna plugin Moodleen.
2. Aseta Azure-asetukset (`endpoint`, `apikey`, `deployment`, `api version`).
3. Varmista capability `assignfeedback/airubric:generate` tarvittaville rooleille.
4. Varmista, etta tehtava-aktiviteetissa kaytetaan rubric-arviointia.
5. Varmista, etta opiskelijalla on online-tekstipalautus, Word-dokumenttipalautus tai tekstipohjainen PDF-palautus.
6. Testaa generointi opettajan nakymasta.
7. Tarkista Moodle-lokit virhetilanteissa.

## 12. Tiedostokartta
- `version.php`
- `README.md`
- `README_EN.md`
- `LICENSE`
- `settings.php`
- `locallib.php`
- `db/access.php`
- `db/services.php`
- `classes/local/eligibility.php`
- `classes/local/submission_text.php`
- `classes/external/generate_feedback.php`
- `classes/service/azure_feedback_service.php`
- `classes/privacy/provider.php`
- `templates/modal.mustache`
- `amd/src/feedback_button.js`
- `amd/build/feedback_button.min.js`
- `lang/fi/assignfeedback_airubric.php`
- `lang/en/assignfeedback_airubric.php`

## 13. Lisenssi ja kolmannen osapuolen kirjastot
Kaikki pluginin PHP-tiedostot on lisensoitu GNU GPL v3 or later -lisenssillä Moodle-otsikon mukaisesti. Pluginin mukana ei toimiteta erillisiä kolmannen osapuolen kirjastoja; AMD-moduuli käyttää Moodlen tarjoamia `core/ajax`, `core/notification` ja `jquery` -moduuleja sekä käyttöliittymässä Moodlen/teeman Bootstrap-modal API:a. Tämän vuoksi erillistä `thirdpartylibs.xml`-tiedostoa ei tarvita nykyisessä paketissa.
