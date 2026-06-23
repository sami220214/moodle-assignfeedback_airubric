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
use assignfeedback_aifeedback\local\eligibility;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/feedbackplugin.php');

/**
 * AI feedback plugin for assignment grading.
 */
class assign_feedback_aifeedback extends assign_feedback_plugin {
    /**
     * Return the feedback plugin display name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'assignfeedback_aifeedback');
    }

    /**
     * Plugin has no custom persisted grading fields.
     *
     * @return bool
     */
    public function is_enabled() {
        return true;
    }

    /**
     * Add feedback button into grading form.
     *
     * @param stdClass|null $submissionorgrade
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return bool
     */
    public function get_form_elements($submissionorgrade, MoodleQuickForm $mform, stdClass $data) {
        global $PAGE, $OUTPUT;

        if (!has_capability('assignfeedback/aifeedback:generate', $this->assignment->get_context())) {
            return true;
        }

        $userid = 0;
        if (!empty($submissionorgrade->userid)) {
            $userid = (int)$submissionorgrade->userid;
        } else if (!empty($data->userid)) {
            $userid = (int)$data->userid;
        } else {
            $userid = optional_param('userid', 0, PARAM_INT);
        }

        $checker = new eligibility($this->assignment);
        $state = $checker->for_user((int)$userid);

        $context = [
            'userid' => (int)$userid,
            'cmid' => (int)$this->assignment->get_course_module()->id,
            'eligible' => $state->eligible,
            'reason' => $state->reason,
            'buttonlabel' => get_string('generatefeedback', 'assignfeedback_aifeedback'),
            'modaltitle' => get_string('modaltitle', 'assignfeedback_aifeedback'),
            'copylabel' => get_string('copytoclipboard', 'assignfeedback_aifeedback'),
            'generatinglabel' => get_string('generating', 'assignfeedback_aifeedback'),
            'resultlabel' => get_string('suggestedfeedback', 'assignfeedback_aifeedback'),
            'tokencountlabel' => get_string('inputtokens', 'assignfeedback_aifeedback'),
        ];

        $html = $OUTPUT->render_from_template('assignfeedback_aifeedback/modal', $context);
        $mform->addElement('static', 'assignfeedback_aifeedback_control', get_string('pluginname', 'assignfeedback_aifeedback'), $html);

        $PAGE->requires->js_call_amd('assignfeedback_aifeedback/feedback_button', 'init');
        return true;
    }

    /**
     * Persisting grading form fields is not needed.
     *
     * @param stdClass $grade
     * @param stdClass $data
     * @return bool
     */
    public function save(stdClass $grade, stdClass $data) {
        return true;
    }
}
