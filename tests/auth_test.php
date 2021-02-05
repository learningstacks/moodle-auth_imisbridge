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
 * @copyright 2017 Learning Stacks LLC {@link https://learningstacks.com/}
 * @license   All Rights Reserved
 */

namespace auth_imisbridge\tests;

require_once(__DIR__ . '/../auth.php');

use advanced_testcase;
use auth_plugin_imisbridge;
use moodle_exception;
use dml_exception;

defined('MOODLE_INTERNAL') || die();


/**
 * Test class for adhoc tasks.
 *
 * @package auth_imisbridge
 * @category auth
 * @copyright 2017 Learning Stacks LLC {@link https://learningstacks.com/}
 * @license   All Rights Reserved
 */
class auth_testcase extends advanced_testcase
{
    /**
     * @param array $mapspec
     */
    protected function set_field_map(array $mapspec)
    {
        foreach ($mapspec as $spec) {
            list($mdl_name, $imis_name, $update_when, $lock) = $spec;
            set_config("field_map_$mdl_name", $imis_name, 'auth_imisbridge');
            set_config("field_lock_$mdl_name", $lock, 'auth_imisbridge');
            set_config("field_updatelocal_$mdl_name", $update_when, 'auth_imisbridge');
        }
    }

    /**
     *
     */
    protected function setUp()
    {
        global $CFG;
        $this->resetAfterTest(true);
        $CFG->debugdeveloper = false;
        $CFG->debug = 0;
    }

    /**
     *
     */
    public function test_properties()
    {
        $this->resetAfterTest(true);
        $auth = new auth_plugin_imisbridge();
        $this->assertFalse($auth->can_reset_password());
        $this->assertFalse($auth->can_change_password());
        $this->assertFalse($auth->is_internal());
        $this->assertSame('', $auth->change_password_url());
        $this->assertFalse($auth->is_synchronised_with_external());
    }

    /**
     *
     */
    public function test_get_config_defaults()
    {
        $this->resetAfterTest(true);
        $auth = new auth_plugin_imisbridge();
        $this->assertEquals('', $auth->config->sso_login_url);
        $this->assertEquals('', $auth->config->sso_logout_url);
        $this->assertEquals('', $auth->config->imis_home_url);
        $this->assertEquals('1', $auth->config->synch_profile);
        $this->assertEquals('0', $auth->config->create_user);
    }

    /**
     *
     */
    public function test_get_config_values()
    {
        $this->resetAfterTest(true);
        set_config('sso_login_url', 'https://sso_login.example.com', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_logout_url', 'https://sso_logout.example.com', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('imis_home_url', 'https://imis.example.com', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('synch_profile', '0', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('create_user', '1', auth_plugin_imisbridge::COMPONENT_NAME);

        $auth = new auth_plugin_imisbridge();

        $this->assertSame('https://sso_login.example.com', $auth->config->sso_login_url);
        $this->assertSame('https://sso_logout.example.com', $auth->config->sso_logout_url);
        $this->assertSame('https://imis.example.com', $auth->config->imis_home_url);
        $this->assertSame('0', $auth->config->synch_profile);
        $this->assertSame('1', $auth->config->create_user);
    }

    /**
     * @return array[]
     */
    public function data_test_synch()
    {
        // event, enabled, map, when, lock, crnt, expect
        return [
            ['synch_enabled', 'create', 'oncreate', 'unlocked', 'not_empty', 'should_update'],
            ['synch_enabled', 'create', 'oncreate', 'unlockedifempty', 'empty', 'should_update'],
            ['synch_enabled', 'create', 'oncreate', 'unlockedifempty', 'not_empty', 'should_not_update'],
            ['synch_enabled', 'create', 'oncreate', 'locked', 'empty', 'should_not_update'],

            ['synch_enabled', 'create', 'onlogin', 'unlocked', 'not_empty', 'should_update'],
            ['synch_enabled', 'create', 'onlogin', 'unlockedifempty', 'empty', 'should_update'],
            ['synch_enabled', 'create', 'onlogin', 'unlockedifempty', 'not_empty', 'should_not_update'],
            ['synch_enabled', 'create', 'onlogin', 'locked', 'empty', 'should_not_update'],

            ['synch_enabled', 'login', 'oncreate', 'unlocked', 'empty', 'should_not_update'],
            ['synch_enabled', 'login', 'oncreate', 'empty', 'empty', 'should_not_update'],
            ['synch_enabled', 'login', 'oncreate', 'unlockedifempty', 'empty', 'should_not_update'],
            ['synch_enabled', 'login', 'oncreate', 'locked', 'empty', 'should_not_update'],

            ['synch_enabled', 'login', 'onlogin', 'unlocked', 'not_empty', 'should_update'],
            ['synch_enabled', 'login', 'onlogin', 'unlockedifempty', 'empty', 'should_update'],
            ['synch_enabled', 'login', 'onlogin', 'unlockedifempty', 'not_empty', 'should_not_update'],
            ['synch_enabled', 'login', 'onlogin', 'locked', 'empty', 'should_not_update'],

            ['synch_disabled', 'create', 'onlogin', 'unlocked', 'not_empty', 'should_not_update'],

        ];
    }

    /**
     * @dataProvider data_test_synch
     * @param $synch_enabled
     * @param $event
     * @param $updatewhen
     * @param $fldlock
     * @param $crnt_value
     * @param $expect
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function test_synch($synch_enabled, $event, $updatewhen, $fldlock, $crnt_value, $expect)
    {
        global $DB;

        $this->resetAfterTest(true);
        $imisid = 'user1';
        set_config('synch_profile', ($synch_enabled == 'synch_enabled') ? 1 : 0, 'auth_imisbridge');
        $DB->insert_record('user_info_field', (object)[
            'shortname' => 'company',
            'name' => 'company',
            'categoryid' => 1,
            'datatype' => 'text'
        ]);
        $this->set_field_map([
            ['firstname', 'Firstname', $updatewhen, $fldlock],
            ['profile_field_company', 'COMPANY', $updatewhen, $fldlock]
        ]);

        $crnt_firstname = $crnt_value == 'empty' ? '' : 'crnt_firstname';
        $crnt_company = $crnt_value == 'empty' ? '' : 'crnt_company';
        $gen = $this->getDataGenerator();
        $user = $gen->create_user([
            'username' => $imisid,
            'firstname' => $crnt_firstname,
        ]);
        $user->profile_field_company = $crnt_company;
        profile_save_data($user);
        $imis_profile = [
            'customerid' => $imisid,
            'firstname' => "new_firstname",
            "company" => "new_company"
        ];

        $auth = new auth_plugin_imisbridge();
        $user = $auth->synch_user_record($event, $imisid, $imis_profile);
        $userrec = $DB->get_record('user', ['username' => $imisid]);
        $custdata = profile_user_record($user->id);

        $should_update = ($expect == 'should_update');
        $this->assertEquals($user->firstname, $userrec->firstname);
        $this->assertEquals($user->profile['company'], $custdata->company);
        $this->assertEquals($should_update ? 'new_firstname' : $crnt_firstname, $user->firstname);
        $this->assertEquals($should_update ? 'new_company' : $crnt_company, $user->profile['company']);
    }

}
