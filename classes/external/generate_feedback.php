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
namespace assignfeedback_aifeedback\external;

use assign;
use assignfeedback_aifeedback\local\eligibility;
use assignfeedback_aifeedback\local\submission_text;
use assignfeedback_aifeedback\service\azure_feedback_service;
use core\context\module as context_module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

defined('MOODLE_INTERNAL') || die();

/**
 * External endpoint for generating rubric-based verbal feedback.
 */
class generate_feedback extends external_api {
    /**
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'userid' => new external_value(PARAM_INT, 'Student user ID'),
        ]);
    }

    /**
     * @param int $cmid
     * @param int $userid
     * @return array
     */
    public static function execute(int $cmid, int $userid): array {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'userid' => $userid,
        ]);

        $cm = get_coursemodule_from_id('assign', $params['cmid'], 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $context = context_module::instance($cm->id);

        require_login($course, false, $cm);
        require_sesskey();
        self::validate_context($context);
        require_capability('mod/assign:grade', $context);
        require_capability('assignfeedback/aifeedback:generate', $context);

        $assign = new assign($context, $cm, $course);
        $checker = new eligibility($assign);
        $state = $checker->for_user($params['userid']);

        if (!$state->eligible) {
            throw new \moodle_exception('noteligible', 'assignfeedback_aifeedback', '', $state->reason);
        }

        $controller = get_grading_manager($context, 'mod_assign', 'submissions')->get_active_controller();
        $rubric = '';

        if ($controller) {
            $renderwithpage = function() use ($CFG, $context, $course, $cm, $controller, $params): string {
                require_once($CFG->libdir . '/pagelib.php');

                $page = new \moodle_page();
                $page->set_context($context);
                $page->set_course($course);
                $page->set_cm($cm);
                $page->set_url(new \moodle_url('/mod/assign/view.php', ['id' => $cm->id]));

                return (string)$controller->render_grade(
                    $page,
                    (int)$params['userid'],
                    [],
                    '',
                    false
                );
            };

            try {
                $method = new \ReflectionMethod($controller, 'render_grade');
                $firstparam = $method->getParameters()[0] ?? null;

                if ($firstparam && $firstparam->getName() === 'grade') {
                    $grade = $assign->get_user_grade((int)$params['userid'], true);
                    $rubric = (string)$controller->render_grade($grade, false, true, false, false);
                } else {
                    $rubric = $renderwithpage();
                }
            } catch (\Throwable $e) {
                // Fallback for Moodle variants with different rubric controller signatures.
                $rubric = $renderwithpage();
            }
        }
        $submission = $assign->get_user_submission($params['userid'], false);
        $reader = new submission_text($assign);
        $submissiontext = $submission ? $reader->get_text($submission) : '';

        $service = new azure_feedback_service();
        $result = $service->generate_with_usage(strip_tags($rubric), $submissiontext);

        return [
            'feedback' => $result['feedback'],
            'inputtokens' => $result['inputtokens'],
        ];
    }

    /**
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'feedback' => new external_value(PARAM_RAW, 'AI generated feedback text'),
            'inputtokens' => new external_value(PARAM_INT, 'Estimated input token count sent to AI'),
        ]);
    }
}
