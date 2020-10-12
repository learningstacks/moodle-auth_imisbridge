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

use auth_plugin_imisbridge;
use moodle_exception;
use coding_exception;
use dml_exception;
use PHPUnit\Framework\MockObject\MockObject;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/test_base.php');


/**
 * Test class for adhoc tasks.
 *
 * @package auth_imisbridge
 * @category auth
 * @copyright 2017 Learning Stacks LLC {@link https://learningstacks.com/}
 * @license   All Rights Reserved
 */
class auth_testcase extends test_base
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

    /**
     * @param $fldlock
     * @param $fldupdate
     * @throws dml_exception
     */
    public function set_field_map($fldlock, $fldupdate)
    {
        global $DB;
        foreach (self::$stdflds as $mdl => $imis) {
            set_config("field_map_$mdl", $imis, 'auth_imisbridge');
            set_config("field_lock_$mdl", $fldlock, 'auth_imisbridge');
            set_config("field_updatelocal_$mdl", $fldupdate, 'auth_imisbridge');
        }

        foreach (self::$cust_flds as $mdlfld => $imisfld) {
            $DB->insert_record('user_info_field', (object)[
                'shortname' => $mdlfld,
                'name' => $mdlfld,
                'datatype' => 'text',
                'categoryid' => 1,
            ]);
            set_config("field_map_profile_field_$mdlfld", $imisfld, 'auth_imisbridge');
            set_config("field_lock_profile_field_$mdlfld", $fldlock, 'auth_imisbridge');
            set_config("field_updatelocal_profile_field_$mdlfld", $fldupdate, 'auth_imisbridge');
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
            'CustomerID' => $id,
            'FirstName' => "{$id}_firstname",
            "LastName" => "{$id}_lastname",
            "Email" => "{$id}_email@email.com",
            "Member" => 1,
            "COMPANY" => "{$id}_company"
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
        $this->assertEquals('1', $auth->config->synch_profile);
        $this->assertEquals('0', $auth->config->create_user);
    }

    /**
     *
     */
    public function test_get_config_values()
    {
        $this->resetAfterTest(true);
        set_config('sso_login_url', 'sso_login_url', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_logout_url', 'sso_logout_url', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('synch_profile', '0', auth_plugin_imisbridge::COMPONENT_NAME);

        $auth = new auth_plugin_imisbridge();

        $this->assertSame('sso_login_url', $auth->config->sso_login_url);
        $this->assertSame('sso_logout_url', $auth->config->sso_logout_url);
        $this->assertSame('0', $auth->config->synch_profile);
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
     *
     */
    public function test_redirect_when_no_token()
    {
        $auth = $this->getMockBuilder(auth_plugin_imisbridge::class)
            ->setMethods(['get_token', 'redirect_to_sso_login'])
            ->getMock();
        $auth->expects($this->once())->method('get_token')->willReturn(null);
        $auth->expects($this->once())->method('redirect_to_sso_login');
        $auth->authenticate_user();
    }

    /**
     * @return array
     */
    public function data_test_authenticate_user_success()
    {
        $wantsurl = 'https://test.com/wantsurl';
        return [
            'token in param, no wantsurl' => [
                'token' => 'activeuser',
                'wantsurl' => null
            ],
            'token in param, wantsurl' => [
                'token' => 'activeuser',
                'wantsurl' => $wantsurl
            ],
            'token in wantsurl' => [
                'token' => null,
                'wantsurl' => $wantsurl . '?token=activeuser'
            ]
        ];
    }

    /**
     * @dataProvider data_test_authenticate_user_success
     * @param $token
     * @param $wantsurl
     */
    public function test_authenticate_user_success($token, $wantsurl)
    {
        global $CFG, $SESSION;

        $this->resetAfterTest(true);
        $gen = self::getDataGenerator();
        $gen->create_user(['username' => 'activeuser', 'suspended' => 0, 'deleted' => 0]);

        $_GET['token'] = $token;
        if (!empty($wantsurl)) {
            $SESSION->wantsurl = $wantsurl;
            $expected_redirect = $wantsurl;
        } else {
            unset($SESSION->wantsurl);
            $expected_redirect = $CFG->wwwroot;
        }

        $imis_profile = self::imis_user('activeuser');

        $auth = $this->getMockBuilder(auth_plugin_imisbridge::class)
            ->setMethods([
                'get_imis_profile',
                'complete_user_login',
                'redirect'
            ])
            ->getMock();
        $auth
            ->expects($this->once())
            ->method('get_imis_profile')
            ->with('activeuser')
            ->willReturn($imis_profile);

        $auth->expects($this->once())->method('complete_user_login');
        $auth->expects($this->once())->method('redirect')->with($expected_redirect);
        $auth->authenticate_user();
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
                'imis_profile' => null
            ],
            'no moodle user and not create' => [
                'token' => 'nouser',
                'imis_profile' => self::imis_user('nouser')
            ],
            'deleted user' => [
                'token' => 'deleteduser',
                'imis_profile' => self::imis_user('deleteduser')
            ],
            'suspended user' => [
                'token' => 'suspendeduser',
                'imis_profile' => self::imis_user('suspendeduser')
            ],
        ];
    }

    /**
     * @dataProvider data_test_authenticate_user_fail
     * @param $token
     * @param $imis_profile
     * @throws moodle_exception
     */
    public function test_authenticate_user_fail($token, $imis_profile)
    {
        global $DB;
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
        $auth = $this->get_auth_mock($token, $imis_profile, false, null);
        $this->expectException('Exception');
        $auth->authenticate_user();
    }

    /**
     * @return array[]
     */
    public function data_test_synch()
    {
        return [
            'synch, noforce, unlocked, onlogin, expect_update' => [true, false, 'unlocked', 'onlogin', true],
            'synch, noforce, unlocked, oncreate, expect_no_update' => [true, false, 'unlocked', 'oncreate', false],
            'synch, noforce, unlockedifempty, onlogin, expect_no_update' => [true, false, 'unlockedifempty', 'onlogin', false],
            'synch, noforce, unlockedifempty, oncreate, expect_no_update' => [true, false, 'unlockedifempty', 'oncreate', false],
            'synch, noforce, locked, onlogin, expect_no_update' => [true, false, 'locked', 'onlogin', false],
            'synch, noforce, locked, oncreate, expect_no_update' => [true, false, 'locked', 'oncreate', false],
            'no synch, noforce, unlocked, onlogin, expect_no_update' => [false, false, 'unlocked', 'onlogin', false],
            'no synch, force, locked, oncreate, expect_update' => [false, true, 'locked', 'oncreate', true],
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
    public function test_synch($synch_enabled, $force, $fldlock, $fldupdate, $should_update)
    {
        $this->resetAfterTest(true);

        $imisid = 'user1';
        set_config('synch_profile', $synch_enabled ? 1 : 0, 'auth_imisbridge');
        $this->set_field_map($fldlock, $fldupdate);

        $orig_imis_profile = self::imis_user('orig');
        $new_imis_profile = self::imis_user($imisid);

        $gen = $this->getDataGenerator();
        $gen->create_user([
            'username' => $imisid,
            'firstname' => $orig_imis_profile['FirstName'],
            'lastname' => $orig_imis_profile['LastName'],
            'email' => $orig_imis_profile['Email'],
            'idnumber' => $imisid,
            'profile_field_company' => $orig_imis_profile['COMPANY']
        ]);
        $origuser = get_complete_user_data('username', $imisid, 1);
        $this->validate_profile($origuser, $orig_imis_profile);
        $auth = new auth_plugin_imisbridge();
        $newuser = $auth->synch_user_record($imisid, $new_imis_profile, $force);
        $this->validate_profile($newuser, $should_update ? $new_imis_profile : $orig_imis_profile);
        $user = get_complete_user_data('username', $imisid, 1);
        $this->validate_profile($user, $should_update ? $new_imis_profile : $orig_imis_profile);
    }

    /**
     * @return array[]
     */
    public function data_test_create_user()
    {
        return [
            "no create, no synch, no login, no redirect" => [false, false, false, null],
            "create, no synch, login, redirect" => [true, false, true, 'https://target.com/']
        ];
    }

    /**
     * @dataProvider data_test_create_user
     * @param $create_enabled
     * @param $synch_enabled
     * @param $expect_login
     * @param $expect_redirect
     * @throws dml_exception
     */
    public function test_create_user($create_enabled, $synch_enabled, $expect_login, $expect_redirect)
    {
        global $SESSION;
        $this->resetAfterTest(true);

        $imisid = 'nouser';
        set_config('create_user', $create_enabled ? 1 : 0, 'auth_imisbridge');
        set_config('synch_profile', $synch_enabled ? 1 : 0, 'auth_imisbridge');
        if (!empty($expect_redirect)) {
            $SESSION->wantsurl = $expect_redirect;
        }

        $imis_profile = self::imis_user($imisid);
        $this->set_field_map('locked', 'oncreate');
        $auth = $this->get_auth_mock($imisid, $imis_profile, $expect_login, $expect_redirect);
        if (!$expect_login) {
            $this->expectException('Exception');
        }
        $auth->authenticate_user();

        if ($create_enabled) {
            $user = get_complete_user_data('username', $imisid, 1);
            $this->validate_profile($user, $imis_profile);
        }

    }
}
