<?php /** @noinspection PhpUnhandledExceptionInspection */
// Copyright (C) 2017 Learning Stacks LLC https://learningstacks.com/
//
// This file is a part of the IMIS Integration Components developed by
// Learning Stacks LLC - https://learningstacks.com/
//
// This file cannot be copied or distributed without the express permission
// of Learning Stacks LLC.

/**
 * @package   auth_imisbridge
 * @copyright 2017 Learning Stacks LLC {@link https://learningstacks.com/}
 * @license   All Rights Reserved
 */

defined('MOODLE_INTERNAL') || die;
global $ADMIN;

if ($ADMIN->fulltree) {

    // Introductory explanation.
    $settings->add(new admin_setting_heading('auth_imisbridge/pluginname', '',
        new lang_string('auth_imisbridgedescription', 'auth_imisbridge')));


    $yesno = array(
        '0' => new lang_string('no'),
        '1' => new lang_string('yes'),
    );

    $settings->add(new admin_setting_heading('auth_imisbridge/ssoinformation', '',
        new lang_string('ssoinformation', 'auth_imisbridge')));

    $settings->add(new admin_setting_configtext('auth_imisbridge/sso_login_url',
        get_string('sso_login_url_label', 'auth_imisbridge'),
        get_string('sso_login_url_desc', 'auth_imisbridge'), '', PARAM_URL));

    $settings->add(new admin_setting_configtext('auth_imisbridge/sso_logout_url',
        get_string('sso_logout_url_label', 'auth_imisbridge'),
        get_string('sso_logout_url_desc', 'auth_imisbridge'), '', PARAM_URL));

    $settings->add(new admin_setting_configselect('auth_imisbridge/synch_profile',
        new lang_string('synch_profile_label', 'auth_imisbridge'),
        new lang_string('synch_profile_desc', 'auth_imisbridge'), 1, $yesno));

    // Display locking / mapping of profile fields.
    $authplugin = get_auth_plugin('imisbridge');
    $help = '';
    $help .= get_string('auth_updatelocal_expl', 'auth');
    $help .= get_string('auth_fieldlock_expl', 'auth');

    display_auth_lock_options($settings, $authplugin->authtype, $authplugin->userfields, $help, true, false,
        $authplugin->get_custom_user_profile_fields());
}
