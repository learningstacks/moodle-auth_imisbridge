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
     * @var null
     */
    protected $logfile = null;

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
     * @param $msg
     * @param null $data
     */
    protected function log($msg, $data = null)
    {
        if (!empty($this->logfile)) {
            file_put_contents($this->logfile, PHP_EOL . $msg . PHP_EOL . print_r($data, true), FILE_APPEND);
        }
    }

    /**
     * Returns true if the username and password work and false if they are
     * wrong or don't exist.
     *
     * @param string $imis_id The username
     * @param string $password The password
     * @return void Authentication success or failure.
     * @throws moodle_exception
     */
    public function user_login($imis_id, $password)
    {
        $this->redirect_to_sso_login(1);
    }

    /**
     * Updates the user's password.
     *
     * called when the user password is updated.
     *
     * @param object $user User table object  (with system magic quotes)
     * @param string $newpassword Plaintext password (with system magic quotes)
     * @return void result
     * @throws moodle_exception
     */
    public function user_update_password($user, $newpassword)
    {
        $this->redirect_to_sso_login(1);
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
     * Confirm the new user as registered. This should normally not be used,
     * but it may be necessary if the user auth_method is changed to manual
     * before the user is confirmed.
     * @param string $imis_id
     * @param null $confirmsecret
     */
    public function user_confirm($imis_id, $confirmsecret = null)
    {
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
        global $CFG, $USER, $COURSE;

        // If nosso url parameter is present skip this auth
        if (!is_null(optional_param('nosso', null, PARAM_RAW))) {
            return false;
        }

        // If user entered credentials skip this auth (manual login attempted)
        if (!empty(optional_param('username', '', PARAM_TEXT))) {
            return false;
        }

        // Determine the URL to which user should be redirected upon successful authentication
        $SESSION = $_SESSION['SESSION'];
        $urltogo = isset($SESSION->wantsurl) ? $SESSION->wantsurl : $CFG->wwwroot;
        unset($SESSION->wantsurl);

        // Determine course that is to be viewed
        // Bridge only redirects to course/view.php with courseid parameter
        $courseid = !empty($COURSE->id) ? $COURSE->id : 1;
        $redirect_msg = null;

        $token = $this->get_token();
        if ($token) {
            $imis_profile = $this->get_service_proxy()->get_imis_profile($token);
            if ($imis_profile) {
                $imis_id = $imis_profile['CustomerID'];
                $user = $this->get_user_by_imis_id($imis_id);
                if ($user) {
                    if ($this->config->synch_profile) {
                        $user = $this->synch_user_record($user, $imis_profile);
                    }
                    $this->complete_user_login($user);         // Complete setting up the $USER
                    $this->log('User logged in ', $USER);
                    return $this->redirect($urltogo);   // Send to originally requested url
                    // redirect function will not return
                    // return value is returned to support unit test
                } else {
                    $redirect_msg = get_string('moodle_user_not_found', 'auth_imisbridge', $imis_id);
                }
            }
        }

        // Either user was not found, imis_id was not provided, imis_id not valid, user was not active.
        $this->redirect_to_sso_login($courseid, $redirect_msg); // Does not return if redirect succeeds

        // Else authentication failed
        return false;
    }

    /**
     * @param int $courseid After SSO login IMIS will redirect to this course
     * @param null $msg Will be displayed to the user before the redirect occurs
     * @return bool
     * @throws moodle_exception
     */
    public function redirect_to_sso_login($courseid, $msg = null)
    {
        $params = ['id' => $courseid];

        if (!empty($this->config->sso_login_url)) {
            $sso_login_url = (new moodle_url($this->config->sso_login_url, $params))->out();
            $this->redirect($sso_login_url, $msg);
        }

        return false;
    }

    /**
     * Execute a redirect. THis function is added to support unit test.
     * @param moodle_url|string $url
     * @param string|null $msg
     * @throws moodle_exception
     */
    protected function redirect($url, $msg = null)
    {
        redirect($url, $msg);
    }

    /**
     * Obtain and decrypt the userid stored in the token parameter
     *
     * @return null|string
     * @throws coding_exception
     * @throws moodle_exception
     */
    protected function get_token()
    {
        $imis_id = null;

        $token = optional_param('token', null, PARAM_TEXT);

        // Token may be part of the original target URL which is now
        // in the session wantsurl variable
        if (is_null($token)) {
            if (isset($_SESSION['SESSION']->wantsurl)) {
                $url = new moodle_url($_SESSION['SESSION']->wantsurl);
                $token = $url->get_param('token');
            }
        }

        return $token;
    }

    /**
     * Return a Moodle user record given an imis_id.
     *
     * @param string $imis_id
     * @return mixed|null
     */
    protected function get_user_by_imis_id($imis_id)
    {
        global $DB;

        $user = null;
        $auth = 'manual';
        try {
            $user = $DB->get_record('user', array('username' => $imis_id, 'deleted' => 0, 'suspended' => 0, 'auth' => $auth));
            if ($user === false) {
                $user = null;
            }
        } catch (dml_exception $ex) {
            $user = null;
        }

        return $user;
    }

    /**
     * @param $fld
     * @param $data
     * @return string|null
     */
    protected function get_src_value($fld, $data)
    {
        if (isset($this->config->{'field_map_' . $fld})) {
            $srcname = $this->config->{'field_map_' . $fld};
            if (isset($data[$srcname])) {
                return $data[$srcname];
            }
        }

        return null;
    }

    /**
     * Update the user's Moodle profile to match their IMIS profile.
     *
     * @param stdClass $user
     * @param array $imis_profile
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function synch_user_record($user, $imis_profile)
    {
        global $PAGE;

        $PAGE->set_context(context_system::instance());
        $origuser = get_complete_user_data('id', $user->id);
        $newuser = array();
        $this->log('imis_profile', $imis_profile);
        $customfields = $this->get_custom_user_profile_fields();

        // Map incoming date to moodle profile fields
        $flds = array_merge($this->userfields, $customfields);
        $newinfo = [];
        foreach ($flds as $fld) {
            $val = $this->get_src_value($fld, $imis_profile);
            if (!is_null($val)) {
                $newinfo[$fld] = $val;
            }
        }
        $newinfo = truncate_userinfo($newinfo);

        foreach ($newinfo as $key => $value) {

            $iscustom = in_array($key, $customfields);
            if (!$iscustom) {
                $key = strtolower($key);
            }

            // Skip fields that should never be updated or do not exist in Moodle
            if ((!property_exists($origuser, $key) && !$iscustom)
                or $key === 'username'
                or $key === 'id'
                or $key === 'auth'
                or $key === 'mnethostid'
                or $key === 'deleted'
                or $key === 'idnumber'
            ) {
                // Unknown or must not be changed.
                continue;
            }

            if ($iscustom) {
                $name = str_replace('profile_field_', '', $key);
                $origval = $origuser->profile[$name];
            } else {
                $origval = $origuser->$key;
            }

            $update = $this->config->{'field_updatelocal_' . $key};
            $lock = $this->config->{'field_lock_' . $key};
            $updateable = !empty($update)
                && !empty($lock)
                && ($update === 'onlogin')
                && ($lock === 'unlocked' || ($lock === 'unlockedifempty' and empty($origval)))
                && (string)$origval !== (string)$value;

            if ($updateable) {
                $newuser[$key] = (string)$value;
            }
        }

        if ($newuser) {
            $newuser['id'] = $origuser->id;
            $newuser['timemodified'] = time();
            user_update_user((object)$newuser, false, false);

            // Save user profile data.
            profile_save_data((object)$newuser);

            // Trigger event.
            \core\event\user_updated::create_from_userid($newuser['id'])->trigger();
        }

        return get_complete_user_data('id', $origuser->id);
    }

    /**
     * Get the IMIS Bridge services proxy.
     *
     * @return \local_imisbridge\service_proxy
     */
    protected function get_service_proxy()
    {
        return new \local_imisbridge\service_proxy();
    }

    /**
     * @param stdClass $user
     * @return stdClass
     */
    protected function complete_user_login($user)
    {
        return complete_user_login($user);
    }
}
