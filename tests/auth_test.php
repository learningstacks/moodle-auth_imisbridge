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
use local_imisbridge\service_proxy;
use stdClass;

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
class auth_imisbridge_testcase extends test_base
{


    /**
     *
     */
    public function setUp()
    {
        global $CFG;
        $_COOKIE = [];
        $CFG->debugdeveloper = false;
        $CFG->debug = 0;
    }

    /**
     * @throws \dml_exception
     */
    public function test_basics()
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
        $this->assertEquals('MoodleSSO', $auth->config->sso_cookie_name);
        $this->assertEquals('/', $auth->config->sso_cookie_path);
        $this->assertEquals('', $auth->config->sso_cookie_domain);
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
        set_config('sso_cookie_name', 'sso_cookie_name', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_path', 'sso_cookie_path', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_domain', 'sso_cookie_domain', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('synch_profile', '0', auth_plugin_imisbridge::COMPONENT_NAME);

        $auth = new \auth_plugin_imisbridge();

        $this->assertSame('sso_login_url', $auth->config->sso_login_url);
        $this->assertSame('sso_logout_url', $auth->config->sso_logout_url);
        $this->assertSame('sso_cookie_name', $auth->config->sso_cookie_name);
        $this->assertSame('sso_cookie_path', $auth->config->sso_cookie_path);
        $this->assertSame('sso_cookie_domain', $auth->config->sso_cookie_domain);
        $this->assertSame('0', $auth->config->synch_profile);
    }

    /**
     * @return array
     */
    public function data_test_get_imis_id()
    {
        return [
            'cookie' => [
                'cookie' => 'encrypted_cookie',
                'token' => null,
                'wantsurl' => null,
                'expected_result' => 'unencrypted_encrypted_cookie',
                'decrypt_throws' => false
            ],
            'token' => [
                'cookie' => null,
                'token' => 'encrypted_token',
                'wantsurl' => null,
                'expected_result' => 'unencrypted_encrypted_token',
                'decrypt_throws' => false
            ],
            'token in wantsaurl' => [
                'cookie' => null,
                'token' => null,
                'wantsurl' => 'https://any.com/?token=encrypted_token_in_wantsurl',
                'expected_result' => 'unencrypted_encrypted_token_in_wantsurl',
                'decrypt_throws' => false
            ],
            'No token or cookie' => [
                'cookie' => null,
                'token' => null,
                'wantsurl' => null,
                'expected_result' => null,
                'decrypt_throws' => false
            ],
            'Decrypt fails' => [
                'cookie' => null,
                'token' => 'encrypted_token',
                'wantsurl' => null,
                'expected_result' => null,
                'decrypt_throws' => true
            ],
            'token overrides wantsurl and cookie' => [
                'cookie' => 'encrypted_cookie',
                'token' => 'encrypted_token',
                'wantsurl' => 'https://any.com/?token=encrypted_token_in_wantsurl',
                'expected_result' => 'unencrypted_encrypted_token',
                'decrypt_throws' => false
            ],
            'token in wantsurl overrides cookie' => [
                'cookie' => 'unencrypted_cookie',
                'token' => null,
                'wantsurl' => 'https://any.com/?token=encrypted_token_in_wantsurl',
                'expected_result' => 'unencrypted_encrypted_token_in_wantsurl',
                'decrypt_throws' => false
            ],
        ];
    }

    /**
     * @dataProvider data_test_get_imis_id
     * @param $cookie
     * @param $token
     * @param $wantsurl
     * @param $expected_result
     * @param $decrypt_throws
     */
    public function test_get_imis_id($cookie, $token, $wantsurl, $expected_result, $decrypt_throws)
    {
        global $_COOKIE, $_GET, $_SESSION;

        $this->resetAfterTest(true);

        set_config('sso_cookie_path', '/', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_name', 'cookiename', auth_plugin_imisbridge::COMPONENT_NAME);

        if ($token) {
            $_GET['token'] = $token;
        } else {
            unset($_GET['token']);
        }

        if ($cookie) {
            $_COOKIE['cookiename'] = $cookie;
        } else {
            unset($_COOKIE['cookiename']);
        }

        if ($wantsurl) {
            $_SESSION['SESSION']->wantsurl = $wantsurl;
        } else {
            unset($_SESSION['SESSION']->wantsurl);
        }

        $svc = $this->createMock(iServiceProxy::class);
        if ($decrypt_throws) {
            $svc->expects($this->once())
                ->method('decrypt')
                ->will($this->throwException(new \Exception));
        } elseif (!$cookie && !$token && !$wantsurl) {
            $svc->expects($this->never())
                ->method('decrypt');
        } else {
            $svc->expects($this->once())
                ->method('decrypt')
                ->willReturn($expected_result);
        }

        $auth = $this->getMockBuilder(\auth_plugin_imisbridge::class)
            ->setMethods(['get_service_proxy'])
            ->getMock();
        $auth->expects($this->any())
            ->method('get_service_proxy')
            ->willReturn($svc);

        /** @noinspection PhpUndefinedMethodInspection */
        $this->assertSame($expected_result, $auth->get_imis_id());
        $this->getDebuggingMessages();
    }

    public function data_test_get_user_by_imis_id()
    {
        return [
            'matching user found' => ['imisid', 'imisid', 0, 0, 'manual', true],
            'non matching imisid user not found' => ['imisid', 'non-imisid', 0, 0, 'manual', false],
            'suspended user not found' => ['imisid', 'imisid', 1, 0, 'manual', false],
            'deleted user not found' => ['imisid', 'imisid', 0, 1, 'manual', false],
            'wrong auth not found' => ['imisid', 'imisid', 0, 0, 'not-manual', false]
        ];
    }

    /**
     * @group get_user_by_imis_id
     * @dataProvider data_test_get_user_by_imis_id
     * @param $imis_id
     * @param $user_idnumber
     * @param $user_suspended
     * @param $user_deleted
     * @param $user_auth
     * @param $expect_success
     * @throws \dml_exception
     */
    public function test_get_user_by_imis_id($imis_id, $user_idnumber, $user_suspended, $user_deleted, $user_auth, $expect_success)
    {
        $this->resetAfterTest(true);
        $gen = $this->getDataGenerator();
        $user = $gen->create_user([
            'idnumber' => $user_idnumber,
            'auth' => $user_auth,
            'suspended' => $user_suspended,
            'deleted' => $user_deleted,

        ]);
        $auth = new \auth_plugin_imisbridge();
        $founduser = $auth->get_user_by_imis_id($imis_id);
        if ($expect_success) {
            $this->assertNotNull($founduser);
            $this->assertEquals($user->id, $founduser->id);
        } else {
            $this->assertNull($founduser);
        }
    }

    /**
     *
     */
    public function test_get_userinfo()
    {
        $this->resetAfterTest(true);
//        $auth = new \auth_plugin_imisbridge();
        $this->markTestIncomplete('Under development');
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

        /** @noinspection PhpUndefinedMethodInspection */
        $auth->pre_loginpage_hook();
        $auth->loginpage_hook();
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

    public function data_test_authenticate_user_redirect_on_success()
    {
        return [
            [null, 'http://root/'],
            ['http://redirect/', 'http://redirect/']
        ];
    }

    /**
     * @dataProvider data_test_authenticate_user_redirect_on_success
     * @param $wantsurl
     * @param $expected_url
     */
    public function test_authenticate_user_redirect_on_success($wantsurl, $expected_url)
    {
        global $CFG;
        $this->resetAfterTest(true);
        $imis_id = 'id1';
        $this->getDataGenerator()->create_user(['idnumber' => $imis_id]);
        set_config('synch_profile', 0, \auth_plugin_imisbridge::COMPONENT_NAME);
        $CFG->wwwroot = 'http://root';
        if ($wantsurl) {
            $_SESSION['SESSION']->wantsurl = $wantsurl;
        }

        $auth = $this->getMockBuilder(\auth_plugin_imisbridge::class)
            ->setMethods([
                'get_imis_id',
                'complete_user_login',
                'redirect',
                'redirect_to_sso_login'
            ])
            ->getMock();
        $auth->expects($this->once())->method('get_imis_id')->willReturn($imis_id);
        $auth->expects($this->once())->method('complete_user_login');
        $auth->expects($this->never())->method('redirect_to_sso_login');
        $auth->expects($this->once())
            ->method('redirect')
            ->with($this->equalTo($expected_url));
        $auth->authenticate_user();
    }

    public function data_test_authenticate_user_sso_redirect()
    {
        return [
            'courseid defaults to 1' => [
                null,
                'imis_id',
                null,
                1,
                null
            ],
            'courseid overrides default' => [
                null,
                'imis_id',
                2,
                2,
                null
            ],
            'user not found message' => [
                'imis_id',
                'non_imis_id',
                null,
                1,
                get_string('moodle_user_not_found', 'auth_imisbridge', 'imis_id')
            ],
            'no message when no imis id' => [
                null,
                'imis_id',
                null,
                1,
                null
            ],
        ];
    }

    /**
     * @dataProvider data_test_authenticate_user_sso_redirect
     * @param $imis_id
     * @param $user_idnumber
     * @param $course_id
     * @param $expected_redirect_course_id
     * @param $expected_msg
     */
    public function test_authenticate_user_sso_redirect($imis_id, $user_idnumber, $course_id, $expected_redirect_course_id, $expected_msg)
    {
        global $CFG, $COURSE;
        $this->resetAfterTest(true);
        $this->getDataGenerator()->create_user(['idnumber' => $user_idnumber]);
        set_config('synch_profile', 0, \auth_plugin_imisbridge::COMPONENT_NAME);
        $CFG->wwwroot = 'http://root';
        $COURSE->id = $course_id;

        $auth = $this->getMockBuilder(\auth_plugin_imisbridge::class)
            ->setMethods([
                'get_imis_id',
                'complete_user_login',
                'redirect_to_sso_login'
            ])->getMock();
        $auth->expects($this->once())->method('get_imis_id')->willReturn($imis_id);
        $auth->expects($this->never())->method('complete_user_login');
        $auth->expects($this->once())->method('redirect_to_sso_login')
            ->with(
                $this->equalTo($expected_redirect_course_id),
                $this->equalTo($expected_msg)
            );

        $auth->authenticate_user();
    }

    public function data_test_authenticate_user_synch_profile()
    {
        return [
            'synch enabled' => [true],
            'synch disabled' => [false]
        ];
    }

    /**
     * @dataProvider data_test_authenticate_user_synch_profile
     * @param $synch_enabled
     */
    public function test_authenticate_user_synch_profile($synch_enabled)
    {
        global $CFG;

        $this->resetAfterTest(true);
        $CFG->wwwroot = 'http://abc.com';

        $gen = $this->getDataGenerator();
        $user = $gen->create_user([
            'idnumber' => 'imisid',
            'auth' => 'manual'
        ]);
        set_config('synch_profile', $synch_enabled ? '1' : '0', auth_plugin_imisbridge::COMPONENT_NAME);

        $auth = $this->getMockBuilder(\auth_plugin_imisbridge::class)
            ->setMethods([
                'get_imis_id',
                'synch_user_record',
                'complete_user_login',
                'redirect'
            ])->getMock();
        $auth->expects($this->once())->method('get_imis_id')->willReturn('imisid');
        $auth->expects($this->once())->method('complete_user_login');
        $auth->expects($this->exactly($synch_enabled ? 1 : 0))->method('synch_user_record');

        /** @noinspection PhpUndefinedMethodInspection */
        $auth->authenticate_user();
    }

    public function data_test_redirect_to_sso_login()
    {
        return [
            'url courseid msg' => ['http://sso/', 2, 'msg', true],
            'no msg' => ['http://sso/', 2, null, true],
            'no url' => [null, 2, 'msg', false],

        ];
    }

    /**
     * @dataProvider data_test_redirect_to_sso_login
     * @param $sso_login_url
     * @param $courseid
     * @param $msg
     * @param $expect_redirect
     * @throws \moodle_exception
     */
    public function test_redirect_to_sso_login($sso_login_url, $courseid, $msg, $expect_redirect)
    {
        $this->resetAfterTest(true);
        set_config('sso_login_url', $sso_login_url, auth_plugin_imisbridge::COMPONENT_NAME);
        $auth = $this->getMockBuilder(\auth_plugin_imisbridge::class)
            ->setMethods(['redirect'])->getMock();

        if ($expect_redirect) {
            $auth->expects($this->once())->method('redirect')
                ->with(
                    $this->equalTo((new \moodle_url($sso_login_url, ['id' => $courseid]))->out()),
                    $this->equalTo($msg)
                );
        } else {
            $auth->expects($this->never())->method('redirect');
        }
        /** @noinspection PhpUndefinedMethodInspection */
        $result = $auth->redirect_to_sso_login($courseid, $msg);
        if (!$expect_redirect) {
            $this->assertFalse($result);
        }
    }
}
