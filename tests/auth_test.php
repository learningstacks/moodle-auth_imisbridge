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

    public function setUp()
    {
        $_COOKIE = [];
    }

    public function test_basics()
    {
        $this->resetAfterTest(true);
        $auth = new test_subject();
        $this->assertFalse($auth->can_reset_password());
        $this->assertFalse($auth->can_change_password());
        $this->assertFalse($auth->is_internal());
        $this->assertSame('', $auth->change_password_url());
        $this->assertFalse($auth->is_synchronised_with_external());
    }

    /**
     * @group get_user_by_imis_id
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
        $auth = new test_subject();
        $this->assertNull($auth->get_user_by_imis_id('u2'));
        $this->assertNotNull($auth->get_user_by_imis_id('u1'));
    }

    /**
     * @group get_user_by_imis_id
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
        $auth = new test_subject();
        $this->assertNull($auth->get_user_by_imis_id('u1'));
    }

    /**
     * @group get_user_by_imis_id
     */
    public function test_get_user_by_imis_id_user_suspended()
    {
        $this->resetAfterTest(true);
        $gen = $this->getDataGenerator();
        $gen->create_user([
            'username' => 'u1',
            'idnumber' => 'u1',
            'auth' => 'manual',
            'suspended' => 1
        ]);
        $auth = new test_subject();
        $this->assertNull($auth->get_user_by_imis_id('u1'));
    }

    /**
     * @group get_user_by_imis_id
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
        $auth = new test_subject();
        $this->assertNull($auth->get_user_by_imis_id('u1'));
    }

    // get_config
    public function test_get_config_defaults()
    {
        global $DB;

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

    // get_sso_cookie
    public function test_get_sso_cookie_found()
    {
        $this->resetAfterTest(true);
        $_COOKIE['cookiename'] = 'abc';
        set_config('sso_cookie_name', 'cookiename', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_path', '/cookiepath', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_domain', 'cookiedomain.com', auth_plugin_imisbridge::COMPONENT_NAME);

        $auth = new test_subject();

        $this->assertSame('abc', $auth->get_sso_cookie());
    }

    public function test_get_sso_cookie_not_found()
    {
        $this->resetAfterTest(true);
        $_COOKIE['badname'] = 'abc';
        set_config('sso_cookie_name', 'cookiename', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_path', '/cookiepath', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_domain', 'cookiedomain.com', auth_plugin_imisbridge::COMPONENT_NAME);

        $auth = new test_subject();

        $this->assertNull($auth->get_sso_cookie());
    }

    public function test_get_sso_cookie_name_not_set()
    {
        $this->resetAfterTest(true);
        $auth = new test_subject();
        $this->assertNull($auth->get_sso_cookie());
    }

    // get_imis_id
    public function test_get_imis_id_unencrypted()
    {
        $this->resetAfterTest(true);
        $auth = $this->getMockBuilder(test_subject::class)
            ->setMethods(['get_sso_cookie', 'decrypt'])
            ->getMock();
        $auth->expects($this->once())->method('get_sso_cookie')->willReturn('imis_id');
        $auth->expects($this->never())->method('decrypt');
        $this->assertSame('imis_id', $auth->get_sso_cookie());
    }

    public function test_get_imis_id_encrypted()
    {
        $this->resetAfterTest(true);

        $svc = $this->createMock(iServiceProxy::class);
        $svc->expects($this->once())
            ->method('decrypt')
            ->willReturn('unencrypted_imis_id');

        set_config('sso_cookie_is_encrypted', '1', auth_plugin_imisbridge::COMPONENT_NAME);

        $auth = $this->getMockBuilder(test_subject::class)
            ->setMethods(['get_sso_cookie', 'get_service_proxy'])
            ->getMock();
        $auth->expects($this->once())
            ->method('get_sso_cookie')
            ->willReturn('encrypted_imis_id');
        $auth->expects($this->once())
            ->method('get_service_proxy')
            ->willReturn($svc);

        $val = $auth->get_imis_id();
        $this->assertSame('unencrypted_imis_id', $val);
    }

    public function test_get_imis_id_no_cookie()
    {
        $this->resetAfterTest(true);
        set_config('sso_cookie_is_encrypted', '1', auth_plugin_imisbridge::COMPONENT_NAME);

        $auth = $this->getMockBuilder(test_subject::class)
            ->setMethods(['get_sso_cookie', 'decrypt'])
            ->getMock();
        $auth->expects($this->once())
            ->method('get_sso_cookie')
            ->willReturn(null);
        $auth->expects($this->never())
            ->method('decrypt');

        $val = $auth->get_imis_id();
        $this->assertNull($val);
    }

    public function test_get_imis_id_decryption_fails()
    {
        $this->resetAfterTest(true);

        $svc = $this->createMock(iServiceProxy::class);
        $svc->expects($this->once())
            ->method('decrypt')
            ->will($this->throwException(new \Exception));

        set_config('sso_cookie_is_encrypted', '1', auth_plugin_imisbridge::COMPONENT_NAME);

        $auth = $this->getMockBuilder(test_subject::class)
            ->setMethods(['get_sso_cookie', 'get_service_proxy'])
            ->getMock();
        $auth->expects($this->once())
            ->method('get_sso_cookie')
            ->willReturn('encrypted_imis_id');
        $auth->expects($this->once())
            ->method('get_service_proxy')
            ->willReturn($svc);

        $val = $auth->get_imis_id();
        $this->assertNull($val);
    }

    // expire_sso_cookie
//    public function test_expire_sso_cookie_all_valid_config()
//    {
//        $this->resetAfterTest(true);
//        set_config('sso_cookie_name', 'abc', auth_plugin_imisbridge::COMPONENT_NAME);
//        set_config('sso_cookie_path', '/', auth_plugin_imisbridge::COMPONENT_NAME);
//        set_config('sso_cookie_domain', 'abc.com', auth_plugin_imisbridge::COMPONENT_NAME);
//        $auth = new test_subject();
////        $auth->expire_sso_cookie();
//        $this->markTestIncomplete();
//    }
//
//    public function test_expire_sso_cookie_invalid_config()
//    {
//        $this->resetAfterTest(true);
//        set_config('sso_cookie_name', '', auth_plugin_imisbridge::COMPONENT_NAME);
//        set_config('sso_cookie_path', '', auth_plugin_imisbridge::COMPONENT_NAME);
//        set_config('sso_cookie_domain', '', auth_plugin_imisbridge::COMPONENT_NAME);
//        $auth = new test_subject();
////        $auth->expire_sso_cookie();
//        $this->markTestIncomplete();
//    }


    // update_user_profile
    public function test_get_userinfo()
    {
        $this->resetAfterTest(true);
        $auth = new test_subject();
        $this->markTestIncomplete('Under development');
    }

    // redirect_to_sso_login
//    public function test_redirect_to_sso_login()
//    {
//        $this->resetAfterTest(true);
//
//        set_config('sso_login_url', 'val_sso_logoin_url', auth_plugin_imisbridge::COMPONENT_NAME);
//
//        $auth = $this->getMockBuilder(test_subject::class)
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

        $auth = $this->getMockBuilder(test_subject::class)
            ->setMethods(['expire_sso_cookie'])
            ->getMock();
        $auth->expects($this->once())->method('expire_sso_cookie');

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

        $auth = $this->getMockBuilder(test_subject::class)
            ->setMethods(['expire_sso_cookie'])
            ->getMock();
        $auth->expects($this->never())->method('expire_sso_cookie');

        $auth->logoutpage_hook();
        $this->assertEquals('val_logouturl', $redirect);
    }

    /**
     * @group login_page_hook
     */
    public function test_login_page_hook_nosso()
    {
        $this->resetAfterTest(true);
        $_GET['nosso'] = 1;

        $auth = $this->getMockBuilder(test_subject::class)
            ->setMethods([
                'get_imis_id',
                'redirect_to_sso_login'
            ])
            ->getMock();

        $auth->expects($this->never())->method('get_imis_id');
        $auth->expects($this->never())->method('redirect_to_sso_login');

        $this->assertFalse($auth->loginpage_hook());
    }

    /**
     * @group login_page_hook
     */
    public function test_login_page_hook_no_cookie()
    {
        $this->resetAfterTest(true);

//        set_config('sso_logout_url', 'val_logouturl', auth_plugin_imisbridge::COMPONENT_NAME);
//        set_config('sso_cookie_remove_on_logout', '0', auth_plugin_imisbridge::COMPONENT_NAME);
        $auth = $this->getMockBuilder(test_subject::class)
            ->setMethods([
                'get_user_by_imis_id',
                'redirect_to_sso_login',
                'redirect'
            ])
            ->getMock();
        $auth->expects($this->never())->method('get_user_by_imis_id');
        $auth->expects($this->never())->method('redirect');
        $auth->expects($this->once())->method('redirect_to_sso_login');

        $this->assertFalse($auth->loginpage_hook());
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

        $auth = $this->getMockBuilder(test_subject::class)
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

        $auth->loginpage_hook();
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

        $auth = $this->getMockBuilder(test_subject::class)
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

        $auth->loginpage_hook();
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

        $auth = $this->getMockBuilder(test_subject::class)
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

        $auth->loginpage_hook();
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

        $auth = $this->getMockBuilder(test_subject::class)
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

        $auth->loginpage_hook();
    }

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

        $auth = $this->getMockBuilder(test_subject::class)
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

        $auth->loginpage_hook();
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

        $auth = $this->getMockBuilder(test_subject::class)
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
        $auth->expects($this->once())->method('redirect')->with($this->equalTo($expected_redirect));

        $auth->loginpage_hook();

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

        $auth = $this->getMockBuilder(test_subject::class)
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

        $auth->loginpage_hook();
    }

}
