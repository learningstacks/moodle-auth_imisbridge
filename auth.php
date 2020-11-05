<?php
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


use local_imisbridge\service_proxy;

ini_set('display_errors', 1);
defined('MOODLE_INTERNAL') || die();

global $CFG;
/** @noinspection PhpIncludeInspection */
require_once($CFG->libdir . '/authlib.php');
/** @noinspection PhpIncludeInspection */
require_once($CFG->dirroot . '/user/lib.php');
/** @noinspection PhpIncludeInspection */
require_once($CFG->dirroot . '/user/profile/lib.php');

/**
 * Class auth_plugin_imisbridge
 */
class auth_plugin_imisbridge extends auth_plugin_base
{

    /**
     *
     */
    const COMPONENT_NAME = "auth_imisbridge";

    /**
     * auth_plugin_imisbridge constructor.
     * @throws dml_exception
     */
    public function __construct()
    {
        $this->authtype = 'imisbridge';
        $this->config = get_config(self::COMPONENT_NAME);
    }

    /**
     * Returns true if the username and password work and false if they are
     * wrong or don't exist.
     *
     * @param string $username The username
     * @param string $password The password
     * @return bool Authentication success or failure.
     */
    public function user_login($username, $password)
    {
        return false;
    }

    /**
     * @return bool
     */
    public function prevent_local_passwords()
    {
        return false;
    }

    /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * @return bool
     */
    public function is_internal()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function is_synchronised_with_external()
    {
        return false;
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return bool
     */
    function can_change_password()
    {
        return false;
    }

    /**
     * Returns the URL for changing the user's pw, or empty if the default can
     * be used.
     *
     * @return string
     */
    public function change_password_url()
    {
        return '';
    }

    /**
     * Returns true if plugin allows resetting of internal password.
     *
     * @return bool
     */
    public function can_reset_password()
    {
        return false;
    }

    /**
     * Confirm the new user as registered.
     * @param string $username
     * @param string|null $confirmsecret
     * @return int
     * @throws dml_exception
     */
    public function user_confirm($username, $confirmsecret = null): int
    {
        global $DB;

        $user = get_complete_user_data('username', $username);

        if (!empty($user)) {
            if ($user->confirmed) {
                return AUTH_CONFIRM_ALREADY;
            } else {
                $DB->set_field("user", "confirmed", 1, array("id"=>$user->id));
                return AUTH_CONFIRM_OK;
            }
        } else  {
            return AUTH_CONFIRM_ERROR;
        }
    }

    /**
     *
     */
    public function logoutpage_hook()
    {
        global $redirect;

        if (isset($this->config->sso_logout_url) && !empty($this->config->sso_logout_url)) {
            $redirect = $this->config->sso_logout_url;
        }
    }

    /**
     * @return bool|void
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function pre_loginpage_hook()
    {
        return $this->authenticate_user();
    }

    /**
     * @return bool|void
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function loginpage_hook()
    {
        return $this->authenticate_user();
    }

    /**
     * @return bool|void
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function authenticate_user()
    {
        global $CFG, $USER, $SESSION;

        unset($SESSION->notifications);

        // If nosso url parameter is present skip this auth
        if (!is_null(optional_param('nosso', null, PARAM_RAW))) {
            return false;
        }

        // If user entered credentials skip this auth (manual login attempted)
        if (!empty(optional_param('username', '', PARAM_TEXT))) {
            return false;
        }

        // defaults
        $token = optional_param('token', null, PARAM_RAW);
        $courseid = 1;
        $event = 'login';

        if (!empty($SESSION->wantsurl)) {
            $wantsurl = new moodle_url($SESSION->wantsurl);
            $courseid = ($wantsurl->get_path() == '/course/view.php') ? $wantsurl->get_param('id') : 1;
        }

        if (empty($token)) {
            // keep $SESSION->wantsurl
            $this->redirect_to_sso_login($courseid, null); // Does not return if redirect succeeds
            return false; // should not reach this except in unit test
        }

        $imis_profile = $this->get_imis_profile($token);
        if (empty($imis_profile)) {
            unset($SESSION->wantsurl);
            $this->display_error('no_imis_user', $this->config->sso_logout_url, $token);
            return false; // should not reach this except in unit test
        }

        $imis_id = $imis_profile['customerid'];
        $user = empty($imis_id) ? null : $this->get_user_by_imis_id($imis_id);
        if (empty($user)) {
            if ($this->config->create_user) {
                $user = create_user_record($imis_id, generate_password(), 'manual');
                $event = 'create';
            } else {
                unset($SESSION->wantsurl);
                $this->display_error('no_lms_user', $this->config->imis_home_url, $imis_id);
                return false; // should not reach this except in unit test
            }
        }

        if ($user->deleted) {
            unset($SESSION->wantsurl);
            $this->display_error('deleted_lms_user', $this->config->imis_home_url, $imis_id);
            return false; // should not reach this except in unit test
        }

        if ($user->suspended) {
            unset($SESSION->wantsurl);
            $this->display_error('suspended_lms_user', $this->config->imis_home_url, $imis_id);
            return false; // should not reach this except in unit test
        }

        // We have an authenticated IMIS user and a matching, active Moodle account, log in
        $user = $this->synch_user_record($event, $imis_id, $imis_profile);
        complete_user_login($user);

        if (user_not_fully_set_up($USER, true)) {
            $urltogo = $CFG->wwwroot . '/user/edit.php';
            // We don't delete $SESSION->wantsurl yet, so we get there later
        } else if (isset($SESSION->wantsurl) and (strpos($SESSION->wantsurl, $CFG->wwwroot) === 0)) {
            $urltogo = $SESSION->wantsurl;    // Because it's an address in this site
            unset($SESSION->wantsurl);
        } else {
            // No wantsurl stored or external - go to homepage
            $urltogo = $CFG->wwwroot . '/';
            unset($SESSION->wantsurl);
        }

        $this->redirect($urltogo);

        return false; // should not reach this except in unit test
    }

    /**
     * @param int $courseid After SSO login IMIS will redirect to this course
     * @param string|null $msg Will be displayed to the user before the redirect occurs
     * @param int|null $delay
     * @return bool
     * @throws moodle_exception
     */
    public function redirect_to_sso_login(int $courseid, string $msg = null, int $delay = null)
    {
        $params = ['id' => $courseid];

        if (!empty($this->config->sso_login_url)) {
            $sso_login_url = (new moodle_url($this->config->sso_login_url, $params))->out();
            $this->redirect($sso_login_url, $msg, $delay);
        }

        return false;
    }

    /**
     * @param string $error_code
     * @param null $continue_url
     * @param string $imisid
     * @throws coding_exception
     * @throws dml_exception
     */
    protected function display_error(string $error_code, $continue_url = null, $imisid = '')
    {
        global $PAGE, $OUTPUT;

        $error_heading = get_string("${error_code}_title", self::COMPONENT_NAME, $imisid);
        $error_message = get_string("${error_code}_message", self::COMPONENT_NAME, $imisid);
        $continue_message = get_string("imis_home_continue_message", self::COMPONENT_NAME);

        header("HTTP/1.1 401 Unauthorized");
        header("Status: 401 Unauthorized");

        $PAGE->set_url('/auth/imisbridge/error.php');
        $PAGE->set_context(context_system::instance());
        $PAGE->set_title('IMIS SSO Error');
        $PAGE->set_heading($error_heading);
        echo $OUTPUT->header();
        echo $OUTPUT->box($error_message, 'generalbox boxalignleft');
        echo $OUTPUT->box($continue_message, 'generalbox boxalignleft');

        if (!empty($continue_url)) {
            echo $OUTPUT->continue_button($continue_url);
        }

        echo $OUTPUT->footer();
        exit;
    }

    /**
     * Execute a redirect. This function should be stubbed during unit test.
     * @param moodle_url|string $url
     * @param string|null $msg
     * @param null $delay
     * @throws moodle_exception
     */
    protected function redirect($url, $msg = null, $delay = null)
    {
        redirect($url, $msg, $delay);
    }

    /**
     * Return a Moodle user record given an imis_id.
     *
     * @param string $imis_id
     * @return mixed|null
     * @throws dml_exception
     */
    protected function get_user_by_imis_id(string $imis_id)
    {
        global $DB;
        return $DB->get_record('user', ['username' => $imis_id], '*', IGNORE_MISSING);
    }

    /**
     * Update the user's Moodle profile to match their IMIS profile.
     *
     * @param string $event Either 'create' or 'login'
     * @param string $username
     * @param array $imis_profile
     * @return mixed False, or A {@link $USER} object.
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function synch_user_record(string $event, string $username, array $imis_profile)
    {
        global $DB;

        if ($this->config->synch_profile) {
            // Fetch current user properties
            $crntuser = [];
            $userrec = $DB->get_record('user', ['username' => $username], '*', MUST_EXIST);
            foreach ($this->userfields as $key) {
                $crntuser[$key] = $userrec->{$key};
            }
            foreach (profile_user_record($userrec->id) as $key => $val) {
                $crntuser['profile_field_' . $key] = $val;
            }

            // Determine which fields are to be updated
            $updated_fields = [];
            foreach ($crntuser as $fld => $crntval) {
                $imisfld = empty($this->config->{'field_map_' . $fld}) ? null : strtolower($this->config->{'field_map_' . $fld});
                $update_when = empty($this->config->{'field_updatelocal_' . $fld}) ? null : $this->config->{'field_updatelocal_' . $fld};
                $lock = empty($this->config->{'field_lock_' . $fld}) ? null : $this->config->{'field_lock_' . $fld};

                if (
                    !empty($imisfld) // mapped
                    && isset($imis_profile[$imisfld]) // exists
                    && $imis_profile[$imisfld] != $crntval // value differs
                    && ($lock == 'unlocked' || ($lock == 'unlockedifempty' && empty($crntval))) // not locked
                    && ($update_when == 'onlogin' || $event == 'create') // matches event
                ) {
                    $updated_fields[$fld] = $imis_profile[$imisfld];
                }

            }

            if (!empty($updated_fields)) {
                $newuser = (object)truncate_userinfo($updated_fields);
                $newuser->id = $userrec->id;
                $newuser->timemodified = time();
                profile_save_data($newuser);
                user_update_user($newuser, false, true);
            }
        }

        return get_complete_user_data('username', $username);
    }

    /**
     * Get the IMIS Bridge services proxy.
     *
     * @return service_proxy
     */
    protected function get_service_proxy()
    {
        return new service_proxy();
    }

    protected function get_imis_profile($token)
    {
        return $this->get_service_proxy()->get_imis_profile($token);
    }

}
