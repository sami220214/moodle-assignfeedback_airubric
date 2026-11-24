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

namespace assignfeedback_airubric;

/**
 * Checks incomplete submissions and truncated AI responses cannot become feedback.
 *
 * @covers \assignfeedback_airubric\local\submission_text
 * @covers \assignfeedback_airubric\local\rubric_renderer
 * @covers \assignfeedback_airubric\service\azure_feedback_service
 */
final class feedback_input_test extends \advanced_testcase {
    /**
     * Export works with Moodle's rubric controller without a get_method method.
     */
    public function test_rubric_export_uses_controller_type(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        require_once($CFG->dirroot . '/grade/grading/lib.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('assign', $module->id);
        $context = \context_module::instance($cm->id);
        $manager = get_grading_manager($context, 'mod_assign', 'submissions');
        $manager->set_active_method('rubric');
        $areaid = $DB->get_field('grading_areas', 'id', [
            'contextid' => $context->id,
            'component' => 'mod_assign',
            'areaname' => 'submissions',
        ], MUST_EXIST);
        $definitionid = $DB->insert_record('grading_definitions', (object)[
            'areaid' => $areaid,
            'method' => 'rubric',
            'name' => 'Essay rubric',
            'description' => '',
            'descriptionformat' => FORMAT_HTML,
            'status' => \gradingform_controller::DEFINITION_STATUS_READY,
            'timecreated' => time(),
            'usercreated' => get_admin()->id,
            'timemodified' => time(),
            'usermodified' => get_admin()->id,
            'options' => '',
        ]);
        $criterionid = $DB->insert_record('gradingform_rubric_criteria', (object)[
            'definitionid' => $definitionid,
            'sortorder' => 0,
            'description' => '<p>Topic relevance</p>',
            'descriptionformat' => FORMAT_HTML,
        ]);
        foreach ([0 => 'Off topic', 5 => 'Addresses the topic thoroughly'] as $score => $description) {
            $DB->insert_record('gradingform_rubric_levels', (object)[
                'criterionid' => $criterionid,
                'score' => $score,
                'definition' => $description,
                'definitionformat' => FORMAT_HTML,
            ]);
        }
        $assignment = new \assign($context, $cm, $course);
        $renderer = new \assignfeedback_airubric\local\rubric_renderer($assignment, $context, $course, $cm);
        $rubric = json_decode($renderer->render_definition(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(1, $rubric);
        $this->assertSame('Topic relevance', trim($rubric[0]['criterion']));
        $this->assertSame([0, 5], array_column($rubric[0]['levels'], 'score'));
        $this->assertSame('Off topic', trim($rubric[0]['levels'][0]['description']));
    }

    /**
     * A readable online answer must not hide an unreadable attachment.
     */
    public function test_unreadable_attachment_blocks_partial_submission(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $student = $this->getDataGenerator()->create_user();
        $cm = get_coursemodule_from_instance('assign', $module->id);
        $context = \context_module::instance($cm->id);
        $assignment = new \assign($context, $cm, $course);
        $submission = $assignment->get_user_submission($student->id, true);
        $DB->insert_record('assignsubmission_onlinetext', (object)[
            'assignment' => $module->id,
            'submission' => $submission->id,
            'onlinetext' => '<p>A readable answer.</p>',
            'onlineformat' => FORMAT_HTML,
        ]);
        get_file_storage()->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'assignsubmission_file',
            'filearea' => 'submission_files',
            'itemid' => $submission->id,
            'filepath' => '/',
            'filename' => 'unreadable.docx',
        ], 'This is not a ZIP document.');
        $reader = new \assignfeedback_airubric\local\submission_text($assignment);
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('unreadable.docx');
        $reader->get_text($submission);
    }

    /**
     * A partial completion must not be displayed as a complete assessment.
     */
    public function test_truncated_response_is_rejected(): void {
        $service = new \assignfeedback_airubric\service\azure_feedback_service();
        $method = new \ReflectionMethod($service, 'extract_feedback_from_response');
        $method->setAccessible(true);
        $this->expectException(\moodle_exception::class);
        $method->invoke($service, json_encode(['choices' => [[
            'message' => ['content' => 'Only the first criterion...'],
            'finish_reason' => 'length',
        ]]]));
    }

    /**
     * A completed response remains available as plain feedback.
     */
    public function test_completed_response_is_accepted(): void {
        $service = new \assignfeedback_airubric\service\azure_feedback_service();
        $method = new \ReflectionMethod($service, 'extract_feedback_from_response');
        $method->setAccessible(true);
        $response = json_encode(['choices' => [[
            'message' => ['content' => ' Complete feedback. '],
            'finish_reason' => 'stop',
        ]]]);
        $this->assertSame('Complete feedback.', $method->invoke($service, $response));
    }
}
