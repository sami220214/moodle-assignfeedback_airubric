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
     * Exports every criterion and level from the active rubric definition.
     *
     * @return string Structured rubric JSON.
     */
    public function render_definition(): string {
        global $DB;
        $controller = get_grading_manager($this->context, 'mod_assign', 'submissions')->get_active_controller();
        if (!$controller instanceof \gradingform_rubric_controller) {
            throw new \moodle_exception('noteligible_norubric', 'assignfeedback_airubric');
        }
        $definition = $controller->get_definition();
        if (!$definition || empty($definition->id)) {
            throw new \moodle_exception('noteligible_norubric', 'assignfeedback_airubric');
        }
        $criteria = $DB->get_records('gradingform_rubric_criteria', ['definitionid' => $definition->id], 'sortorder, id');
        $result = [];
        foreach ($criteria as $criterion) {
            $levels = $DB->get_records('gradingform_rubric_levels', ['criterionid' => $criterion->id], 'score, id');
            if (!$levels) {
                throw new \moodle_exception('noteligible_norubric', 'assignfeedback_airubric');
            }
            $item = [
                'criterion' => content_to_text($criterion->description, $criterion->descriptionformat),
                'levels' => [],
            ];
            foreach ($levels as $level) {
                $item['levels'][] = [
                    'score' => (float)$level->score,
                    'description' => content_to_text($level->definition, $level->definitionformat),
                ];
            }
            $result[] = $item;
        }
        if (!$result) {
            throw new \moodle_exception('noteligible_norubric', 'assignfeedback_airubric');
        }
        return json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
