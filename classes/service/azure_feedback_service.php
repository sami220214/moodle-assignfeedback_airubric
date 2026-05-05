<?php
// This file is part of Moodle - http://moodle.org/

namespace assignfeedback_aifeedback\service;

defined('MOODLE_INTERNAL') || die();

/**
 * Builds and sends AI feedback requests to Azure OpenAI endpoint.
 */
class azure_feedback_service {
    /**
     * @param string $rubric
     * @param string $submission
     * @return string
     */
    public function generate(string $rubric, string $submission): string {
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

        return trim((string)$decoded['choices'][0]['message']['content']);
    }
}
