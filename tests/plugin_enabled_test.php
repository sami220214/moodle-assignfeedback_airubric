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

namespace assignfeedback_airubric;

/**
 * Checks assignment settings control the feedback form.
 *
 * @package   assignfeedback_airubric
 * @copyright 2026 Sami Simpanen
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \assign_feedback_airubric
 */
final class plugin_enabled_test extends \advanced_testcase {
    /**
     * Disabling feedback removes its form control even for a grader with permission.
     */
    public function test_disabled_plugin_does_not_add_feedback_control(): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        require_once($CFG->libdir . '/formslib.php');

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('assign', $module->id);
        $assignment = new \assign(\context_module::instance($cm->id), $cm, $course);
        $plugin = $assignment->get_feedback_plugin_by_type('airubric');

        $plugin->set_config('enabled', 1);
        $this->assertTrue((bool)$plugin->is_enabled());
        $plugin->set_config('enabled', 0);
        // Reopening the grading view creates a new instance with a fresh enabled cache.
        $assignment = new \assign(\context_module::instance($cm->id), $cm, $course);
        $plugin = $assignment->get_feedback_plugin_by_type('airubric');
        $this->assertFalse((bool)$plugin->is_enabled());

        $form = new \MoodleQuickForm('gradingform', 'post', '');
        $plugin->get_form_elements(null, $form, new \stdClass());
        $this->assertFalse($form->elementExists('assignfeedback_airubric_control'));

        $plugin->set_config('enabled', 1);
        $assignment = new \assign(\context_module::instance($cm->id), $cm, $course);
        $plugin = $assignment->get_feedback_plugin_by_type('airubric');
        $this->assertTrue((bool)$plugin->is_enabled());
    }
}
