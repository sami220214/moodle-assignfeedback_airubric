# assignfeedback_airubric: technical documentation

## 1. Purpose
`assignfeedback_airubric` is a Moodle assignment feedback plugin that produces a draft of written feedback for the teacher:
- based on the assessment rubric
- based on the student's online text submission, Word document (`.docx`/`.doc`), or text-based PDF document (`.pdf`)

The plugin does not store generated feedback in its own database structure.

## 2. Version and compatibility
- Component: `assignfeedback_airubric`
- Plugin type: `assignfeedback`
- Technical name: `airubric`
- Frankenstyle component: `assignfeedback_airubric`
- Installation path: `mod/assign/feedback/airubric/`
- Version: `2026062300`
- Release: `1.0.0`
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
4. The teacher clicks the feedback button.
5. The AMD module calls the AJAX endpoint `assignfeedback_airubric_generate_feedback`.
6. The External API retrieves the rubric content, the student's online text and/or Word/PDF document text content, and calls Azure OpenAI.
7. The generated text is shown in the modal textarea and can be copied to the clipboard.

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
- `classes/external/generate_feedback.php`
  - AJAX-callable External API
  - validates parameters, context, and capabilities
  - renders the rubric through the grading controller, with fallbacks for different Moodle signatures
  - retrieves the student's submission text from online text and/or Word/PDF files
  - calls the Azure service
- `classes/service/azure_feedback_service.php`
  - builds the HTTP request to the Azure OpenAI Chat Completions API
  - handles cURL and response-format errors

### 4.2 UI (Mustache + AMD)
- `templates/modal.mustache`
  - button, modal, status area, textarea, and copy button
  - shows a disabled button and reason when feedback generation is not eligible
- `amd/src/feedback_button.js` (+ `amd/build/feedback_button.min.js`)
  - opens the modal (Bootstrap 5 API, jQuery modal fallback, final DOM fallback)
  - makes the AJAX call
  - shows generation status
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
`azure_feedback_service::generate($rubric, $submission)`:
- builds the URL:
  - `{endpoint}/openai/deployments/{deployment}/chat/completions?api-version={apiversion}`
- sends the payload:
  - `messages`: `system` + `user`
  - `max_completion_tokens`: `7096`
- sets the headers:
  - `Content-Type: application/json`
  - `api-key: <azureapikey>`
- timeout: `45` s

The sent content is formed in the external class:
- rubric: `strip_tags($rubric)`
- student text: online text and/or text read from Word/PDF documents

## 8. Error handling
Backend:
- missing configuration -> `azureconfigmissing`
- cURL error -> `azurecommunicationerror`
- Azure JSON error (`error.message`) -> `azurecommunicationerror`
- incomplete response format -> `azureinvalidresponse`
- ineligible case -> `noteligible`

Frontend:
- AJAX errors are normalized (`toException`) and shown through `Notification.exception(...)`.
- Errors are not written into the modal textarea; the status row is cleared in error cases.

## 9. Privacy
`classes/privacy/provider.php` implements Moodle's Privacy API metadata provider:
- the plugin does not store personal data in its own database tables.
- the plugin describes the external location `azure_openai` with the Privacy API `add_external_location_link` metadata.

The following data is sent to the external Azure OpenAI service to generate the feedback draft:
- the student's submission text, extracted from online text, Word documents, or text-based PDF files
- the assignment rubric criteria and grading structure

Notes:
- generated feedback is shown to the teacher, but it is not automatically stored in the plugin's own database.
- the Azure OpenAI endpoint is configured in admin settings, so privacy requirements, contracts, regional location, and organizational policies must be verified during deployment.

## 10. Known limitations
1. Support covers rubric grading and online text submissions, Word document submissions (`.docx`/`.doc`), or text-based PDF submissions (`.pdf`); scanned PDFs and other submission types are not included in the current path.
2. Generated feedback is not automatically stored as assessment feedback; the teacher copies it manually.
3. The prompt is not a separate admin setting, but a language string (`systemprompt`).
4. The plugin directory does not include an actual automated test suite (unit/integration).

## 11. Deployment checklist
1. Install the plugin in Moodle.
2. Configure the Azure settings (`endpoint`, `apikey`, `deployment`, `api version`).
3. Ensure the `assignfeedback/airubric:generate` capability is granted to the required roles.
4. Ensure the assignment activity uses rubric grading.
5. Ensure the student has an online text submission, Word document submission, or text-based PDF submission.
6. Test generation from the teacher view.
7. Check Moodle logs in error cases.

## 12. File map
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

## 13. License and third-party libraries
All PHP files in the plugin are licensed under GNU GPL v3 or later through the Moodle file header. The plugin does not bundle separate third-party libraries; the AMD module uses Moodle-provided `core/ajax`, `core/notification`, and `jquery` modules, and the UI uses the Moodle/theme Bootstrap modal API. Therefore a separate `thirdpartylibs.xml` file is not required for the current package.
