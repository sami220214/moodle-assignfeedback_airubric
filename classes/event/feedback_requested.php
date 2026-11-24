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

namespace assignfeedback_airubric\event;

/**
 * Records AI feedback processing without storing submission or feedback text.
 */
class feedback_requested extends \core\event\base {
    /**
     * Initialise event metadata.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'assign';
    }

    /**
     * Return the translated event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventfeedback_requested', 'assignfeedback_airubric');
    }

    /**
     * Describe the processing operation using internal identifiers only.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' started AI feedback processing for user with id " .
            "'$this->relateduserid' in assignment with id '$this->objectid' using " .
            "'{$this->other['service']}' (host '{$this->other['servicehost']}'). " .
            "Request reference: {$this->other['requestid']}.";
    }

    /**
     * Link to the assignment.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/assign/view.php', ['id' => $this->contextinstanceid]);
    }

    /**
     * Validate the required audit fields.
     */
    protected function validate_data() {
        parent::validate_data();
        if (empty($this->objectid) || empty($this->relateduserid)) {
            throw new \coding_exception('Assignment and student IDs are required.');
        }
        foreach (['service', 'servicehost', 'requestid'] as $field) {
            if (!isset($this->other[$field])) {
                throw new \coding_exception('Missing AI feedback audit field: ' . $field);
            }
        }
    }

    /**
     * Map the assignment when restoring event data.
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => 'assign', 'restore' => 'assign'];
    }

    /**
     * Service metadata has no database references.
     *
     * @return false
     */
    public static function get_other_mapping() {
        return false;
    }
}
