<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
        'assignfeedback_aifeedback/azureendpoint',
        get_string('azureendpoint', 'assignfeedback_aifeedback'),
        get_string('azureendpoint_desc', 'assignfeedback_aifeedback'),
        ''
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'assignfeedback_aifeedback/azureapikey',
        get_string('azureapikey', 'assignfeedback_aifeedback'),
        get_string('azureapikey_desc', 'assignfeedback_aifeedback'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'assignfeedback_aifeedback/azuredeployment',
        get_string('azuredeployment', 'assignfeedback_aifeedback'),
        get_string('azuredeployment_desc', 'assignfeedback_aifeedback'),
        'gpt-5-mini'
    ));

    $settings->add(new admin_setting_configtext(
        'assignfeedback_aifeedback/azureapiversion',
        get_string('azureapiversion', 'assignfeedback_aifeedback'),
        get_string('azureapiversion_desc', 'assignfeedback_aifeedback'),
        '2024-12-01-preview'
    ));
}
