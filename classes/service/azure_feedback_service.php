<?php
// This file is part of Moodle - http://moodle.org/

namespace assignfeedback_aifeedback\service;

defined('MOODLE_INTERNAL') || die();

/**
 * Builds and sends AI feedback requests to Azure OpenAI endpoint.
 */
class azure_feedback_service {
    /** @var int Maximum estimated input tokens allowed before sending the request. */
    private const MAX_INPUT_TOKENS = 100000;

    /**
     * @param string $rubric
     * @param string $submission
     * @return string
     */
    public function generate(string $rubric, string $submission): string {
        $result = $this->generate_with_usage($rubric, $submission);

        return $result['feedback'];
    }

    /**
     * @param string $rubric
     * @param string $submission
     * @return array{feedback: string, inputtokens: int}
     */
    public function generate_with_usage(string $rubric, string $submission): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $endpoint = trim((string)get_config('assignfeedback_aifeedback', 'azureendpoint'));
        $apikey = trim((string)get_config('assignfeedback_aifeedback', 'azureapikey'));
        $deployment = trim((string)get_config('assignfeedback_aifeedback', 'azuredeployment'));
        $apiversion = trim((string)get_config('assignfeedback_aifeedback', 'azureapiversion'));

        if ($endpoint === '' || $apikey === '' || $deployment === '' || $apiversion === '') {
            throw new \moodle_exception('azureconfigmissing', 'assignfeedback_aifeedback');
        }

        $url = rtrim($endpoint, '/') . '/openai/deployments/' . rawurlencode($deployment) . '/chat/completions?api-version=' . urlencode($apiversion);
        $systemprompt = trim(get_string('systemprompt', 'assignfeedback_aifeedback'));

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
        if ($inputtokens > self::MAX_INPUT_TOKENS) {
            throw new \moodle_exception('inputtokenlimitexceeded', 'assignfeedback_aifeedback', '', [
                'tokens' => $inputtokens,
                'limit' => self::MAX_INPUT_TOKENS,
            ]);
        }

        $curl = new \curl();
        $headers = [
            'Content-Type: application/json',
            'api-key: ' . $apikey,
        ];

        $response = $curl->post($url, json_encode($payload), [
            'CURLOPT_HTTPHEADER' => $headers,
            'CURLOPT_TIMEOUT' => 45,
        ]);

        if ($curl->get_errno() !== 0) {
            throw new \moodle_exception('azurecommunicationerror', 'assignfeedback_aifeedback', '', $curl->error);
        }

        $decoded = json_decode($response, true);
        if (is_array($decoded) && !empty($decoded['error']['message'])) {
            throw new \moodle_exception('azurecommunicationerror', 'assignfeedback_aifeedback', '', $decoded['error']['message']);
        }

        if (!is_array($decoded) || empty($decoded['choices'][0]['message']['content'])) {
            throw new \moodle_exception('azureinvalidresponse', 'assignfeedback_aifeedback', '', $response);
        }

        return [
            'feedback' => trim((string)$decoded['choices'][0]['message']['content']),
            'inputtokens' => $inputtokens,
        ];
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
