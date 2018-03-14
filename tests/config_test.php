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
namespace auth_imisbridge\tests;

use auth_plugin_imisbridge;
use \stdClass;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/test_base.php');

/**
 * Test class for adhoc tasks.
 *
 * @package auth_imisbridge
 * @category auth
 * @copyright 2017 Learning Stacks LLC
 * @license   All Rights Reserved
 */
class config_testcase extends test_base
{

    public function setUp()
    {
        $_COOKIE = [];
    }

    /**
     * @throws \dml_exception
     */
    public function test_get_config_defaults()
    {
        $this->resetAfterTest(true);
        $auth = new test_subject();
        $config = $auth->get_config();

        $this->assertSame('', $config->sso_login_url);
        $this->assertSame('', $config->sso_logout_url);
        $this->assertSame('', $config->sso_cookie_name);
        $this->assertSame('/', $config->sso_cookie_path);
        $this->assertSame('', $config->sso_cookie_domain);
        $this->assertSame('1', $config->sso_cookie_remove_on_logout);
        $this->assertSame('0', $config->sso_cookie_is_encrypted);
        $this->assertSame('0', $config->synch_profile);
    }

    /**
     * @throws \dml_exception
     */
    public function test_get_config_values()
    {
        $this->resetAfterTest(true);
        set_config('sso_login_url', 'sso_login_url', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_logout_url', 'sso_logout_url', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_name', 'sso_cookie_name', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_path', 'sso_cookie_path', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_domain', 'sso_cookie_domain', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_remove_on_logout', '0', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_is_encrypted', '1', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('synch_profile', '1', auth_plugin_imisbridge::COMPONENT_NAME);
        $auth = new test_subject();
        $config = $auth->get_config();

        $this->assertSame('sso_login_url', $config->sso_login_url);
        $this->assertSame('sso_logout_url', $config->sso_logout_url);
        $this->assertSame('sso_cookie_name', $config->sso_cookie_name);
        $this->assertSame('sso_cookie_path', $config->sso_cookie_path);
        $this->assertSame('sso_cookie_domain', $config->sso_cookie_domain);
        $this->assertSame('0', $config->sso_cookie_remove_on_logout);
        $this->assertSame('1', $config->sso_cookie_is_encrypted);
        $this->assertSame('1', $config->synch_profile);
    }

    // validate_form

    /**
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_validate_form_nulls()
    {
        $this->resetAfterTest(true);

        $form = new \stdClass();
        $err = array();
        $subj = new test_subject();
        $subj->validate_form($form, $err);
        $this->assertArrayHasKey('sso_login_url', $err);
        $this->assertArrayHasKey('sso_logout_url', $err);
        $this->assertArrayHasKey('sso_cookie_name', $err);
        $this->assertArrayHasKey('sso_cookie_path', $err);
        $this->assertArrayHasKey('sso_cookie_domain', $err);
        $this->assertArrayHasKey('sso_cookie_remove_on_logout', $err);
        $this->assertArrayHasKey('sso_cookie_is_encrypted', $err);
        $this->assertArrayHasKey('synch_profile', $err);
    }

    /**
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_validate_form_blanks()
    {
        $this->resetAfterTest(true);

        $form = new \stdClass();
        $err = array();
        $subj = new test_subject();

        $form->sso_login_url = '  ';
        $form->sso_logout_url = '  ';
        $form->sso_cookie_name = '  ';
        $form->sso_cookie_path = '  ';
        $form->sso_cookie_domain = '  ';
        $form->sso_cookie_remove_on_logout = '  ';
        $form->sso_cookie_is_encrypted = '  ';
        $form->synch_profile = '  ';

        $subj->validate_form($form, $err);
        $this->assertArrayHasKey('sso_login_url', $err);
        $this->assertArrayHasKey('sso_logout_url', $err);
        $this->assertArrayHasKey('sso_cookie_name', $err);
        $this->assertArrayHasKey('sso_cookie_path', $err);
        $this->assertArrayHasKey('sso_cookie_domain', $err);
        $this->assertArrayHasKey('sso_cookie_remove_on_logout', $err);
        $this->assertArrayHasKey('sso_cookie_is_encrypted', $err);
        $this->assertArrayHasKey('synch_profile', $err);
    }

    /**
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_validate_form_valid()
    {
        $this->resetAfterTest(true);

        $form = new \stdClass();
        $err = array();
        $subj = new test_subject();

        $form->sso_login_url = 'http://bogus.com/';
        $form->sso_logout_url = 'http://bogus.com/';
        $form->sso_cookie_name = 'abc';
        $form->sso_cookie_path = '/';
        $form->sso_cookie_domain = 'a.b.com';
        $form->sso_cookie_remove_on_logout = '1';
        $form->sso_cookie_is_encrypted = '1';
        $form->synch_profile = '1';

        $subj->validate_form($form, $err);
        $this->assertArrayNotHasKey('sso_login_url', $err);
        $this->assertArrayNotHasKey('sso_logout_url', $err);
        $this->assertArrayNotHasKey('sso_cookie_name', $err);
        $this->assertArrayNotHasKey('sso_cookie_path', $err);
        $this->assertArrayNotHasKey('sso_cookie_domain', $err);
        $this->assertArrayNotHasKey('sso_cookie_remove_on_logout', $err);
        $this->assertArrayNotHasKey('sso_cookie_is_encrypted', $err);
        $this->assertArrayNotHasKey('synch_profile', $err);
    }

    // process_config

    /**
     * @throws \dml_exception
     */
    public function test_process_config()
    {
        $this->resetAfterTest(true);

        $data = new stdClass();
        $data->sso_login_url = ' a ';
        $data->sso_logout_url = ' b ';
        $data->sso_cookie_name = ' c ';
        $data->sso_cookie_path = ' d ';
        $data->sso_cookie_domain = ' e ';
        $data->sso_cookie_remove_on_logout = '2';
        $data->sso_cookie_is_encrypted = '3';
        $data->synch_profile = '1';

        $subj = new test_subject();
        $subj->process_config($data);
        $config = $subj->get_config();

        $this->assertSame('a', $config->sso_login_url);
        $this->assertSame('b', $config->sso_logout_url);
        $this->assertSame('c', $config->sso_cookie_name);
        $this->assertSame('d', $config->sso_cookie_path);
        $this->assertSame('e', $config->sso_cookie_domain);
        $this->assertSame('2', $config->sso_cookie_remove_on_logout);
        $this->assertSame('3', $config->sso_cookie_is_encrypted);
        $this->assertSame('1', $config->synch_profile);
    }

}
