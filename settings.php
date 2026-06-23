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
defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
        'assignfeedback_airubric/azureendpoint',
        get_string('azureendpoint', 'assignfeedback_airubric'),
        get_string('azureendpoint_desc', 'assignfeedback_airubric'),
        ''
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'assignfeedback_airubric/azureapikey',
        get_string('azureapikey', 'assignfeedback_airubric'),
        get_string('azureapikey_desc', 'assignfeedback_airubric'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'assignfeedback_airubric/azuredeployment',
        get_string('azuredeployment', 'assignfeedback_airubric'),
        get_string('azuredeployment_desc', 'assignfeedback_airubric'),
        'gpt-5-mini'
    ));

    $settings->add(new admin_setting_configtext(
        'assignfeedback_airubric/azureapiversion',
        get_string('azureapiversion', 'assignfeedback_airubric'),
        get_string('azureapiversion_desc', 'assignfeedback_airubric'),
        '2024-12-01-preview'
    ));
}
