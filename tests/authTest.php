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
use local_imisbridge\service_proxy;
use stdClass;
use moodle_exception;
use moodle_url;
use dml_exception;

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

    /**
     *
     */
    public function setUp()
    {
        global $CFG;
        $CFG->debugdeveloper = false;
        $CFG->debug = 0;
    }

    /**
     * @throws dml_exception
     */
    public function test_properties()
    {
        $this->resetAfterTest(true);
        $auth = new \auth_plugin_imisbridge();
        $this->assertFalse($auth->can_reset_password());
        $this->assertFalse($auth->can_change_password());
        $this->assertFalse($auth->is_internal());
        $this->assertSame('', $auth->change_password_url());
        $this->assertFalse($auth->is_synchronised_with_external());
    }

    /**
     * @throws \dml_exception
     */
    public function test_get_config_defaults()
    {
        $this->resetAfterTest(true);
        $auth = new \auth_plugin_imisbridge();
        $this->assertEquals('', $auth->config->sso_login_url);
        $this->assertEquals('', $auth->config->sso_logout_url);
        $this->assertEquals('1', $auth->config->synch_profile);
    }

    /**
     * @throws \dml_exception
     */
    public function test_get_config_values()
    {
        $this->resetAfterTest(true);
        set_config('sso_login_url', 'sso_login_url', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_logout_url', 'sso_logout_url', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('synch_profile', '0', auth_plugin_imisbridge::COMPONENT_NAME);

        $auth = new \auth_plugin_imisbridge();

        $this->assertSame('sso_login_url', $auth->config->sso_login_url);
        $this->assertSame('sso_logout_url', $auth->config->sso_logout_url);
        $this->assertSame('0', $auth->config->synch_profile);
    }


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
     * @throws \dml_exception
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
            ->getMockBuilder(\auth_plugin_imisbridge::class)
            ->setMethods(['authenticate_user'])->getMock();
        $auth->expects($this->exactly(2))->method('authenticate_user');

        $auth->pre_loginpage_hook();
        $auth->loginpage_hook();
    }

    /**
     * @return array
     */
    public function data_test_authenticate_user_success()
    {
        global $CFG;
        $wantsurl = 'https://test.com/wantsurl';
        return [
            'token in param, no wantsurl' => [
                'token_param' => 'validuser',
                'wantsurl' => null,
                'expected_redirect' => $CFG->wwwroot
            ],
            'token in param, wantsurl' => [
                'token_param' => 'validuser',
                'wantsurl' => $wantsurl,
                'expected_redirect' => $wantsurl
            ],
            'token in wantsurl' => ['token_param' => 'null',
                'wantsurl' => $wantsurl . '?token=validuser',
                'expected_redirect' => $wantsurl . '?token=validuser'
            ]
        ];
    }

    /**
     * @dataProvider data_test_authenticate_user_success
     * @param $token_param
     * @param $wantsurl
     * @param $expected_redirect
     * @throws moodle_exception
     */
    public function test_authenticate_user_success($token_param, $wantsurl, $expected_redirect)
    {
        global $USER;

        $this->resetAfterTest(true);

        if ($token_param) {
            $_GET['token'] = $token_param;
        } else {
            unset($_GET['token']);
        }

        if ($wantsurl) {
            $_SESSION['SESSION']->wantsurl = $wantsurl;
        } else {
            unset($_SESSION['SESSION']->wantsurl);
        }
        $gen = $this->getDataGenerator();
        $user = $gen->create_user([
            'idnumber' => 'activeuser',
            'auth' => 'manual'
        ]);

        $imis_profile = [
            'CustomerID' => 'activeuser',
            'FirstName' => 'imis_firstname',
            'LastName' => 'imis_lastname',
            'Email' => 'imis_email@atsol.org',
            'Member' => 1,
            'COMPANY' => 'imis_company'
        ];

        $svc = $this->getMockBuilder(service_proxy::class)
            ->setMethods([
                'get_imis_profile'
            ])
            ->getMock();
        $svc->expects($this->once())
            ->method('get_imis_profile')
            ->willReturn($imis_profile);


        $auth = $this->getMockBuilder(\auth_plugin_imisbridge::class)
            ->setMethods([
                'get_service_proxy',
                'redirect',
                'complete_user_login'
            ])
            ->getMock();
        $auth->expects($this->any())
            ->method('get_service_proxy')
            ->willReturn($svc);
        $auth->expects($this->once())
            ->method('redirect')
            ->with($this->equalTo((new moodle_url($expected_redirect))->out()));
        $auth->expects($this->once())
            ->method('complete_user_login')
            ->with($this->callback(function ($arg) use ($imis_profile) {
                return $arg->idnumber == $imis_profile['CustomerID'];
            }));

        $this->assertEquals(0, $USER->id);
        $auth->authenticate_user();
    }

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

        $auth = $this->getMockBuilder(\auth_plugin_imisbridge::class)
            ->setMethods(['get_imis_id'])->getMock();
        $auth->expects($this->never())->method('get_imis_id');

        $_GET[$name] = $val;
        $this->assertFalse($auth->authenticate_user());
    }


    public function data_test_authenticate_user_fail()
    {
        $imis_profile = [
            'CustomerID' => 'activeuser',
            'FirstName' => 'imis_firstname',
            'LastName' => 'imis_lastname',
            'Email' => 'imis_email@atsol.org',
            'Member' => 1,
            'COMPANY' => 'imis_company'
        ];

        $ssourl = 'https://sso/';

        // no token
        // no imis profile
        // no moodle user
        // deleted moodle user
        // suspended moodle user
        return [
            'no token' => [
                'token' => null,
                'imis_profile' => $imis_profile,
                'user_status' => 'active',
                'courseid' => null,
                'ssourl' => $ssourl
            ],
            'no imis profile' => [
                'token' => 'activeuser',
                'imis_profile' => null,
                'user_status' => 'active',
                'courseid' => 1,
                'ssourl' => $ssourl
            ],
            'no moodle user' => [
                'token' => 'activeuser',
                'imis_profile' => $imis_profile,
                'user_status' => null,
                'courseid' => 2,
                'ssourl' => $ssourl
            ],
            'deleted user' => [
                'token' => 'activeuser',
                'imis_profile' => $imis_profile,
                'user_status' => 'deleted',
                'courseid' => 3,
                'ssourl' => $ssourl
            ],
            'suspended user' => [
                'token' => 'activeuser',
                'imis_profile' => $imis_profile,
                'user_status' => 'suspended',
                'courseid' => 4,
                'ssourl' => null
            ],
        ];
    }

    /**
     * @dataProvider data_test_authenticate_user_fail
     * @param $token
     * @param $imis_profile
     * @param $user_status
     * @param $courseid
     * @param $ssourl
     * @throws moodle_exception
     */
    public function test_authenticate_user_fail($token, $imis_profile, $user_status, $courseid, $ssourl)
    {
        global $CFG, $COURSE;
        $this->resetAfterTest(true);

        if ($token) {
            $_GET['token'] = $token;
        } else {
            unset($_GET['token']);
        }

        set_config('sso_login_url', $ssourl, 'auth_imisbridge');

        $gen = $this->getDataGenerator();
        if ($user_status == 'active') {
            $gen->create_user(['idnumber' => 'activeuser']);
        } else if ($user_status == 'deleted') {
            $gen->create_user(['idnumber' => 'activeuser', 'deleted' => 1]);
        } else if ($user_status == 'suspended') {
            $gen->create_user(['idnumber' => 'activeuser', 'suspended' => 1]);
        }

        $CFG->wwwroot = 'http://root';
        $COURSE->id = $courseid ? $courseid : 1;
        $expected_redirect_url = (new moodle_url($ssourl, ['id' => $COURSE->id]))->out();

        $svc = $this->getMockBuilder(service_proxy::class)
            ->setMethods([
                'get_imis_profile'
            ])
            ->getMock();
        $svc->expects($this->exactly($token ? 1 : 0))
            ->method('get_imis_profile')
            ->willReturn($imis_profile);

        $auth = $this->getMockBuilder(\auth_plugin_imisbridge::class)
            ->setMethods([
                'get_service_proxy',
                'redirect',
                'complete_user_login'
            ])
            ->getMock();
        $auth->expects($this->any())
            ->method('get_service_proxy')
            ->willReturn($svc);
        $auth->expects($this->exactly($ssourl ? 1 : 0))
            ->method('redirect')
            ->with($this->equalTo($expected_redirect_url));
        $auth->expects($this->never())->method('complete_user_login');

        $auth->authenticate_user();
    }

    public function data_test_synch()
    {
        return [
            [true, 'unlocked', 'onlogin', true],
            [true, 'unlocked', 'oncreate', false],
            [true, 'unlockedifempty', 'onlogin', false],
            [true, 'unlockedifempty', 'oncreate', false],
            [true, 'locked', 'onlogin', false],
            [true, 'locked', 'oncreate', false],
            [false, 'unlocked', 'onlogin', false]
        ];
    }

    /**
     * @dataProvider data_test_synch
     * @param $synch_enabled
     * @param $fldlock
     * @param $fldupdate
     * @param $should_update
     * @throws \dml_exception
     */
    public function test_synch($synch_enabled, $fldlock, $fldupdate, $should_update)
    {
        global $DB;
        $this->resetAfterTest(true);

        $token = 'user1';
        $_GET['token'] = $token;
        set_config('synch_profile', $synch_enabled ? 1 : 0, 'auth_imisbridge');

        $imis_profile = [
            'CustomerID' => $token,
            'FirstName' => 'imis_firstname',
            'LastName' => 'imis_lastname',
            'Email' => 'imis_email@atsol.org',
            'Member' => 1,
            'COMPANY' => 'imis_company'
        ];

        $gen = $this->getDataGenerator();
        $orig_user = $gen->create_user([
            'username' => $token,
            'firstname' => 'orig_firstname',
            'lastname' => 'orig_lastname',
            'email' => 'orig@email.com',
            'idnumber' => $token,
        ]);

        $stdflds = [
            'firstname' => 'FirstName',
            'lastname' => 'LastName',
            'email' => 'Email',
//            'profile_field_company' => 'COMPANY'
        ];

        foreach ($stdflds as $mdl => $imis) {
            set_config("field_map_$mdl", $imis, 'auth_imisbridge');
            set_config("field_lock_$mdl", $fldlock, 'auth_imisbridge');
            set_config("field_updatelocal_$mdl", $fldupdate, 'auth_imisbridge');
        }

        $custflds = [
            'company' => 'COMPANY'
        ];

        foreach ($custflds as $mdlfld => $imisfld) {
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


        $svc = $this->getMockBuilder(service_proxy::class)
            ->setMethods([
                'get_imis_profile'
            ])
            ->getMock();
        $svc->expects($this->once())
            ->method('get_imis_profile')
            ->willReturn($imis_profile);

        $auth = $this->getMockBuilder(\auth_plugin_imisbridge::class)
            ->setMethods([
                'get_service_proxy',
                'redirect',
                'complete_user_login'
            ])
            ->getMock();
        $auth->expects($this->any())
            ->method('get_service_proxy')
            ->willReturn($svc);
        $auth->expects($this->once())->method('redirect');
        $auth->expects($this->once())->method('complete_user_login');

        $auth->authenticate_user();
        $user = get_complete_user_data('idnumber', $token, 1);
        foreach ($stdflds as $mdlfld => $imisfld) {
            $this->assertEquals($should_update ? $imis_profile[$imisfld] : $orig_user->$mdlfld, $user->$mdlfld, $mdlfld);
        }
//        foreach ($custflds as $mdlfld => $imisfld) {
//            $this->assertEquals($should_update ? $imis_profile[$imisfld] : $orig_user->$mdlfld, $user->$mdlfld, $mdlfld);
//        }
    }
}
