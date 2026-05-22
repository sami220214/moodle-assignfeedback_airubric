<?php
// This file is part of Moodle - http://moodle.org/

$string['pluginname'] = 'AI feedback assistant';
$string['generatefeedback'] = 'Feedback';
$string['systemprompt'] = 'You act as a lecturer at a university of applied sciences and provide the student with only written (verbal) feedback based on the assessment rubric every criteria in English only, without any concluding questions :';
$string['modaltitle'] = 'Generate verbal feedback from rubric';
$string['copytoclipboard'] = 'Copy feedback';
$string['closebutton'] = 'Close';
$string['suggestedfeedback'] = 'Suggested feedback';
$string['generating'] = 'Generating feedback...';
$string['inputtokens'] = 'Estimated input tokens sent to AI: {$a}';
$string['inputtokenlimitexceeded'] = 'AI feedback cannot be generated because the estimated input token count ({$a->tokens}) exceeds the limit ({$a->limit}).';
$string['noteligible'] = 'AI feedback cannot be generated: {$a}';
$string['noteligible_norubric'] = 'Rubric grading method is not enabled for this assignment.';
$string['noteligible_nosubmission'] = 'Student has not submitted anything yet.';
$string['noteligible_noonlinetext'] = 'Student submission is not available as online text.';
$string['noteligible_nosubmissiontext'] = 'Student submission does not contain online text or readable Word/PDF document text.';
$string['azureconfigmissing'] = 'Azure OpenAI settings are incomplete.';
$string['azurecommunicationerror'] = 'Azure OpenAI request failed: {$a}';
$string['azureinvalidresponse'] = 'Azure OpenAI returned an invalid response: {$a}';
$string['privacy:metadata'] = 'The AI feedback assistant plugin does not store personal data.';
$string['azureendpoint'] = 'Azure OpenAI endpoint';
$string['azureendpoint_desc'] = 'Example: https://your-resource.openai.azure.com';
$string['azureapikey'] = 'Azure OpenAI API key';
$string['azureapikey_desc'] = 'API key for the Azure OpenAI deployment.';
$string['azuredeployment'] = 'Azure deployment name';
$string['azuredeployment_desc'] = 'Deployment name, for example gpt-5-mini.';
$string['azureapiversion'] = 'Azure API version';
$string['azureapiversion_desc'] = 'Example: 2024-12-01-preview';
$string['buttondisabledreason'] = 'Feedback button is disabled: {$a}';
$string['enabled'] = 'AI feedback assistant';
$string['enabled_help'] = 'If enabled, AI feedback assistant can be used in assignment feedback. Online text, Word document, or text-based PDF submissions and rubric grading method are required for AI feedback to be available.';
