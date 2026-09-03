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
namespace assignfeedback_airubric\service;


/**
 * Builds and sends AI feedback requests to Azure OpenAI endpoint.
 */
class azure_feedback_service {
    /** @var int Maximum estimated input tokens allowed before sending the request. */
    private const MAX_INPUT_TOKENS = 100000;

    /**
     * Generates feedback text for a rubric and submission.
     *
     * @param string $rubric Rubric text.
     * @param string $submission Submission text.
     * @return string Generated feedback text.
     */
    public function generate(string $rubric, string $submission): string {
        $result = $this->generate_with_usage($rubric, $submission);

        return $result['feedback'];
    }

    /**
     * Generates feedback text and returns token usage metadata.
     *
     * @param string $rubric Rubric text.
     * @param string $submission Submission text.
     * @return array{feedback: string, inputtokens: int} Generated feedback and input token count.
     */
    public function generate_with_usage(string $rubric, string $submission): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $config = $this->get_azure_config();
        $url = rtrim($config['endpoint'], '/') . '/openai/deployments/' . rawurlencode($config['deployment'])
            . '/chat/completions?api-version=' . urlencode($config['apiversion']);
        $systemprompt = trim(get_string('systemprompt', 'assignfeedback_airubric'));

        $payload = [
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemprompt . ' ' . $rubric,
                ],
                [
                    'role' => 'user',
                    'content' => $submission,
                ],
            ],
            'max_completion_tokens' => 7096,
        ];
        $inputtokens = self::estimate_messages_tokens($payload['messages']);
        $this->validate_input_token_count($inputtokens);

        $curl = new \curl();
        $headers = [
            'Content-Type: application/json',
            'api-key: ' . $config['apikey'],
        ];

        $response = $curl->post($url, json_encode($payload), [
            'CURLOPT_HTTPHEADER' => $headers,
            'CURLOPT_TIMEOUT' => 45,
        ]);

        if ($curl->get_errno() !== 0) {
            throw new \moodle_exception('azurecommunicationerror', 'assignfeedback_airubric', '', $curl->error);
        }

        return [
            'feedback' => $this->extract_feedback_from_response($response),
            'inputtokens' => $inputtokens,
        ];
    }

    /**
     * Returns validated Azure OpenAI configuration.
     *
     * @return array{endpoint: string, apikey: string, deployment: string, apiversion: string}
     */
    private function get_azure_config(): array {
        $config = [
            'endpoint' => trim((string)get_config('assignfeedback_airubric', 'azureendpoint')),
            'apikey' => trim((string)get_config('assignfeedback_airubric', 'azureapikey')),
            'deployment' => trim((string)get_config('assignfeedback_airubric', 'azuredeployment')),
            'apiversion' => trim((string)get_config('assignfeedback_airubric', 'azureapiversion')),
        ];

        if (in_array('', $config, true)) {
            throw new \moodle_exception('azureconfigmissing', 'assignfeedback_airubric');
        }

        return $config;
    }

    /**
     * Validates estimated input token count before sending a request.
     *
     * @param int $inputtokens Estimated input token count.
     */
    private function validate_input_token_count(int $inputtokens): void {
        if ($inputtokens <= self::MAX_INPUT_TOKENS) {
            return;
        }

        throw new \moodle_exception('inputtokenlimitexceeded', 'assignfeedback_airubric', '', [
            'tokens' => $inputtokens,
            'limit' => self::MAX_INPUT_TOKENS,
        ]);
    }

    /**
     * Extracts feedback text from an Azure OpenAI response.
     *
     * @param string $response Raw JSON response.
     * @return string Feedback text.
     */
    private function extract_feedback_from_response(string $response): string {
        $decoded = json_decode($response, true);
        if (is_array($decoded) && !empty($decoded['error']['message'])) {
            throw new \moodle_exception('azurecommunicationerror', 'assignfeedback_airubric', '', $decoded['error']['message']);
        }

        if (!is_array($decoded) || empty($decoded['choices'][0]['message']['content'])) {
            throw new \moodle_exception('azureinvalidresponse', 'assignfeedback_airubric', '', $response);
        }

        return trim((string)$decoded['choices'][0]['message']['content']);
    }

    /**
     * Estimate tokens for the chat messages before sending them to Azure OpenAI.
     *
     * The exact tokenizer depends on the deployed model. This keeps the estimate
     * deterministic and includes the common chat message framing overhead.
     *
     * @param array $messages
     * @return int
     */
    private static function estimate_messages_tokens(array $messages): int {
        $tokens = 3;

        foreach ($messages as $message) {
            $tokens += 3;
            $tokens += self::estimate_text_tokens((string)($message['role'] ?? ''));
            $tokens += self::estimate_text_tokens((string)($message['content'] ?? ''));
        }

        return $tokens;
    }

    /**
     * Estimate tokens in a text value using words, punctuation and long-word splits.
     *
     * @param string $text
     * @return int
     */
    private static function estimate_text_tokens(string $text): int {
        $text = trim($text);
        if ($text === '') {
            return 0;
        }

        if (!preg_match_all('/[\p{L}\p{N}]+|[^\s\p{L}\p{N}]/u', $text, $matches)) {
            return (int)ceil(strlen($text) / 4);
        }

        $tokens = 0;
        foreach ($matches[0] as $part) {
            if (preg_match('/^[\p{L}\p{N}]+$/u', $part)) {
                $length = \core_text::strlen($part);
                $tokens += max(1, (int)ceil($length / 4));
            } else {
                $tokens++;
            }
        }

        return $tokens;
    }
}
