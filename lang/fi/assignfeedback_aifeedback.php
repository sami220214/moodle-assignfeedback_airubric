<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * AI feedback plugin.
 *
 * @package   assignfeedback_aifeedback
 * @copyright 2026 Sami Simpanen
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['pluginname'] = 'Tekoälyn arviointimatriisipalaute';
$string['generatefeedback'] = 'Palaute';
$string['systemprompt'] = 'Sinä toimit ammattikorkeakoulun opettajana ja annat opiskelijan palautukseen pelkän rakentavan sanallisen palautteen arviointimatriisin kaikkien kriteerien perusteella kriteereittäin ilman loppu kyselyjä. Älä käytä palautteen luomisessa ulkopuolisia lähteitä. Älä keksi sisältöä, jota opiskelijan tekstissä ei ole. Jos aineistoa puuttuu, kerro mitä puuttuu.';
$string['modaltitle'] = 'Luo sanallinen palaute arviointimatriisista';
$string['copytoclipboard'] = 'Kopioi palaute';
$string['closebutton'] = 'Sulje';
$string['suggestedfeedback'] = 'Ehdotettu palaute';
$string['generating'] = 'Luodaan palautetta...';
$string['inputtokens'] = 'Tekoälylle lähetettävät arvioidut tokenit: {$a}';
$string['inputtokenlimitexceeded'] = 'Tekoälypalautetta ei voida luoda, koska arvioitu lähetettävä tokenimäärä ({$a->tokens}) ylittää rajan ({$a->limit}).';
$string['noteligible'] = 'Tekoälypalautetta ei voida luoda: {$a}';
$string['noteligible_norubric'] = 'Tehtävässä ei ole käytössä arviointimatriisia.';
$string['noteligible_nosubmission'] = 'Opiskelijalla ei ole palautusta.';
$string['noteligible_noonlinetext'] = 'Palautus ei ole online-tekstinä.';
$string['noteligible_nosubmissiontext'] = 'Palautuksessa ei ole online-tekstiä tai luettavaa Word-/PDF-dokumentin tekstiä.';
$string['azureconfigmissing'] = 'Azure OpenAI -asetukset puuttuvat.';
$string['azurecommunicationerror'] = 'Azure OpenAI -kutsu epäonnistui: {$a}';
$string['azureinvalidresponse'] = 'Azure OpenAI palautti virheellisen vastauksen: {$a}';
$string['privacy:metadata'] = 'Tekoälyn arviointimatriisipalaute ei tallenna henkilötietoja omiin tietokantatauluihinsa.';
$string['privacy:metadata:azure_openai'] = 'Tekoälyn arviointimatriisipalaute lähettää tehtävän palautustekstin ja arviointimatriisin arviointikriteerit määritettyyn Azure OpenAI -päätepisteeseen ehdotetun sanallisen palautteen luomista varten.';
$string['privacy:metadata:azure_openai:submissiontext'] = 'Opiskelijan tehtäväpalautuksen teksti, joka on poimittu online-tekstistä, Word-dokumenteista tai tekstipohjaisista PDF-tiedostoista.';
$string['privacy:metadata:azure_openai:rubric'] = 'Tehtävän arviointimatriisin kriteerit ja arviointirakenne, joita käytetään palautteen luonnin kontekstina.';
$string['azureendpoint'] = 'Azure OpenAI -päätepiste';
$string['azureendpoint_desc'] = 'Esimerkki: https://your-resource.openai.azure.com';
$string['azureapikey'] = 'Azure OpenAI API-avain';
$string['azureapikey_desc'] = 'API-avain Azure OpenAI -käyttöönotolle.';
$string['azuredeployment'] = 'Azure käyttöönoton nimi';
$string['azuredeployment_desc'] = 'Käyttöönotto, esimerkiksi gpt-5-mini.';
$string['azureapiversion'] = 'Azure API-versio';
$string['azureapiversion_desc'] = 'Esimerkiksi 2024-12-01-preview';
$string['buttondisabledreason'] = 'Palautepainike ei ole aktiivinen: {$a}';
$string['enabled'] = 'Tekoälyn arviointimatriisipalaute';
$string['enabled_help'] = 'Jos tämä on käytössä, tekoälyn arviointimatriisipalautetta voidaan käyttää tehtävän palautteenannossa. Online-teksti-, Word-dokumentti- tai tekstipohjainen PDF-palautus ja arviointimatriisi vaaditaan, jotta tekoälyapuria voidaan käyttää.';
