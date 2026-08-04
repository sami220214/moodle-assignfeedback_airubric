# assignfeedback_airubric: tekninen dokumentaatio

## 1. Tarkoitus
`assignfeedback_airubric` on Moodle-tehtäväaktiviteetin palautelaajennus, joka tuottaa opettajalle sanallisen palauteluonnoksen:
- arviointimatriisin (rubric) perusteella
- opiskelijan online-tekstipalautuksen, Word-dokumentin (`.docx`/`.doc`) tai tekstipohjaisen PDF-dokumentin (`.pdf`) perusteella

Laajennus näyttää opettajalle ehdotetun palautteen ja arvioidun tekoälylle lähetettävän syötetokenien määrän. Generoitua palautetta ei tallenneta automaattisesti pluginin omaan tietokantaan.

## 2. Versio ja yhteensopivuus
- Komponentti: `assignfeedback_airubric`
- Lisäosan tyyppi: `assignfeedback`
- Tekninen nimi: `airubric`
- Frankenstyle-komponentti: `assignfeedback_airubric`
- Asennuspolku: `mod/assign/feedback/airubric/`
- Versio: `2026080400`
- Release: `1.0.1`
- Vaatii vähintään Moodlen: `4.5` (`2024100700`)
- Kypsyys: `MATURITY_STABLE`

Lähde: `version.php`.

## 3. Korkean tason toiminta
1. Opettaja avaa opiskelijan arviointinäkymän tehtävässä.
2. `locallib.php` renderöi painikkeen ja modalin, jos opettajalla on capability `assignfeedback/airubric:generate`.
3. Kelpoisuus tarkistetaan:
   - tehtävässä on käytössä rubric-arviointi
   - opiskelijalla on palautus
   - palautuksessa on online-teksti, luettava Word-dokumentti tai tekstipohjainen PDF
4. Opettaja painaa palautepainiketta.
5. AMD-moduuli kutsuu AJAX-endpointia `assignfeedback_airubric_generate_feedback`.
6. External API hakee rubric-sisällön, opiskelijan online-tekstin ja/tai Word-/PDF-dokumentin tekstisisällön.
7. Palvelu arvioi syötetokenien määrän ja estää kutsun, jos arvio ylittää `100000` tokenia.
8. Azure OpenAI tuottaa palauteluonnoksen.
9. Generoitu teksti ja tokeniarvio näytetään modalissa, ja palaute voidaan kopioida leikepöydälle.

## 4. Arkkitehtuuri

### 4.1 Palvelin (PHP)
- `locallib.php`
  - määrittelee `assign_feedback_airubric`-pluginin
  - tarkistaa capabilityn ja kelpoisuuden
  - renderöi UI:n (`templates/modal.mustache`)
  - lataa AMD-moduulin `assignfeedback_airubric/feedback_button`
- `classes/local/eligibility.php`
  - kapseloi kelpoisuussäännöt
  - tarkistaa rubricin, palautuksen olemassaolon ja luettavan palautustekstin
- `classes/local/submission_text.php`
  - kokoaa online-tekstin ja tuettujen dokumenttitiedostojen tekstisisällön yhdeksi palautustekstiksi
  - lukee `.docx`-tiedostot OOXML-sisällöstä ja `.doc`-tiedostot PHP-pohjaisena best-effort-tekstipoimintana
  - lukee tekstipohjaiset `.pdf`-tiedostot PDF:n tekstikerroksesta best-effort-tekstipoimintana; skannatut PDF:t vaativat OCR:n eivätkä kuulu nykyiseen tukeen
- `classes/external/generate_feedback.php`
  - AJAX-kutsuttava External API
  - validoi parametrit, kontekstin ja capabilityt
  - renderöi rubricin grading-controllerin kautta, mukana fallback eri Moodle-signatuureille
  - hakee opiskelijan palautustekstin online-tekstistä ja/tai Word-/PDF-tiedostoista
  - kutsuu Azure-palvelua
  - palauttaa generoidun tekstin ja arvioidun syötetokenien määrän
- `classes/service/azure_feedback_service.php`
  - muodostaa HTTP-kutsun Azure OpenAI Chat Completions -rajapintaan
  - arvioi syötetokenien määrän ennen kutsua
  - estää liian suuret pyynnöt `100000` tokenin rajalla
  - käsittelee cURL- ja vastemuotovirheet

### 4.2 UI (Mustache + AMD)
- `templates/modal.mustache`
  - painike, modal, status-alue, textarea ja kopiointipainike
  - näyttää ei-kelpoisessa tilanteessa disabloidun painikkeen ja syyn
- `amd/src/feedback_button.js` (+ `amd/build/feedback_button.min.js`)
  - avaa modalin Bootstrap 5 API:lla, jQuery modal -fallbackilla tai DOM-fallbackilla
  - tekee AJAX-kutsun
  - näyttää generointistatuksen
  - näyttää generoidun palautteen ja arvioidun syötetokenien määrän
  - näyttää virheet `core/notification`-poikkeuksena
  - kopioi tekstin leikepöydälle (`navigator.clipboard`, fallback `execCommand`)

## 5. Oikeudet ja rekisteröinti
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
`azure_feedback_service::generate_with_usage($rubric, $submission)`:
- rakentaa URL:n:
  - `{endpoint}/openai/deployments/{deployment}/chat/completions?api-version={apiversion}`
- lähettää payloadin:
  - `messages`: `system` + `user`
  - `max_completion_tokens`: `7096`
- arvioi `messages`-sisällön tokenimäärän deterministisellä sanapohjaisella arviolla
- keskeyttää pyynnön ennen Azure-kutsua, jos arvioitu syöte ylittää `100000` tokenia
- asettaa headerit:
  - `Content-Type: application/json`
  - `api-key: <azureapikey>`
- timeout: `45` s
- palauttaa:
  - `feedback`: generoitu palaute
  - `inputtokens`: arvioitu syötetokenien määrä

Lähetettävä sisältö muodostetaan external-luokassa:
- rubric: `strip_tags($rubric)`
- opiskelijan teksti: online-teksti ja/tai Word-/PDF-dokumentista luettu teksti

## 8. Virheenkäsittely
Backend:
- puuttuva konfiguraatio -> `azureconfigmissing`
- liian suuri arvioitu syöte -> `inputtokenlimitexceeded`
- cURL-virhe -> `azurecommunicationerror`
- Azure JSON error (`error.message`) -> `azurecommunicationerror`
- puutteellinen vastemuoto -> `azureinvalidresponse`
- ei-kelpoinen tapaus -> `noteligible`

Frontend:
- AJAX-virhe normalisoidaan (`toException`) ja näytetään `Notification.exception(...)` kautta.
- Virhettä ei kirjoiteta modaliin tekstialueeseen; status-rivi tyhjennetään virhetilassa.

## 9. Tietosuoja
`classes/privacy/provider.php` toteuttaa Moodlen Privacy API:n metadata-providerin:
- plugin ei tallenna henkilötietoja omiin tietokantatauluihinsa
- plugin kuvaa ulkoisen sijainnin `azure_openai` Privacy API:n `add_external_location_link` -metadatalla

Ulkoiseen Azure OpenAI -palveluun lähetetään palauteluonnoksen luomista varten:
- opiskelijan palautusteksti, joka poimitaan online-tekstistä, Word-dokumenteista tai tekstipohjaisista PDF-tiedostoista
- tehtävän arviointimatriisin kriteerit ja arviointirakenne

Huomio:
- generoitu palaute näytetään opettajalle, mutta sitä ei tallenneta automaattisesti pluginin omaan tietokantaan
- Azure OpenAI -päätepiste määritetään admin-asetuksissa, joten tietosuojavaatimukset, sopimukset, alueellinen sijainti ja organisaation käytännöt on varmistettava käyttöönotossa

## 10. Tunnetut rajoitteet
1. Tuki koskee rubric-arviointia sekä online-tekstipalautusta, Word-tiedostopalautusta (`.docx`/`.doc`) tai tekstipohjaista PDF-palautusta (`.pdf`); skannatut PDF:t ja muut palautustyypit eivät kuulu nykyiseen polkuun.
2. Generoitu palaute ei tallennu automaattisesti arviointipalautteeksi, vaan opettaja kopioi sen manuaalisesti.
3. Prompti ei ole erillisenä admin-asetuksena, vaan kielijonossa (`systemprompt`).
4. Tokenimäärä on arvio, koska tarkka tokenointi riippuu käytettävästä Azure OpenAI -mallista.
5. Plugin-hakemistossa ei ole varsinaista automatisoitua testipakettia (unit/integration). Repossa on kuitenkin GitHub Actions -CI työnkulku Moodle Plugin CI -tarkistuksille.

## 11. Käyttöönoton tarkistuslista
1. Asenna plugin Moodleen polkuun `mod/assign/feedback/airubric/`.
2. Aja Moodlen päivitys, jotta versio `2026080400` rekisteröityy.
3. Aseta Azure-asetukset (`endpoint`, `apikey`, `deployment`, `api version`).
4. Varmista capability `assignfeedback/airubric:generate` tarvittaville rooleille.
5. Varmista, että tehtäväaktiviteetissa käytetään rubric-arviointia.
6. Varmista, että opiskelijalla on online-tekstipalautus, Word-dokumenttipalautus tai tekstipohjainen PDF-palautus.
7. Testaa generointi opettajan arviointinäkymästä.
8. Tarkista Moodle-lokit virhetilanteissa.

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
- `.github/workflows/ci.yml`

## 13. CI
`.github/workflows/ci.yml` ajaa Moodle Plugin CI -tarkistukset GitHub Actionsissa push- ja pull request -tapahtumissa.

Matriisi:
- PHP: `8.1`, `8.2`, `8.3`
- Moodle: `MOODLE_405_STABLE`
- Tietokannat: `pgsql`, `mariadb`

Tarkistuksiin kuuluvat PHP lint, PHP Mess Detector, Moodle Code Checker, PHPDoc Checker, validate, savepoints, Mustache lint, Grunt, PHPUnit ja Behat.

## 14. Lisenssi ja kolmannen osapuolen kirjastot
Kaikki pluginin PHP-tiedostot on lisensoitu GNU GPL v3 or later -lisenssillä Moodle-otsikon mukaisesti. Pluginin mukana ei toimiteta erillisiä kolmannen osapuolen kirjastoja; AMD-moduuli käyttää Moodlen tarjoamia `core/ajax`, `core/notification` ja `jquery` -moduuleja sekä käyttöliittymässä Moodlen/teeman Bootstrap-modal API:a. Tämän vuoksi erillistä `thirdpartylibs.xml`-tiedostoa ei tarvita nykyisessä paketissa.
