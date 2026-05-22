<?php
// This file is part of Moodle - http://moodle.org/

$string['pluginname'] = 'Tekoälypalaute avustaja';
$string['generatefeedback'] = 'Palaute';
$string['systemprompt'] = 'Sinä toimit ammattikorkeakoulun opettajana ja annat opiskelijan palautukseen pelkän sanallisen palautteen arviointimatriisin kaikkien kriteerien perusteella kriteereittäin ilman loppu kyselyjä: ';
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
$string['privacy:metadata'] = 'Tekoälypalaute avustaja ei tallenna henkilötietoja.';
$string['azureendpoint'] = 'Azure OpenAI -päätepiste';
$string['azureendpoint_desc'] = 'Esimerkki: https://your-resource.openai.azure.com';
$string['azureapikey'] = 'Azure OpenAI API-avain';
$string['azureapikey_desc'] = 'API-avain Azure OpenAI -käyttöönotolle.';
$string['azuredeployment'] = 'Azure käyttöönoton nimi';
$string['azuredeployment_desc'] = 'Käyttöönotto, esimerkiksi gpt-5-mini.';
$string['azureapiversion'] = 'Azure API-versio';
$string['azureapiversion_desc'] = 'Esimerkiksi 2024-12-01-preview';
$string['buttondisabledreason'] = 'Palautepainike ei ole aktiivinen: {$a}';
$string['enabled'] = 'Tekoälypalaute avustaja';
$string['enabled_help'] = 'Jos tämä on käytössä, tekoälypalauteavustajaa voidaan käyttää tehtävän palautteenannossa. Online-teksti-, Word-dokumentti- tai tekstipohjainen PDF-palautus ja arviointimatriisi vaaditaan, jotta tekoälyapuria voidaan käyttää.';
