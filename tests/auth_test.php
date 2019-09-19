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
     * @group get_user_by_imis_id
     * @throws \dml_exception
     * @throws \dml_exception
     */
    public function test_get_user_by_imis_id()
    {
        $this->resetAfterTest(true);
        $gen = $this->getDataGenerator();
        $gen->create_user([
            'username' => 'u1',
            'idnumber' => 'u1',
            'auth' => 'manual'
        ]);
        $auth = new \auth_plugin_imisbridge();
        $this->assertNull($auth->get_user_by_imis_id('u2'));
        $this->assertNotNull($auth->get_user_by_imis_id('u1'));
    }

    /**
     * @group get_user_by_imis_id
     * @throws \dml_exception
     */
    public function test_get_user_by_imis_id_different_auth()
    {
        $this->resetAfterTest(true);
        $gen = $this->getDataGenerator();
        $gen->create_user([
            'username' => 'u1',
            'idnumber' => 'u1',
            'auth' => 'other',
            'suspended' => 1
        ]);
        $auth = new \auth_plugin_imisbridge();
        $this->assertNull($auth->get_user_by_imis_id('u1'));
    }

    /**
     * @group get_user_by_imis_id
     * @throws \dml_exception
     */
    public function test_get_user_by_imis_id_user_suspended_returns_null()
    {
        $this->resetAfterTest(true);
        $gen = $this->getDataGenerator();
        $gen->create_user([
            'username' => 'u1',
            'idnumber' => 'u1',
            'auth' => 'manual',
            'suspended' => 1
        ]);
        $auth = new \auth_plugin_imisbridge();
        $this->assertNull($auth->get_user_by_imis_id('u1'));
    }

    /**
     * @group get_user_by_imis_id
     * @throws \dml_exception
     */
    public function test_get_user_by_imis_id_user_deleted()
    {
        $this->resetAfterTest(true);
        $gen = $this->getDataGenerator();
        $gen->create_user([
            'username' => 'u1',
            'idnumber' => 'u1',
            'auth' => 'manual',
            'deleted' => 1
        ]);
        $auth = new \auth_plugin_imisbridge();
        $this->assertNull($auth->get_user_by_imis_id('u1'));
    }

    // get_config

    /**
     * @throws \dml_exception
     */
    public function test_get_config_defaults()
    {
        global $DB;

        $this->resetAfterTest(true);
        $auth = new \auth_plugin_imisbridge();
        $this->assertEquals('', $auth->config->sso_login_url);
        $this->assertEquals('', $auth->config->sso_logout_url);
        $this->assertEquals('MoodleSSO', $auth->config->sso_cookie_name);
        $this->assertEquals('/', $auth->config->sso_cookie_path);
        $this->assertEquals('', $auth->config->sso_cookie_domain);
        $this->assertEquals('1', $auth->config->sso_cookie_remove_on_logout);
        $this->assertEquals('1', $auth->config->sso_cookie_is_encrypted);
        $this->assertEquals('1', $auth->config->synch_profile);
    }

    /**
     * @throws \dml_exception
     */
    public function test_get_config_values()
    {
        global $DB;

        $this->resetAfterTest(true);
        set_config('sso_login_url', 'sso_login_url', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_logout_url', 'sso_logout_url', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_name', 'sso_cookie_name', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_path', 'sso_cookie_path', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_domain', 'sso_cookie_domain', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_remove_on_logout', '0', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_is_encrypted', '0', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('synch_profile', '0', auth_plugin_imisbridge::COMPONENT_NAME);

        $auth = new \auth_plugin_imisbridge();

        $this->assertSame('sso_login_url', $auth->config->sso_login_url);
        $this->assertSame('sso_logout_url', $auth->config->sso_logout_url);
        $this->assertSame('sso_cookie_name', $auth->config->sso_cookie_name);
        $this->assertSame('sso_cookie_path', $auth->config->sso_cookie_path);
        $this->assertSame('sso_cookie_domain', $auth->config->sso_cookie_domain);
        $this->assertSame('0', $auth->config->sso_cookie_remove_on_logout);
        $this->assertSame('0', $auth->config->sso_cookie_is_encrypted);
        $this->assertSame('0', $auth->config->synch_profile);
    }

    /**
     * @return array
     */
    public function data_test_get_imis_id()
    {
        return [
            'Unencrypted cookie' => [
                'method' => 'cookie',
                'encrypted' => '0',
                'in' => 'unencrypted_cookie',
                'out' => 'unencrypted_cookie',
                'decrypt_throws' => false
            ],
            'Unencrypted token' => [
                'method' => 'token',
                'encrypted' => '0',
                'in' => 'unencrypted_token',
                'out' => 'unencrypted_token',
                'decrypt_throws' => false
            ],
            'Encrypted cookie' => [
                'method' => 'cookie',
                'encrypted' => '1',
                'in' => 'encrypted_cookie',
                'out' => 'unencrypted_encrypted_cookie',
                'decrypt_throws' => false
            ],
            'Encrypted token' => [
                'method' => 'token',
                'encrypted' => '1',
                'in' => 'unencrypted_token',
                'out' => 'unencrypted_encrypted_token',
                'decrypt_throws' => false
            ],
            'No token or cookie' => [
                'method' => null,
                'encrypted' => '0',
                'in' => null,
                'out' => null,
                'decrypt_throws' => false
            ],
            'Decrypt fails' => [
                'method' => 'token',
                'encrypted' => '1',
                'in' => 'encrypted_token',
                'out' => null,
                'decrypt_throws' => true
            ],
        ];
    }

    /**
     * @dataProvider data_test_get_imis_id
     * @param string"null $method 'token', 'cookie' or null
     * @param bool $encrypted if true configure for encrypted toke/cookie
     * @param string|null $in The incoming (possible encrypted) value
     * @param string|null $out The expected result
     * @param bool $decrypt_throws Simulate a decryption error if true
     */
    public function test_get_imis_id($method, $encrypted, $in, $out, $decrypt_throws)
    {
        global $_COOKIE, $_GET;

        $this->resetAfterTest(true);

        set_config('sso_cookie_is_encrypted', $encrypted ? '1' : '0', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_path', '/', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_name', 'cookiename', auth_plugin_imisbridge::COMPONENT_NAME);

        if ($method == 'token') {
            unset($_COOKIE['cookiename']);
            $_GET['token'] = $in;
        } elseif ($method == 'cookie') {
            unset($_GET['token']);
            $_COOKIE['cookiename'] = $in;
        } else {
            unset($_GET['token']);
            unset($_COOKIE['cookiename']);
        }

        $svc = $this->createMock(iServiceProxy::class);
        if (!$encrypted) {
            $svc->expects($this->never())->method('decrypt');
        } elseif ($decrypt_throws) {
            $svc->expects($this->once())
                ->method('decrypt')
                ->will($this->throwException(new \Exception));
        } else {
            $svc->expects($this->once())
                ->method('decrypt')
                ->willReturn($out);
        }

        $auth = $this->getMockBuilder(\auth_plugin_imisbridge::class)
            ->setMethods(['get_service_proxy'])
            ->getMock();
        $auth->expects($this->any())
            ->method('get_service_proxy')
            ->willReturn($svc);

        /** @noinspection PhpUndefinedMethodInspection */
        $this->assertSame($out, $auth->get_imis_id());
        $this->getDebuggingMessages();
    }

    // update_user_profile

    /**
     * @throws \dml_exception
     */
    public function test_get_userinfo()
    {
        $this->resetAfterTest(true);
        $auth = new \auth_plugin_imisbridge();
        $this->markTestIncomplete('Under development');
    }

    // redirect_to_sso_login
//    public function test_redirect_to_sso_login()
//    {
//        $this->resetAfterTest(true);
//
//        set_config('sso_login_url', 'val_sso_logoin_url', auth_plugin_imisbridge::COMPONENT_NAME);
//
//        $auth = $this->getMockBuilder(\auth_plugin_imisbridge::class)
//            ->setMethods(['redirect'])
//            ->getMock();
//
//        $auth->expects($this->once())
//            ->method('redirect')
//            ->with($this->equalTo('val_sso_login_url'));
//
//        $auth->redirect_to_sso_login();
//    }

    // logout_page_hook
    /**
     * @group logout_page_hook
     */
    public function test_logout_page_hook_remove_cookie()
    {
        $this->resetAfterTest(true);

        global $redirect;

        set_config('sso_logout_url', 'val_logouturl', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_remove_on_logout', '1', auth_plugin_imisbridge::COMPONENT_NAME);

        $auth = $this->getMockBuilder(\auth_plugin_imisbridge::class)
            ->setMethods(['expire_sso_cookie'])
            ->getMock();
        $auth->expects($this->once())->method('expire_sso_cookie');

        /** @noinspection PhpUndefinedMethodInspection */
        $auth->logoutpage_hook();
        $this->assertEquals('val_logouturl', $redirect);
    }

    /**
     * @group logout_page_hook
     */
    public function test_logout_page_hook_no_remove_cookie()
    {
        $this->resetAfterTest(true);

        global $redirect;

        set_config('sso_logout_url', 'val_logouturl', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_remove_on_logout', '0', auth_plugin_imisbridge::COMPONENT_NAME);

        $auth = $this->getMockBuilder(\auth_plugin_imisbridge::class)
            ->setMethods(['expire_sso_cookie'])
            ->getMock();
        $auth->expects($this->never())->method('expire_sso_cookie');

        /** @noinspection PhpUndefinedMethodInspection */
        $auth->logoutpage_hook();
        $this->assertEquals('val_logouturl', $redirect);
    }

    /**
     * @group login_page_hook
     */
    public function test_pre_loginpage_hook_nosso()
    {
        $this->resetAfterTest(true);
        $_GET['nosso'] = 1;

        $auth = $this->getMockBuilder(\auth_plugin_imisbridge::class)
            ->setMethods([
                'get_imis_id',
                'redirect_to_sso_login'
            ])
            ->getMock();

        $auth->expects($this->never())->method('get_imis_id');
        $auth->expects($this->never())->method('redirect_to_sso_login');

        /** @noinspection PhpUndefinedMethodInspection */
        $this->assertFalse($auth->pre_loginpage_hook());
    }

    /**
     * @group login_page_hook
     */
    public function test_login_page_hook_no_cookie()
    {
        $this->resetAfterTest(true);

//        set_config('sso_logout_url', 'val_logouturl', auth_plugin_imisbridge::COMPONENT_NAME);
//        set_config('sso_cookie_remove_on_logout', '0', auth_plugin_imisbridge::COMPONENT_NAME);
        $auth = $this->getMockBuilder(\auth_plugin_imisbridge::class)
            ->setMethods([
                'get_user_by_imis_id',
                'redirect_to_sso_login',
                'redirect'
            ])
            ->getMock();
        $auth->expects($this->never())->method('get_user_by_imis_id');
        $auth->expects($this->never())->method('redirect');
        $auth->expects($this->once())->method('redirect_to_sso_login');

        /** @noinspection PhpUndefinedMethodInspection */
        $this->assertFalse($auth->pre_loginpage_hook());
    }

    /**
     * @group login_page_hook
     */
    public function test_login_page_hook_unencrypted_cookie()
    {
        global $CFG;

        $CFG->wwwroot = 'http://abc.com';

        $this->resetAfterTest(true);

        $gen = $this->getDataGenerator();
        $user = $gen->create_user([
            'username' => 'user1_username',
            'idnumber' => 'id123',
            'auth' => 'manual'
        ]);
        $_COOKIE['cookiename'] = 'id123';

        set_config('sso_cookie_name', 'cookiename', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_is_encrypted', '0', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('synch_profile', '0', auth_plugin_imisbridge::COMPONENT_NAME);

        $auth = $this->getMockBuilder(\auth_plugin_imisbridge::class)
            ->setMethods([
                'redirect_to_sso_login',
                'redirect',
                'get_service_proxy',
                'decrypt',
                'complete_user_login',
                'get_contact_info'
            ])
            ->getMock();
        $auth->expects($this->never())->method('redirect_to_sso_login');
        $auth->expects($this->once())->method('redirect');
        $auth->expects($this->never())->method('decrypt');
        $auth->expects($this->never())->method('get_service_proxy');
        $auth->expects($this->never())->method('get_contact_info')->willReturn([]);

        $auth->expects($this->once())
            ->method('complete_user_login')
            ->with(
                $this->callback(function ($user) {
                    return ($user->username === 'user1_username');
                })
            );
        $auth->expects($this->once())->method('redirect')->with('http://abc.com/');

        /** @noinspection PhpUndefinedMethodInspection */
        $auth->pre_loginpage_hook();
    }

    /**
     * @group login_page_hook
     */
    public function test_login_page_hook_synch_profile()
    {
        global $CFG;

        $CFG->wwwroot = 'http://abc.com';

        $this->resetAfterTest(true);

        $gen = $this->getDataGenerator();
        $user = $gen->create_user([
            'username' => 'user1_username',
            'idnumber' => 'id123',
            'auth' => 'manual'
        ]);
        $_COOKIE['cookiename'] = 'id123';

        set_config('sso_cookie_name', 'cookiename', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_is_encrypted', '0', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('synch_profile', '1', auth_plugin_imisbridge::COMPONENT_NAME);

        $auth = $this->getMockBuilder(\auth_plugin_imisbridge::class)
            ->setMethods([
                'redirect_to_sso_login',
                'redirect',
                'get_service_proxy',
                'decrypt',
                'complete_user_login',
                'get_contact_info'
            ])
            ->getMock();
        $auth->expects($this->never())->method('redirect_to_sso_login');
        $auth->expects($this->once())->method('redirect');
        $auth->expects($this->never())->method('decrypt');
        $auth->expects($this->never())->method('get_service_proxy');
        $auth->expects($this->once())->method('get_contact_info')->willReturn([]);

        $auth->expects($this->once())
            ->method('complete_user_login')
            ->with(
                $this->callback(function ($user) {
                    return ($user->username === 'user1_username');
                })
            );
        $auth->expects($this->once())->method('redirect')->with('http://abc.com/');

        /** @noinspection PhpUndefinedMethodInspection */
        $auth->pre_loginpage_hook();
    }

    /**
     * @group login_page_hook
     */
    public function test_login_page_hook_unencrypted_cookie_with_wantsurl()
    {
        global $CFG;
        $this->resetAfterTest(true);

        $CFG->wwwroot = 'http://abc.com';
        $_SESSION['SESSION']->wantsurl = "http://wantsurl.com/";
        $_COOKIE['cookiename'] = 'id123';

        $gen = $this->getDataGenerator();
        $user = $gen->create_user([
            'username' => 'user1_username',
            'idnumber' => 'id123',
            'auth' => 'manual'
        ]);

        set_config('sso_cookie_name', 'cookiename', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_is_encrypted', '0', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('synch_profile', '0', auth_plugin_imisbridge::COMPONENT_NAME);

        $auth = $this->getMockBuilder(\auth_plugin_imisbridge::class)
            ->setMethods([
                'redirect_to_sso_login',
                'redirect',
                'get_service_proxy',
                'decrypt',
                'complete_user_login',
                'get_contact_info'
            ])
            ->getMock();
        $auth->expects($this->never())->method('redirect_to_sso_login');
        $auth->expects($this->never())->method('decrypt');
        $auth->expects($this->never())->method('get_service_proxy');
        $auth->expects($this->never())->method('get_contact_info')->willReturn([]);
        $auth->expects($this->once())
            ->method('complete_user_login')
            ->with(
                $this->callback(function ($user) {
                    return ($user->username == 'user1_username');
                })
            );
        $auth->expects($this->once())->method('redirect')->with('http://wantsurl.com/');

        /** @noinspection PhpUndefinedMethodInspection */
        $auth->pre_loginpage_hook();
    }


    /**
     * @group login_page_hook
     */
    public function test_login_page_hook_encrypted_cookie()
    {
        global $CFG;

        $CFG->wwwroot = 'http://abc.com';

        $this->resetAfterTest(true);

        $gen = $this->getDataGenerator();
        $user = $gen->create_user([
            'username' => 'user1_username',
            'idnumber' => 'unencrypted_id',
            'auth' => 'manual'
        ]);

        $_COOKIE['cookiename'] = 'encrypted_id';

        set_config('sso_cookie_name', 'cookiename', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_is_encrypted', '1', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('synch_profile', '0', auth_plugin_imisbridge::COMPONENT_NAME);

        $svc = $this->getMockBuilder(service_proxy::class)
            ->setMethods([
                'decrypt',
                'get_contact_info'
            ])
            ->getMock();;
        $svc->expects($this->once())
            ->method('decrypt')
            ->with($this->equalTo('encrypted_id'))
            ->willReturn('unencrypted_id');
        $svc->expects($this->never())
            ->method('get_contact_info')
            ->willReturn([]);

        $auth = $this->getMockBuilder(\auth_plugin_imisbridge::class)
            ->setMethods([
                'redirect_to_sso_login',
                'redirect',
                'get_service_proxy',
                'decrypt',
                'complete_user_login'
            ])
            ->getMock();
        $auth->expects($this->never())->method('redirect_to_sso_login');
        $auth->expects($this->any())->method('get_service_proxy')->willReturn($svc);
        $auth->expects($this->once())
            ->method('complete_user_login')
            ->with(
                $this->callback(function ($user) {
                    return ($user->username === 'user1_username');
                })
            );
        $auth->expects($this->once())->method('redirect')->with('http://abc.com/');

        /** @noinspection PhpUndefinedMethodInspection */
        $auth->pre_loginpage_hook();
    }

    /**
     *
     */
    public function test_login_page_hook_encrypted_token()
    {
        global $CFG;

        $CFG->wwwroot = 'http://abc.com';

        $this->resetAfterTest(true);

        $gen = $this->getDataGenerator();
        $user = $gen->create_user([
            'username' => 'user1_username',
            'idnumber' => 'unencrypted_id',
            'auth' => 'manual'
        ]);

        set_config('sso_cookie_is_encrypted', '1', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('synch_profile', '0', auth_plugin_imisbridge::COMPONENT_NAME);

        $_GET['token'] = 'encrypted_id';

        $svc = $this->getMockBuilder(service_proxy::class)
            ->setMethods([
                'decrypt',
                'get_contact_info'
            ])
            ->getMock();;
        $svc->expects($this->once())
            ->method('decrypt')
            ->with($this->equalTo('encrypted_id'))
            ->willReturn('unencrypted_id');
        $svc->expects($this->never())
            ->method('get_contact_info')
            ->willReturn([]);

        $auth = $this->getMockBuilder(\auth_plugin_imisbridge::class)
            ->setMethods([
                'redirect_to_sso_login',
                'redirect',
                'get_service_proxy',
                'decrypt',
                'complete_user_login'
            ])
            ->getMock();
        $auth->expects($this->never())->method('redirect_to_sso_login');
        $auth->expects($this->any())->method('get_service_proxy')->willReturn($svc);
        $auth->expects($this->once())
            ->method('complete_user_login')
            ->with(
                $this->callback(function ($user) {
                    return ($user->username === 'user1_username');
                })
            );
        $auth->expects($this->once())->method('redirect')->with('http://abc.com/');

        /** @noinspection PhpUndefinedMethodInspection */
        $auth->pre_loginpage_hook();
    }

    /**
     * @group login_page_hook
     */
    public function test_login_page_hook_user_not_found()
    {
        global $CFG;
        $this->resetAfterTest(true);

        $CFG->wwwroot = 'http://abc.com';
        $_SESSION['SESSION']->wantsurl = "http://wantsurl.com/";
        $_COOKIE['cookiename'] = 'unecrypted_id';

        $gen = $this->getDataGenerator();
        // No user

        set_config('sso_cookie_name', 'cookiename', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_is_encrypted', '0', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_login_url', 'val_login_url', auth_plugin_imisbridge::COMPONENT_NAME);

        $expected_redirect = 'val_login_url?id=1';

        $auth = $this->getMockBuilder(\auth_plugin_imisbridge::class)
            ->setMethods([
                'redirect',
                'get_service_proxy',
                'decrypt',
                'complete_user_login'
            ])
            ->getMock();
        $auth->expects($this->never())->method('get_service_proxy');
        $auth->expects($this->never())->method('decrypt');
        $auth->expects($this->never())->method('complete_user_login');
        $auth->expects($this->never())->method('redirect');
        $this->expectException(\moodle_exception::class);

        /** @noinspection PhpUndefinedMethodInspection */
        $auth->pre_loginpage_hook();

    }

    /**
     * @group login_page_hook
     *
     */
    public function test_login_page_hook_no_sso_login_url()
    {
        global $CFG;
        $this->resetAfterTest(true);

        $CFG->wwwroot = 'http://abc.com';
        $_SESSION['SESSION']->wantsurl = "http://wantsurl.com/";
        $_COOKIE['cookiename'] = 'unecrypted_id';

        $gen = $this->getDataGenerator();
        // No user

        set_config('sso_cookie_name', 'cookiename', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_is_encrypted', '0', auth_plugin_imisbridge::COMPONENT_NAME);
        // No sso_login_url

        $auth = $this->getMockBuilder(\auth_plugin_imisbridge::class)
            ->setMethods([
                'redirect',
                'get_service_proxy',
                'decrypt',
                'complete_user_login'
            ])
            ->getMock();
        $auth->expects($this->never())->method('get_service_proxy');
        $auth->expects($this->never())->method('decrypt');
        $auth->expects($this->never())->method('complete_user_login');
        $auth->expects($this->never())->method('redirect');
        $this->expectException(\moodle_exception::class);

        /** @noinspection PhpUndefinedMethodInspection */
        $auth->pre_loginpage_hook();
    }

}
