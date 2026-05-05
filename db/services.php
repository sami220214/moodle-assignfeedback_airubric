<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$functions = [
    'assignfeedback_aifeedback_generate_feedback' => [
        'classname' => 'assignfeedback_aifeedback\\external\\generate_feedback',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Generate textual feedback from rubric and online text submission.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'assignfeedback/aifeedback:generate',
    ],
];
