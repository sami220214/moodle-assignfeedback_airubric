# assignfeedback_airubric: technical documentation

## 1. Purpose
`assignfeedback_airubric` is a Moodle assignment feedback plugin that produces a draft of written feedback for the teacher:
- based on the assessment rubric
- based on the assignment name, description and activity instructions
- based on the student's online text submission, Word document (`.docx`/`.doc`), or text-based PDF document (`.pdf`)

The plugin shows the suggested feedback and the estimated number of input tokens sent to AI. Generated feedback is not automatically stored in the plugin's own database.

## 2. Version and compatibility
- Component: `assignfeedback_airubric`
- Plugin type: `assignfeedback`
- Technical name: `airubric`
- Frankenstyle component: `assignfeedback_airubric`
- Installation path: `mod/assign/feedback/airubric/`
- Version: `2026112001`
- Release: `2.0.1`
- Requires at least Moodle: `4.5` (`2024100700`)
- Maturity: `MATURITY_STABLE`

Source: `version.php`.

## 3. High-level flow
1. The teacher opens the student's grading view in the assignment.
2. `locallib.php` renders the button and modal if the teacher has the `assignfeedback/airubric:generate` capability.
3. Eligibility is checked:
   - rubric grading is enabled for the assignment
   - the student has a submission
   - the submission contains online text, a readable Word document, or a text-based PDF
   - every submission attachment is supported and yields text; unreadable or unsupported attachments block generation
4. The teacher clicks the feedback button.
5. The AMD module calls the AJAX endpoint `assignfeedback_airubric_generate_feedback`.
6. The External API retrieves the assignment name, description and activity instructions, the structured rubric definition, and the submission text.
7. The service estimates the number of input tokens and blocks the request if the estimate exceeds `100000` tokens.
8. Azure OpenAI produces the feedback draft. The prompt requires a task relevance check followed by evidence-based feedback on every criterion, without scores or grades.
9. The generated text and token estimate are shown in the modal, and the feedback can be copied to the clipboard.

## 4. Architecture

### 4.1 Server (PHP)
- `locallib.php`
  - defines the `assign_feedback_airubric` plugin
  - checks the capability and eligibility
  - renders the UI (`templates/modal.mustache`)
  - loads the AMD module `assignfeedback_airubric/feedback_button`
- `classes/local/eligibility.php`
  - encapsulates the eligibility rules
  - checks the rubric, submission existence, and readable submission text
- `classes/local/submission_text.php`
  - combines online text and supported document file text into one submission text
  - reads `.docx` files from OOXML content and `.doc` files with PHP-based best-effort text extraction
  - reads text-based `.pdf` files from the PDF text layer with best-effort text extraction; scanned PDFs require OCR and are not currently supported
- `classes/local/rubric_renderer.php`
  - `render_definition()` exports all criteria, level descriptions and scores from the shared rubric definition as structured JSON text, without previous student grades
- `classes/external/generate_feedback.php`
  - AJAX-callable External API
  - validates parameters, context, and capabilities
  - assembles assignment instructions and obtains the rubric definition through `rubric_renderer`
  - retrieves the student's submission text from online text and/or Word/PDF files
  - calls the Azure service
  - returns the generated text and estimated input token count
- `classes/service/feedback_generator.php`
  - calls the Azure service and records request lifecycle events without content payloads in the Moodle log
- `classes/service/azure_feedback_service.php`
  - builds the HTTP request to the Azure OpenAI Chat Completions API
  - estimates the input token count before the request
  - blocks oversized requests with a `100000` token limit
  - handles cURL and response-format errors; accepts only responses with `finish_reason` equal to `stop`

### 4.2 UI (Mustache + AMD)
- `templates/modal.mustache`
  - button, modal, status area, textarea, and copy button
  - shows a disabled button and reason when feedback generation is not eligible
- `amd/src/feedback_button.js` (+ `amd/build/feedback_button.min.js`)
  - opens the modal through the Bootstrap 5 API, jQuery modal fallback, or final DOM fallback
  - makes the AJAX call
  - shows generation status
  - shows the generated feedback and estimated input token count
  - shows errors through `core/notification` exceptions
  - copies text to the clipboard (`navigator.clipboard`, fallback `execCommand`)

## 5. Permissions and registration
- AJAX function: `db/services.php`
  - `assignfeedback_airubric_generate_feedback`
  - type `write`, `ajax => true`
- Capability: `db/access.php`
  - `assignfeedback/airubric:generate`
  - allowed by default for the `editingteacher` and `manager` roles
- The External API requires:
  - a logged-in user (`require_login`)
  - a valid sesskey (`require_sesskey`)
  - `mod/assign:grade`
  - `assignfeedback/airubric:generate`

## 6. Settings
Plugin admin settings (`settings.php`):
- `assignfeedback_airubric/azureendpoint`
- `assignfeedback_airubric/azureapikey`
- `assignfeedback_airubric/azuredeployment` (default `gpt-5-mini`)
- `assignfeedback_airubric/azureapiversion` (default `2024-12-01-preview`)

## 7. Azure integration
`azure_feedback_service::generate_with_usage($rubric, $submission, $onrequest, $assignment)`:
- builds the URL:
  - `{endpoint}/openai/deployments/{deployment}/chat/completions?api-version={apiversion}`
- sends the payload:
  - `messages`: `system` + `user`
  - `max_completion_tokens`: `7096`
- estimates the token count of the `messages` content with a deterministic word-based estimate
- stops the request before calling Azure if the estimated input exceeds `100000` tokens
- sets the headers:
  - `Content-Type: application/json`
  - `api-key: <azureapikey>`
- timeout: `45` s
- returns:
  - `feedback`: generated feedback
  - `inputtokens`: estimated input token count

The external class assembles the data and the service builds the messages:
- `system`: the `systemprompt` instructions from the active Moodle language
- `user`: a JSON object with `assignment`, `rubric` and `submission` fields
- `assignment`: JSON text containing the assignment name, description, activity instructions and a notice about unavailable assignment materials
- `rubric`: JSON text with all criteria, level descriptions and scores
- `submission`: online text and/or text extracted from Word/PDF documents

## 8. Error handling
Backend:
- missing configuration -> `azureconfigmissing`
- oversized estimated input -> `inputtokenlimitexceeded`
- cURL error -> `azurecommunicationerror`
- Azure JSON error (`error.message`) -> `azurecommunicationerror`
- incomplete response format or a finish reason other than `stop` -> `azureinvalidresponse`
- unreadable or unsupported submission attachment -> `unreadableattachment` (reported through `noteligible` during eligibility validation)
- ineligible case -> `noteligible`

Frontend:
- AJAX errors are normalized (`toException`) and shown through `Notification.exception(...)`.
- Errors are not written into the modal textarea; the status row is cleared in error cases.

## 9. Privacy
`classes/privacy/provider.php` implements Moodle's Privacy API metadata provider:
- the plugin does not store personal data in its own database tables
- the plugin describes the external location `azure_openai` with the Privacy API `add_external_location_link` metadata

The following data is sent to the external Azure OpenAI service to generate the feedback draft:
- the student's submission text, extracted from online text, Word documents, or text-based PDF files
- the assignment rubric criteria and grading structure
- the assignment name, description and activity instructions

Notes:
- generated feedback is shown to the teacher, but it is not automatically stored in the plugin's own database
- the Azure OpenAI endpoint is configured in admin settings, so privacy requirements, contracts, regional location, and organizational policies must be verified during deployment

## 10. Known limitations
1. Support covers rubric grading and online text submissions, Word document submissions (`.docx`/`.doc`), or text-based PDF submissions (`.pdf`); scanned PDFs and other submission types are not included in the current path.
2. Generated feedback is not automatically stored as assessment feedback; the teacher copies it manually.
3. The prompt is not a separate admin setting, but a language string (`systemprompt`).
4. Token count is an estimate because exact tokenization depends on the deployed Azure OpenAI model.
5. PHPUnit tests are in `tests/`; GitHub Actions runs Moodle Plugin CI. Model feedback quality requires separate evaluation.
6. Assignment attachments, images and linked content are not read. The prompt requires disclosure of resulting assessment limitations. Partial text extraction from submission documents may also go undetected.
7. Missing assignment instructions do not technically block requests; the prompt requires disclosure that relevance cannot be determined. A local `systemprompt` language customisation can override the default instructions.

## 11. Deployment checklist
1. Install the plugin in Moodle at `mod/assign/feedback/airubric/`.
2. Run the Moodle upgrade so version `2026112001` is registered.
3. Configure the Azure settings (`endpoint`, `apikey`, `deployment`, `api version`).
4. Ensure the `assignfeedback/airubric:generate` capability is granted to the required roles.
5. Ensure the assignment activity uses rubric grading.
6. Ensure the student has an online text submission, Word document submission, or text-based PDF submission.
7. Test generation from the teacher grading view.
8. Check Moodle logs in error cases.

Also ensure the actual task topic and instructions appear as text in the assignment description or activity instructions. Evaluate off-topic and partial answers and embedded instruction attacks against the configured Azure model to check prompt adherence.

## 12. File map
- `version.php`
- `README.md`
- `README_EN.md`
- `settings.php`
- `locallib.php`
- `db/access.php`
- `db/services.php`
- `classes/local/eligibility.php`
- `classes/local/submission_text.php`
- `classes/local/rubric_renderer.php`
- `classes/external/generate_feedback.php`
- `classes/service/azure_feedback_service.php`
- `classes/service/feedback_generator.php`
- `classes/event/feedback_requested.php`
- `classes/event/feedback_generated.php`
- `classes/event/feedback_failed.php`
- `classes/privacy/provider.php`
- `templates/modal.mustache`
- `amd/src/feedback_button.js`
- `amd/build/feedback_button.min.js`
- `lang/fi/assignfeedback_airubric.php`
- `lang/en/assignfeedback_airubric.php`
- `.github/workflows/ci.yml`
- `tests/feedback_input_test.php`
- `tests/feedback_events_test.php`
- `tests/pdf_text_decoder_test.php`

## 13. CI
`.github/workflows/ci.yml` runs Moodle Plugin CI checks in GitHub Actions on push and pull request events.

Matrix:
- PHP: `8.1`, `8.2`, `8.3`
- Moodle: `MOODLE_405_STABLE`
- Databases: `pgsql`, `mariadb`

Checks include PHP lint, PHP Mess Detector, Moodle Code Checker, PHPDoc Checker, validate, savepoints, Mustache lint, Grunt, PHPUnit, and Behat.

## 14. License and third-party libraries
All PHP files in the plugin are licensed under GNU GPL v3 or later through the Moodle file header. The plugin does not bundle separate third-party libraries; the AMD module uses Moodle-provided `core/ajax`, `core/notification`, and `jquery` modules, and the UI uses the Moodle/theme Bootstrap modal API. Therefore a separate `thirdpartylibs.xml` file is not required for the current package.

### Moodle audit log

AI feedback requests emit Moodle events for request start, successful feedback reception, and failure after the request starts. Events record the acting user and time, assignment and student IDs, Azure OpenAI destination hostname, and a random reference linking the events. A start event records a send attempt, not proof of receipt by Azure. Failures may occur after Azure has received data. Validation failures before sending do not emit request events.

Submission text, prompts, generated feedback, API keys, full endpoint URLs, and exception messages are excluded. Moodle log stores control storage, access, and retention according to the site's configuration; administrators should set these according to the organisation's policy.

## Changes in version 2.0
- Sends assignment name, description and activity instructions separately from rubric and submission.
- Exports all rubric criteria, levels and scores directly from the definition as structured JSON, without previous student grades.
- Finnish and English prompts check task relevance first and require evidence for each criterion. No grades or scores are generated.
- Unreadable or unsupported submission attachments block generation with a named error; incomplete AI completions are rejected.
- Assignment attachments, images and linked content are not automatically read. The assistant must state resulting assessment limitations. Extraction completeness cannot be guaranteed; legacy DOC and PDF extraction remains best-effort.
- Run the normal Moodle upgrade to version `2026112001`; no database schema change is required. Local language customisations of systemprompt can override the new default.
- PHPUnit coverage includes unreadable attachments and incomplete/completed responses. Validate pedagogical quality against the configured Azure model, including off-topic submissions and embedded instruction attacks.

## Fix in 2.0.1
Recognise rubric controllers by their class in eligibility and rubric export, fixing `Call to undefined method gradingform_rubric_controller::get_method()`. Includes a rubric export regression test.

Also corrected PHP LF line endings, test PHPDoc comments, multiline condition formatting and language string ordering. Rubric export is now named `render_definition()` and no longer takes an unused student ID.
