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
namespace assignfeedback_airubric\local;

use assign;
use core\context\module as context_module;
use stdClass;

/**
 * Renders assignment rubric text for AI feedback generation.
 */
class rubric_renderer {
    /** @var assign */
    private $assignment;

    /** @var context_module */
    private $context;

    /** @var stdClass */
    private $course;

    /** @var \cm_info|stdClass */
    private $cm;

    /**
     * Creates a rubric renderer.
     *
     * @param assign $assignment Assignment instance.
     * @param context_module $context Module context.
     * @param stdClass $course Course record.
     * @param \cm_info|stdClass $cm Course module record.
     */
    public function __construct(assign $assignment, context_module $context, stdClass $course, $cm) {
        $this->assignment = $assignment;
        $this->context = $context;
        $this->course = $course;
        $this->cm = $cm;
    }

    /**
     * Renders the active rubric for a user submission.
     *
     * @param int $userid Student user ID.
     * @return string Rendered rubric HTML.
     */
    public function render_for_user(int $userid): string {
        $controller = get_grading_manager($this->context, 'mod_assign', 'submissions')->get_active_controller();
        if (!$controller) {
            return '';
        }

        try {
            if ($this->controller_expects_grade($controller)) {
                $grade = $this->assignment->get_user_grade($userid, true);
                return (string)$controller->render_grade($grade, false, true, false, false);
            }
        } catch (\Throwable $e) {
            return $this->render_with_page($controller, $userid);
        }

        return $this->render_with_page($controller, $userid);
    }

    /**
     * Returns whether the controller render method expects a grade record first.
     *
     * @param object $controller Grading controller.
     * @return bool
     */
    private function controller_expects_grade($controller): bool {
        $method = new \ReflectionMethod($controller, 'render_grade');
        $firstparam = $method->getParameters()[0] ?? null;

        return $firstparam && $firstparam->getName() === 'grade';
    }

    /**
     * Renders rubric through a Moodle page object.
     *
     * @param object $controller Grading controller.
     * @param int $userid Student user ID.
     * @return string Rendered rubric HTML.
     */
    private function render_with_page($controller, int $userid): string {
        global $CFG;
        require_once($CFG->libdir . '/pagelib.php');

        $page = new \moodle_page();
        $page->set_context($this->context);
        $page->set_course($this->course);
        $page->set_cm($this->cm);
        $page->set_url(new \moodle_url('/mod/assign/view.php', ['id' => $this->cm->id]));

        return (string)$controller->render_grade($page, $userid, [], '', false);
    }
}
