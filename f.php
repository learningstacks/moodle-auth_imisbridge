<?php
// Copyright (C) 2017 Learning Stacks LLC https://learningstacks.com/
//
// This file is a part of the IMIS Integration Components developed by
// Learning Stacks LLC - https://learningstacks.com/
//
// This file cannot be copied or distributed without the express permission
// of Learning Stacks LLC.

/**
 *
 *
 * @package   auth_imisbridge
 * @copyright 2017 onwards Learning Stacks LLC {@link https://learningstacks.com/}
 * @license   All Rights Reserved
 */

require('../../config.php');
global $CFG;
require_once('../../lib/formslib.php');

class sso_login_form extends moodleform
{
    public function definition()
    {
        $mform = $this->_form; // Don't forget the underscore!

//        $mform->addElement('text', 'email', get_string('email')); // Add elements to your form
//        $mform->setType('email', PARAM_NOTAGS);                   //Set type of element
//        $mform->setDefault('email', 'Please enter email');        //Default value
        $this->add_action_buttons();
    }

    //Custom validation should be added here
    function validation($data, $files)
    {
        return array();
    }
}
$context = context_system::instance();
$PAGE->set_url("$CFG->wwwroot/auth/imisbidge/f.php");
$PAGE->set_context($context);
$PAGE->set_pagelayout('login');

echo "<h1>SSO Login Form Stub</h1>";
$frm = new sso_login_form();

if ($frm->is_cancelled()) {

} else if ($data = $frm->get_data()) {
    $sso_config = get_config('auth_imisbridge');
    $courseid = optional_param('CourseID', 1, PARAM_INT);
    $redirect = "https://terry-pc.learningstacks.com/iaff_lms32dev/course/view.php?id=$courseid";
    $cookiename = $sso_config->sso_cookie_name;
    $cookiedomain = $sso_config->sso_cookie_domain;
    $cookiepath = $sso_config->sso_cookie_path;
    setcookie($cookiename, '1362661', null, $cookiepath, $cookiedomain);
    redirect($redirect);
    exit;
} else {
    $frm->display();
}

