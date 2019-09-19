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
 * @copyright 2017 onwards Learning Stacks LLC {@link https://learningstacks.com/}
 * @license   All Rights Reserved
 */


ini_set('display_errors', 1);
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/authlib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

/**
 * Class auth_plugin_imisbridge
 */
class auth_plugin_imisbridge extends auth_plugin_base
{

    protected $logfile = null;

    /**
     *
     */
    const COMPONENT_NAME = "auth_imisbridge";

    /**
     * auth_plugin_imisbridge constructor.
     */
    public function __construct()
    {
        $this->authtype = 'imisbridge';
        $this->config = $this->get_config();
    }

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
     * @return bool Authentication success or failure.
     */
    public function user_login($imis_id, $password)
    {
        $this->redirect_to_sso_login();
    }

    /**
     * Updates the user's password.
     *
     * called when the user password is updated.
     *
     * @param  object $user User table object  (with system magic quotes)
     * @param  string $newpassword Plaintext password (with system magic quotes)
     * @return boolean result
     *
     */
    public function user_update_password($user, $newpassword)
    {
        $this->redirect_to_sso_login();
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
     * @return object
     * @throws dml_exception
     */
    protected function get_config()
    {
        $data = get_config(self::COMPONENT_NAME);
        return $data;
    }


    /**
     * Confirm the new user as registered. This should normally not be used,
     * but it may be necessary if the user auth_method is changed to manual
     * before the user is confirmed.
     */
    public function user_confirm($imis_id, $confirmsecret = null)
    { }

    /**
     *
     */
    public function logoutpage_hook()
    {
        global $redirect;

        if ($this->config->sso_cookie_remove_on_logout == 1) {
            $this->expire_sso_cookie();
        }

        $redirect = $this->config->sso_logout_url;
    }

    public function pre_loginpage_hook()
    {
        return $this->authenticate_user();
    }

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
        $urltogo = isset($SESSION->wantsurl) ? $SESSION->wantsurl : $CFG->wwwroot . '/';
        unset($SESSION->wantsurl);

        // Determine course that is to be viewed
        // Bridge only redirects to course/view.php with courseid parameter
        $courseid = isset($COURSE->id) ? $COURSE->id : 1;

        // The imis_id of the user is passed in the configured cookie.
        $imis_id = $this->get_imis_id();
        if ($imis_id) {
            $user = $this->get_user_by_imis_id($imis_id);
            if ($user) {
                if ($this->config->synch_profile) {
                    $user = $this->synch_user_record($user);
                }
                $this->complete_user_login($user);         // Complete setting up the $USER
                $this->log('User logged in ', $USER);
                return $this->redirect($urltogo);   // Send to originally requested url
                // redirect function will not return
                // return value is returned to support unit test
            } else {
                throw new moodle_exception('No user with that IMIS_ID');
            }
        }

        // Either user was not found, the cookie was not present, or imis_id not valid, user was not active...
        $this->redirect_to_sso_login($courseid); // Does not return if redirect succeeds

        // Else authentication failed
        return false;
    }

    /**
     * @param int $courseid
     * @return bool
     * @throws coding_exception
     */
    protected function redirect_to_sso_login($courseid = 1)
    {
        $params = ['id' => $courseid];

        if ($this->config->sso_login_url) {
            $sso_login_url = new moodle_url($this->config->sso_login_url, $params);
            $this->redirect($sso_login_url->out());
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
     * Return the sso cookie contents if the cookie exists
     * @return null|string
     */
    public function get_sso_cookie()
    {
        $cookie = null;

        if (!empty($_COOKIE[$this->config->sso_cookie_name])) {
            $cookie = $_COOKIE[$this->config->sso_cookie_name]; // Contains id
        }

        return $cookie;
    }

    /**
     * Mark the sso cookie as expired
     */
    protected function expire_sso_cookie()
    {
        setcookie($this->config->sso_cookie_name, "", time() - 3600, $this->config->sso_cookie_path, $this->config->sso_cookie_domain); // domain may be null
    }

    /**
     * Obtain and decrypt (if necessary) the userid stored either in the token parameter
     * or SSO Cookie.
     *  
     * @return null|string
     */
    public function get_imis_id()
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

        // Perhaps we have a cookie?
        if (is_null($token)) {
            $cookie = $this->get_sso_cookie();
            if ($cookie) {
                $token = $cookie;
            }
        }

        if (!empty($token)) {
            if ($this->config->sso_cookie_is_encrypted) { // Cookie is encrypted
                try {
                    $svc = $this->get_service_proxy();
                    $imis_id = $svc->decrypt($token); // null returned on error
                    $this->log("token decryption suceeded ({$imis_id})");
                } catch (\Exception $e) {
                    debugging("token decryption failed ({$e->getMessage()}", DEBUG_DEVELOPER);
                    $imis_id = null;
                }
            } else {
                $imis_id = $token; // imis_id is not encrypted (dev only)
            }
        }

        return $imis_id;
    }

    /**
     * Return a Moodle user record given an imis_id.
     *
     * @param string $imis_id
     * @return mixed|null
     */
    public function get_user_by_imis_id($imis_id)
    {
        global $DB;

        $user = null;
        $auth = 'manual';

        $user = $DB->get_record('user', array('idnumber' => $imis_id, 'deleted' => 0, 'suspended' => 0, 'auth' => $auth));
        if ($user === false) {
            $user = null;
        }

        // TODO: Consider logging/reporting errors for each result state suspended, deleted, not found

        return $user;
    }

    /**
     * Provide a map from IMIS field names to Moodle field names.
     *
     * @return array
     */
    protected function get_field_map()
    {
        $map = [];
        include(__DIR__ . '/profile_field_map.php');
        return $map;
    }

    /**
     * Fetch the User's contact data from IMIS
     * @param $imis_id
     * @return array
     */
    protected function get_contact_info($imis_id)
    {
        $svc = $this->get_service_proxy();
        $newinfo = $svc->get_contact_info($imis_id);
        return $newinfo;
    }

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
     * @return array
     */
    public function synch_user_record($user)
    {
        global $PAGE;

        $PAGE->set_context(context_system::instance());
        $origuser = get_complete_user_data('id', $user->id);
        $newuser = array();
        $imis_id = $origuser->idnumber;
        $contact_info = $this->get_contact_info($imis_id);
        $this->log('contact_info', $contact_info);
        $customfields = $this->get_custom_user_profile_fields();

        // Map incoming date to moodle profile fields
        $flds = array_merge($this->userfields, $customfields);
        $newinfo = [];
        foreach ($flds as $fld) {
            $val = $this->get_src_value($fld, $contact_info);
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
                && (string) $origval !== (string) $value;

            if ($updateable) {
                $newuser[$key] = (string) $value;
            }
        }

        if ($newuser) {
            $newuser['id'] = $origuser->id;
            $newuser['timemodified'] = time();
            user_update_user((object) $newuser, false, false);

            // Save user profile data.
            profile_save_data((object) $newuser);

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
     * Decrypt a string.
     *
     * @param string $val
     * @return null|string
     */
    protected function decrypt($val)
    {
        $svc = $this->get_service_proxy();
        return $svc->decrypt($val);
    }

    /**
     * @param \stdClass $user
     */
    protected function complete_user_login($user)
    {
        return complete_user_login($user);
    }
}
