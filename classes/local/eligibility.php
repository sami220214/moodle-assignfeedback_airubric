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
namespace assignfeedback_aifeedback\local;

use assign;
use assignfeedback_aifeedback\local\submission_text;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Calculates whether AI feedback can be generated.
 */
class eligibility {
    /** @var assign */
    private $assignment;

    /**
     * @param assign $assignment
     */
    public function __construct(assign $assignment) {
        $this->assignment = $assignment;
    }

    /**
     * Eligibility for selected student.
     *
     * @param int $userid
     * @return stdClass
     */
    public function for_user(int $userid): stdClass {
        $state = (object)[
            'eligible' => false,
            'reason' => '',
        ];

        if (!$this->has_rubric()) {
            $state->reason = get_string('noteligible_norubric', 'assignfeedback_aifeedback');
            return $state;
        }

        $submission = $this->assignment->get_user_submission($userid, false);
        if (!$submission) {
            $state->reason = get_string('noteligible_nosubmission', 'assignfeedback_aifeedback');
            return $state;
        }

        $reader = new submission_text($this->assignment);
        $text = $reader->get_text($submission);
        if (trim($text) === '') {
            $state->reason = get_string('noteligible_nosubmissiontext', 'assignfeedback_aifeedback');
            return $state;
        }

        $state->eligible = true;
        return $state;
    }

    /**
     * @param int $submissionid
     * @return string
     */
    public function get_online_text_submission(int $submissionid): string {
        $reader = new submission_text($this->assignment);
        return $reader->get_online_text_submission($submissionid);
    }

    /**
     * @return bool
     */
    private function has_rubric(): bool {
        $gradingmanager = get_grading_manager($this->assignment->get_context(), 'mod_assign', 'submissions');
        $controller = $gradingmanager->get_active_controller();
        if (!$controller) {
            return false;
        }

        if (method_exists($controller, 'get_method')) {
            return $controller->get_method() === 'rubric';
        }

        if (method_exists($controller, 'get_name')) {
            return $controller->get_name() === 'rubric';
        }

        return stripos(get_class($controller), 'rubric') !== false;
    }
}
