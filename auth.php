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


ini_set('display_errors', 1);
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/authlib.php');
require_once($CFG->dirroot . '/user/lib.php');

class auth_plugin_imisbridge extends auth_plugin_base
{

    const COMPONENT_NAME = "auth_imisbridge";

    public function __construct()
    {
        $this->authtype = 'imisbridge';
        $this->config = $this->get_config();
    }

    /**
     * Returns true if the username and password work and false if they are
     * wrong or don't exist.
     *
     * @param string $imis_id The username
     * @param string $password The password
     * @return bool Authentication success or failure.
     */
    function user_login($imis_id, $password)
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
    function user_update_password($user, $newpassword)
    {
        $this->redirect_to_sso_login();
    }

    function prevent_local_passwords()
    {
        return false;
    }

    /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * @return bool
     */
    function is_internal()
    {
        return false;
    }

    function is_synchronised_with_external()
    {
        return true;
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
    function change_password_url()
    {
        return '';
    }

    /**
     * Returns true if plugin allows resetting of internal password.
     *
     * @return bool
     */
    function can_reset_password()
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

        $default = new stdClass();
        $default->sso_login_url = '';
        $default->sso_logout_url = '';
        $default->sso_cookie_name = '';
        $default->sso_cookie_path = '/';
        $default->sso_cookie_domain = '';
        $default->sso_cookie_remove_on_logout = '1';
        $default->sso_cookie_is_encrypted = '1';

        $config = (object)array_merge((array)$default, (array)$data);

        return $config;
    }

    /**
     * Prints a form for configuring this authentication plugin.
     *
     * This function is called from admin/auth.php, and outputs a full page with
     * a form for configuring this plugin.
     *
     * @param stdClass $form
     * @param array $err errors
     * @param array $user_fields
     * @return void
     */
    function config_form($form, $err, $user_fields)
    {
        $config = $this->config; // Passed into included script
        include 'config_form.php';
    }

    /**
     * A chance to validate form data, and last chance to
     * do stuff before it is inserted in config_plugin
     *
     * @param stdClass $form
     * @param array $err errors
     * @return void
     */
    function validate_form($form, &$err)
    {

        if (!isset($form->sso_login_url) || trim($form->sso_login_url) == '') {
            $err['sso_login_url'] = get_string('sso_login_url_is_required', self::COMPONENT_NAME);
        }

        if (!isset($form->sso_logout_url) || trim($form->sso_logout_url) == '') {
            $err['sso_logout_url'] = get_string('sso_logout_url_is_required', self::COMPONENT_NAME);
        }

        if (!isset($form->sso_cookie_name) || trim($form->sso_cookie_name) == '') {
            $err['sso_cookie_name'] = get_string('sso_cookie_name_is_required', self::COMPONENT_NAME);
        }

        if (!isset($form->sso_cookie_path) || trim($form->sso_cookie_path) == '') {
            $err['sso_cookie_path'] = get_string('sso_cookie_path_is_required', self::COMPONENT_NAME);
        }

        if (!isset($form->sso_cookie_domain) || trim($form->sso_cookie_domain) == '') {
            $err['sso_cookie_domain'] = get_string('sso_cookie_domain_is_required', self::COMPONENT_NAME);
        }

        if (!isset($form->sso_cookie_remove_on_logout) || trim($form->sso_cookie_remove_on_logout) == '') {
            $err['sso_cookie_remove_on_logout'] = get_string('sso_cookie_remove_on_logout_is_required', self::COMPONENT_NAME);
        }

        if (!isset($form->sso_cookie_is_encrypted) || trim($form->sso_cookie_is_encrypted) == '') {
            $err['sso_cookie_is_encrypted'] = get_string('sso_cookie_is_encrypted_is_required', self::COMPONENT_NAME);
        }

    }

    /**
     * Processes and stores configuration data for this authentication plugin.
     *
     * @param stdClass $form
     * @return bool always true or exception
     */
    function process_config($form)
    {
        set_config('sso_login_url', trim($form->sso_login_url), self::COMPONENT_NAME);
        set_config('sso_logout_url', trim($form->sso_logout_url), self::COMPONENT_NAME);
        set_config('sso_cookie_name', trim($form->sso_cookie_name), self::COMPONENT_NAME);
        set_config('sso_cookie_path', trim($form->sso_cookie_path), self::COMPONENT_NAME);
        set_config('sso_cookie_domain', trim($form->sso_cookie_domain), self::COMPONENT_NAME);
        set_config('sso_cookie_remove_on_logout', $form->sso_cookie_remove_on_logout, self::COMPONENT_NAME);
        set_config('sso_cookie_is_encrypted', $form->sso_cookie_is_encrypted, self::COMPONENT_NAME);

        return true;
    }


    /**
     * Confirm the new user as registered. This should normally not be used,
     * but it may be necessary if the user auth_method is changed to manual
     * before the user is confirmed.
     */
    function user_confirm($imis_id, $confirmsecret = null)
    {
    }

    function logoutpage_hook()
    {
        global $redirect;

        if ($this->config->sso_cookie_remove_on_logout == 1) {
            $this->expire_sso_cookie();
        }

        $redirect = $this->config->sso_logout_url;
    }

    public function loginpage_hook()
    {
        global $CFG, $user, $COURSE;

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
        $imis_id = $this->get_imis_id_from_sso_cookie();
        if ($imis_id) {
            $user = $this->get_user_by_imis_id($imis_id);
            if ($user) {
                $user = update_user_record_by_id($user->id);
                $this->complete_user_login($user);         // Complete setting up the $USER
                return $this->redirect($urltogo);   // Send to originally requested url
                // redirect function will not return
                // return value is returned to support unit test
            }
        }

        // Either user was not found, the cookie was not present, or imis_id not valid, user was not active...
        $this->redirect_to_sso_login($courseid); // Does not return if redirect succeeds

        // Else authentication failed
        return false;
    }

    protected function update_user_profile($user)
    {
        $updated_user = $user;

        /*
         * Get profile details from IMIS_BRIDGE
         * Update user record and custom fields
         */
//        $imis_profile = $this->get_user_profile_from_imis($user);
//        $moodle_profile = null;

        return $updated_user;
    }

    protected function redirect_to_sso_login($courseid = 1)
    {
        $params = ['CourseID' => $courseid];

        if ($this->config->sso_login_url) {
            $sso_login_url = new moodle_url($this->config->sso_login_url, $params);
            $this->redirect($sso_login_url->out(), get_string('loginthroughthemainwebsite', self::COMPONENT_NAME));
        }

        return false;
    }

    protected function redirect($url, $msg = null)
    {
        redirect($url, $msg);
    }

    public function get_sso_cookie()
    {
        $cookie = null;

        if (!empty($_COOKIE[$this->config->sso_cookie_name])) {
            $cookie = $_COOKIE[$this->config->sso_cookie_name]; // Contains id
        }

        return $cookie;
    }

    protected function expire_sso_cookie()
    {
        setcookie($this->config->sso_cookie_name, "", time() - 3600, $this->config->sso_cookie_path, $this->config->sso_cookie_domain); // domain may be null
    }

    protected function get_imis_id_from_sso_cookie()
    {
        $imis_id = null;
        $cookie = $this->get_sso_cookie();

        if ($cookie) {
            if ($this->config->sso_cookie_is_encrypted) { // Cookie is encrypted
                try {
                    $svc = $this->get_service_proxy();
                    $imis_id = $svc->decrypt($cookie); // null returned on error
                } catch (\Exception $e) {
                    $imis_id = null;
                }
            } else {
                $imis_id = $cookie; // imis_id is not encrypted (dev only)
            }
        }

        return $imis_id;
    }

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

    public function get_userinfo($username)
    {
        return array();
    }

    protected function get_service_proxy()
    {
        return new \local_imisbridge\service_proxy();
    }

    protected function decrypt($val)
    {
        $svc = $this->get_service_proxy();
        return $svc->decrypt($val);
    }

    protected function complete_user_login($user)
    {
        complete_user_login($user);
    }
}
