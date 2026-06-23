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
 * @package   assignfeedback_airubric
 * @copyright 2026 Sami Simpanen
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['pluginname'] = 'AI Rubric Feedback';
$string['generatefeedback'] = 'Feedback';
$string['systemprompt'] = 'You act as a university of applied sciences teacher and provide only constructive written feedback on a student submission, based on all criteria of the assessment matrix, criterion by criterion, without any concluding questions. Do not use external sources when generating the feedback. Do not invent content that is not present in the student\'s text. If material is missing, clearly state what is missing.';
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
$string['privacy:metadata'] = 'The AI Rubric Feedback plugin does not store personal data in its own database tables.';
$string['privacy:metadata:azure_openai'] = 'AI Rubric Feedback sends assignment submission text and rubric assessment criteria to the configured Azure OpenAI endpoint to generate suggested written feedback.';
$string['privacy:metadata:azure_openai:submissiontext'] = 'The student assignment submission text extracted from online text, Word documents, or text-based PDF files.';
$string['privacy:metadata:azure_openai:rubric'] = 'The assignment rubric criteria and grading structure used as feedback-generation context.';
$string['azureendpoint'] = 'Azure OpenAI endpoint';
$string['azureendpoint_desc'] = 'Example: https://your-resource.openai.azure.com';
$string['azureapikey'] = 'Azure OpenAI API key';
$string['azureapikey_desc'] = 'API key for the Azure OpenAI deployment.';
$string['azuredeployment'] = 'Azure deployment name';
$string['azuredeployment_desc'] = 'Deployment name, for example gpt-5-mini.';
$string['azureapiversion'] = 'Azure API version';
$string['azureapiversion_desc'] = 'Example: 2024-12-01-preview';
$string['buttondisabledreason'] = 'Feedback button is disabled: {$a}';
$string['enabled'] = 'AI Rubric Feedback';
$string['enabled_help'] = 'If enabled, AI Rubric Feedback can be used in assignment feedback. Online text, Word document, or text-based PDF submissions and rubric grading method are required for AI feedback to be available.';
