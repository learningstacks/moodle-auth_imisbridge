<?php
global $CFG;
require_once("../../../../../config.php");
require_once("../../../../../lib/formslib.php");

class imisbridge_logon_form extends moodleform
{
    //Add elements to form
    protected function definition()
    {
        $mform = $this->_form; // Don't forget the underscore!
        $mform->addElement('text', 'username', "Username"); // Add elements to your form
        $mform->setType('username', PARAM_RAW);
        $mform->addElement('hidden', 'id', $_REQUEST['id']);
        $mform->setType('id', PARAM_RAW);
        $this->add_action_buttons(false, "Log In");
    }
}

$context = context_system::instance();
global $PAGE;
$PAGE->set_url("$CFG->wwwroot/auth/imisbridge/tests/behat/fixtures/sso_login.php");
$PAGE->set_context($context);
$PAGE->set_pagelayout('login');

$mform = new imisbridge_logon_form();

if ($data = $mform->get_data()) {
    $params = [
        "id" => $data->id,
        "token" => $data->username
    ];
    $url = new moodle_url("/course/view.php", $params);
    redirect($url, '', 0);
} else {
    echo("SSO Login Page");
    $mform->display();
}


