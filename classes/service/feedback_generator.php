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
 * Generates feedback and records the request lifecycle in the Moodle event log.
 */
class feedback_generator {
    /**
     * Generate feedback with content-free audit events.
     *
     * @param \context $context Assignment module context.
     * @param int $assignmentid Assignment ID.
     * @param int $userid Student ID.
     * @param string $rubric Plain text rubric.
     * @param string $submissiontext Submission text.
     * @param string $assignment Assignment instructions and context.
     * @return array{feedback: string, inputtokens: int} Generated response.
     */
    public static function generate(
        \context $context,
        int $assignmentid,
        int $userid,
        string $rubric,
        string $submissiontext,
        string $assignment = ''
    ): array {
        $service = new azure_feedback_service();
        // A reference pairs events even when several requests run concurrently.
        $eventdata = [
            'context' => $context,
            'objectid' => $assignmentid,
            'relateduserid' => $userid,
            'other' => [
                'service' => 'Azure OpenAI',
                'servicehost' => '',
                'requestid' => bin2hex(random_bytes(16)),
            ],
        ];
        $started = false;
        try {
            $result = $service->generate_with_usage(
                $rubric,
                $submissiontext,
                static function (string $host) use (&$eventdata, &$started): void {
                    $eventdata['other']['servicehost'] = $host;
                    \assignfeedback_airubric\event\feedback_requested::create($eventdata)->trigger();
                    $started = true;
                },
                $assignment
            );
        } catch (\Throwable $exception) {
            if ($started) {
                // Exception messages can contain submitted content or service responses.
                \assignfeedback_airubric\event\feedback_failed::create($eventdata)->trigger();
            }
            throw $exception;
        }
        \assignfeedback_airubric\event\feedback_generated::create($eventdata)->trigger();

        return $result;
    }
}
