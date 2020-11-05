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

use auth_plugin_imisbridge;
use moodle_exception;
use coding_exception;
use dml_exception;
use PHPUnit\Framework\MockObject\MockObject;

defined('MOODLE_INTERNAL') || die();


/**
 * Test class for adhoc tasks.
 *
 * @package auth_imisbridge
 * @category auth
 * @copyright 2017 Learning Stacks LLC {@link https://learningstacks.com/}
 * @license   All Rights Reserved
 */
class auth_testcase extends \advanced_testcase
{

    public static string $sso_login_url = "https://sso_login";
//    public static string $sso_logout_url = "https://sso_logout";
//    public static string $err_page_url = "https://err_page";

    public static array $stdflds = [
        'firstname' => 'FirstName',
        'lastname' => 'LastName',
        'email' => 'Email'
    ];

    public static array $cust_flds = [
        'company' => 'COMPANY'
    ];

    protected function create_customProfile_fields(array $field_names)
    {
        global $DB;
        foreach ($field_names as $field_name) {
            $DB->insert_record('user_info_field', (object)[
                'shortname' => $field_name,
                'name' => $field_name,
                'datatype' => 'text',
                'categoryid' => 1,
            ]);
        }
    }

    /**
     * @param $fldlock
     * @param $fldupdate
     * @throws dml_exception
     */
    public function set_field_map(array $mapspec)
    {
        global $DB;
        foreach ($mapspec as $spec) {
            list($mdl_name, $imis_name, $update_when, $lock) = $spec;
            set_config("field_map_$mdl_name", $imis_name, 'auth_imisbridge');
            set_config("field_lock_$mdl_name", $lock, 'auth_imisbridge');
            set_config("field_updatelocal_$mdl_name", $update_when, 'auth_imisbridge');
        }
    }

    /**
     * @param $mdluser
     * @param $imis_profile
     */
    public function validate_profile($mdluser, $imis_profile)
    {
        foreach (self::$stdflds as $mdlfld => $imisfld) {
            $this->assertEquals($imis_profile[$imisfld], $mdluser->$mdlfld, $mdlfld);
        }
        foreach (self::$cust_flds as $mdlfld => $imisfld) {
            $this->assertEquals($imis_profile[$imisfld], $mdluser->profile[$mdlfld], $mdlfld);
        }
    }

    /**
     * @param $id
     * @return array
     */
    public static function imis_user($id)
    {
        return [
            'customerid' => $id,
            'firstname' => "{$id}_firstname",
            "lastname" => "{$id}_lastname",
            "email" => "{$id}_email@email.com",
            "member" => 1,
            "company" => "{$id}_company"
        ];
    }

    /**
     * @param $token
     * @param $imis_profile
     * @param $expect_login
     * @param $expect_redirect
     * @return MockObject
     */
    public function get_auth_mock($token, $imis_profile, $expect_login, $expect_redirect)
    {
        $auth = $this->getMockBuilder(auth_plugin_imisbridge::class)
            ->setMethods([
                'get_token',
                'get_imis_profile',
                'complete_user_login',
                'redirect'
            ])
            ->getMock();
        $auth
            ->expects($this->once())
            ->method('get_token')
            ->willReturn($token);
        if (!empty($token)) {
            $auth
                ->expects($this->once())
                ->method('get_imis_profile')
                ->with($token)
                ->willReturn($imis_profile);
        } else {
            $auth->expects($this->never())->method('get_imis_profile');
        }

        $auth->expects($this->exactly($expect_login ? 1 : 0))->method('complete_user_login');

        if (!empty($expect_redirect)) {
            $auth->expects($this->once())->method('redirect')->with($expect_redirect);
        } else {
            $auth->expects($this->never())->method('redirect');
        }

        return $auth;
    }

    /**
     *
     */
    public function setUp()
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
     * @return array
     */
    public function data_test_logout_page_hook()
    {
        return [
            'no url' => [null, 'original'],
            'empty url' => ['', 'original'],
            'a url' => ['http://logout/', 'http://logout/']
        ];
    }

    /**
     * @dataProvider data_test_logout_page_hook
     * @param $sso_logout_url
     * @param $expected_redirect
     */
    public function test_logout_page_hook($sso_logout_url, $expected_redirect)
    {
        global $redirect;
        $redirect = 'original';
        $this->resetAfterTest(true);
        set_config('sso_logout_url', $sso_logout_url, auth_plugin_imisbridge::COMPONENT_NAME);
        $auth = new auth_plugin_imisbridge();
        $auth->logoutpage_hook();
        $this->assertSame($expected_redirect, $redirect);
    }

    /**
     * @group login_page_hook
     */
    public function test_loginpage_hooks()
    {
        $this->resetAfterTest(true);
        $auth = $this
            ->getMockBuilder(auth_plugin_imisbridge::class)
            ->setMethods(['authenticate_user'])->getMock();
        $auth->expects($this->exactly(2))->method('authenticate_user');

        $auth->pre_loginpage_hook();
        $auth->loginpage_hook();
    }

    /**
     * @return array
     */
    public function data_test_redirect_to_sso()
    {
        return [
            [
                'wantsurl' => null,
                'expect_id' => 1
            ],
            [
                'wantsurl' => '',
                'expect_id' => 1
            ],
            [
                'wantsurl' => 'https://lms.example.com',
                'expect_id' => 1,
            ],
            [
                'wantsurl' => 'https://lms.example.com?id=4',
                'expect_id' => 1,
            ],
            [
                'wantsurl' => 'https://lms.example.com/course/view.php?id=4',
                'expect_id' => 4,
            ]
        ];
    }

    /**
     * @dataProvider data_test_redirect_to_sso
     * @param $token
     * @param $wantsurl
     */
    public function test_redirect_to_sso($wantsurl, $expect_id)
    {
        global $SESSION;
        $sso_login_url = 'https://sso.example.com';
        set_config('sso_login_url', $sso_login_url, auth_plugin_imisbridge::COMPONENT_NAME);

        if (is_null($wantsurl)) {
            unset($SESSION->wantsurl);
        } else {
            $SESSION->wantsurl = $wantsurl;
        }
        $expect_redirect_url = "$sso_login_url?id=$expect_id";
        $auth = $this->getMockBuilder(auth_plugin_imisbridge::class)
            ->setMethods(['redirect'])
            ->getMock();
        $auth->expects($this->once())->method('redirect')->with($expect_redirect_url);
        $auth->authenticate_user();
    }

    /**
     * @return array
     */
    public function data_test_authenticate_user_success()
    {
        $wantsurl = 'https://test.com/wantsurl';
        return [
            'token in wantsurl' => [
                'token' => null,
                'wantsurl' => $wantsurl . '?token=activeuser'
            ]
        ];
    }

    /**
     * @return string[][]
     */
    public function data_test_authenticate_user_bypass()
    {
        return [
            'nosso' => ['name' => 'nosso', 'val' => ''],
            'username' => ['name' => 'username', 'val' => 'something'],
        ];
    }

    /**
     * Verify authenticate is bypassed when nosso or a username is provided
     * @dataProvider data_test_authenticate_user_bypass
     * @param $name
     * @param $val
     */
    public function test_authenticate_user_bypass($name, $val)
    {
        global $_GET;
        $this->resetAfterTest(true);

        $auth = $this->getMockBuilder(auth_plugin_imisbridge::class)
            ->setMethods(['get_token'])->getMock();
        $auth->expects($this->never())->method('get_token');

        $_GET[$name] = $val;
        $this->assertFalse($auth->authenticate_user());
    }

    /**
     * @return array[]
     */
    public function data_test_authenticate_user_fail()
    {
        return [
            'no imis profile' => [
                'token' => 'activeuser',
                'imis_profile' => null,
                'no_imis_user'
            ],
            'no moodle user and not create' => [
                'token' => 'nouser',
                'imis_profile' => self::imis_user('nouser'),
                'no_lms_user'
            ],
            'deleted user' => [
                'token' => 'deleteduser',
                'imis_profile' => self::imis_user('deleteduser'),
                'deleted_lms_user'
            ],
            'suspended user' => [
                'token' => 'suspendeduser',
                'imis_profile' => self::imis_user('suspendeduser'),
                'suspended_lms_user'
            ],
        ];
    }

    /**
     * @dataProvider data_test_authenticate_user_fail
     * @param $token
     * @param $imis_profile
     * @throws moodle_exception
     */
    public function test_authenticate_user_fail($token, $imis_profile, $error_code)
    {
        global $DB, $SESSION;
        $this->resetAfterTest(true);

        $gen = self::getDataGenerator();
        $gen->create_user(['username' => 'activeuser', 'suspended' => 0, 'deleted' => 0]);
        $gen->create_user(['username' => 'suspendeduser', 'suspended' => 1, 'deleted' => 0]);
        $deleteduser = $gen->create_user(['username' => 'deleteduser', 'suspended' => 0, 'deleted' => 1]);
        // Deleted users have their username changed, change it back so we find the record
        $deleteduser->username = 'deleteduser';
        $DB->update_record('user', $deleteduser);

        set_config('create_user', 0, 'auth_imisbridge');
        set_config('sso_login_url', self::$sso_login_url, 'auth_imisbridge');
        $SESSION->wantsurl = "/?token={$token}";

        $auth = $this->getMockBuilder(auth_plugin_imisbridge::class)
            ->setMethods([
                'get_imis_profile',
                'display_error'
            ])
            ->getMock();
        $auth
            ->expects($this->once())
            ->method('get_imis_profile')
            ->with($token)
            ->willReturn($imis_profile);
        $auth
            ->expects($this->once())
            ->method('display_error')
            ->with($error_code);

        $auth->authenticate_user();
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
     * @param $force
     * @param $fldlock
     * @param $fldupdate
     * @param $should_update
     * @throws coding_exception
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
        $gen->create_user([
            'username' => $imisid,
            'firstname' => $crnt_firstname,
            'profile_field_company' => $crnt_company
        ]);
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

//    /**
//     * @return array[]
//     */
//    public function data_test_create_user()
//    {
//        return [
//            "no create, no synch, no login, no redirect" => [false, false, false, null],
//            "create, no synch, login, redirect" => [true, false, true, 'https://target.com/']
//        ];
//    }
//
//    /**
//     * @dataProvider data_test_create_user
//     * @param $create_enabled
//     * @param $synch_enabled
//     * @param $expect_login
//     * @param $expect_redirect
//     * @throws dml_exception
//     */
//    public function test_create_user($create_enabled, $synch_enabled, $expect_login, $expect_redirect)
//    {
//        global $SESSION;
//        $this->resetAfterTest(true);
//
//        $imisid = 'nouser';
//        set_config('create_user', $create_enabled ? 1 : 0, 'auth_imisbridge');
//        set_config('synch_profile', $synch_enabled ? 1 : 0, 'auth_imisbridge');
//        if (!empty($expect_redirect)) {
//            $SESSION->wantsurl = $expect_redirect;
//        }
//
//        $imis_profile = self::imis_user($imisid);
//        $this->set_field_map([
//            ['firstname', 'Firstname', 'create', 'unlocked'],
//            ['lastname', 'lastname', 'create', 'unlocked'],
//            ['email', 'email', 'create', 'unlocked'],
//        ]);
//        $auth = $this->get_auth_mock($imisid, $imis_profile, $expect_login, $expect_redirect);
//        if (!$expect_login) {
//            $this->expectException('Exception');
//        }
//        $auth->authenticate_user();
//['customerid' => $imisid]
//        if ($create_enabled) {
//            $user = get_complete_user_data('username', $imisid, 1);
//            $this->validate_profile($user, $imis_profile);
//        }
//
//    }

    public function test_new_user_create_disabled()
    {
        global $SESSION;
        $this->resetAfterTest(true);

        $imisid = 'new_user';
        $SESSION->wantsurl = "/?token=$imisid";
        set_config('create_user', 0, 'auth_imisbridge');
        $auth = $this->getMockBuilder(auth_plugin_imisbridge::class)
            ->setMethods([
                'get_imis_profile',
                'display_error'
            ])
            ->getMock();
        $auth
            ->expects($this->once())
            ->method('get_imis_profile')
            ->with($imisid)
            ->willReturn(['customerid' => $imisid]);
        $auth
            ->expects($this->once())
            ->method('display_error')
            ->with('no_lms_user');

        $auth->authenticate_user();
    }

    public function test_new_user_create_enabled_full_profile()
    {
        global $CFG, $SESSION;
        $this->resetAfterTest(true);

        $imisid = 'nouser';
        $SESSION->wantsurl = "/?token=$imisid";
        set_config('create_user', 1, 'auth_imisbridge');
        $auth = $this->getMockBuilder(auth_plugin_imisbridge::class)
            ->setMethods([
                'get_imis_profile',
                'redirect'
            ])
            ->getMock();
        $auth
            ->expects($this->once())
            ->method('get_imis_profile')
            ->with($imisid)
            ->willReturn([
                'customerid' => $imisid,
                'firstname' => 'firstname',
                'lastname' => 'lastname',
                'email' => 'user@example.com'
                ]);
        $auth
            ->expects($this->once())
            ->method('redirect')
            ->with($CFG->wwwroot . '/course/view.php?id=1');

        $auth->authenticate_user();
    }
}
