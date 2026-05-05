<?php
// This file is part of Moodle - http://moodle.org/

namespace assignfeedback_aifeedback\privacy;

use core_privacy\local\metadata\null_provider;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for assignfeedback_aifeedback.
 */
class provider implements null_provider {
    /**
     * @return string
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
