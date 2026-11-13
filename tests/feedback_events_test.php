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
 * Verifies that audit events retain identifiers without content payloads.
 *
 * @covers \assignfeedback_airubric\event\feedback_requested
 * @covers \assignfeedback_airubric\event\feedback_generated
 * @covers \assignfeedback_airubric\event\feedback_failed
 */
final class feedback_events_test extends \advanced_testcase {
    /**
     * Verify the Moodle log fields and correlation reference for all outcomes.
     */
    public function test_audit_events(): void {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $assignment = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $student = $this->getDataGenerator()->create_user();
        $context = \context_module::instance($assignment->cmid);
        $other = [
            'service' => 'Azure OpenAI',
            'servicehost' => 'example.openai.azure.com',
            'requestid' => 'audit-test-reference',
        ];
        $sink = $this->redirectEvents();
        foreach (['feedback_requested', 'feedback_generated', 'feedback_failed'] as $name) {
            $class = '\\assignfeedback_airubric\\event\\' . $name;
            $event = $class::create([
                'context' => $context,
                'objectid' => $assignment->id,
                'relateduserid' => $student->id,
                'other' => $other,
            ]);
            $event->trigger();
            $data = $event->get_data();
            $this->assertEquals($USER->id, $data['userid']);
            $this->assertEquals($student->id, $data['relateduserid']);
            $this->assertEquals($assignment->id, $data['objectid']);
            $this->assertEquals($course->id, $data['courseid']);
            $this->assertGreaterThan(0, $data['timecreated']);
            $this->assertSame($other, $data['other']);
            $this->assertStringContainsString($other['servicehost'], $event->get_description());
            $this->assertNotEmpty($event->get_name());
            $this->assertEquals(new \moodle_url('/mod/assign/view.php', ['id' => $assignment->cmid]), $event->get_url());
        }
        $this->assertCount(3, $sink->get_events());
        $sink->close();
    }
}
