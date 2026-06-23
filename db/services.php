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
defined('MOODLE_INTERNAL') || die();

$functions = [
    'assignfeedback_aifeedback_generate_feedback' => [
        'classname' => 'assignfeedback_aifeedback\\external\\generate_feedback',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Generate textual feedback from rubric and online text, Word document, or text-based PDF submission.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'assignfeedback/aifeedback:generate',
    ],
];
