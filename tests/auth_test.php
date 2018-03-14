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
class auth_imisbridge_testcase extends test_base {
    /**
     *
     */
    public function setUp() {
        global $CFG;
        $_COOKIE = [];
        $CFG->debugdeveloper = false;
        $CFG->debug = 0;
        set_config('synch_profile', '0', auth_plugin_imisbridge::COMPONENT_NAME);
    }

    /**
     * @throws \dml_exception
     */
    public function test_basics() {
        $this->resetAfterTest(true);
        $auth = new test_subject();
        $this->assertFalse($auth->can_reset_password());
        $this->assertFalse($auth->can_change_password());
        $this->assertFalse($auth->is_internal());
        $this->assertSame('', $auth->change_password_url());
        $this->assertFalse($auth->is_synchronised_with_external());
    }


    /**
     * @throws \dml_exception
     */
    public function test_get_user_by_imis_id() {
        $this->resetAfterTest(true);
        $gen = $this->getDataGenerator();
        $gen->create_user([
            'username' => 'u1',
            'idnumber' => 'other', // Ensure looking up by username
            'auth' => 'manual'
        ]);
        $auth = new test_subject();
        $this->assertNull($auth->get_user_by_imis_id('u2'));
        $this->assertNotNull($auth->get_user_by_imis_id('u1'));
    }


    /**
     * @throws \dml_exception
     */
    public function test_get_user_by_imis_id_different_auth() {
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
     * @throws \dml_exception
     */
    public function test_get_user_by_imis_id_user_suspended() {
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
     * @throws \dml_exception
     */
    public function test_get_user_by_imis_id_user_deleted() {
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

    /**
     * @throws \dml_exception
     */
    public function test_get_sso_cookie_found() {
        $this->resetAfterTest(true);
        $_COOKIE['cookiename'] = 'abc';
        set_config('sso_cookie_name', 'cookiename', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_path', '/cookiepath', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_domain', 'cookiedomain.com', auth_plugin_imisbridge::COMPONENT_NAME);

        $auth = new test_subject();

        $this->assertSame('abc', $auth->get_sso_cookie());
    }

    /**
     * @throws \dml_exception
     */
    public function test_get_sso_cookie_not_found() {
        $this->resetAfterTest(true);
        $_COOKIE['badname'] = 'abc';
        set_config('sso_cookie_name', 'cookiename', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_path', '/cookiepath', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_domain', 'cookiedomain.com', auth_plugin_imisbridge::COMPONENT_NAME);

        $auth = new test_subject();

        $this->assertNull($auth->get_sso_cookie());
    }

    /**
     * @throws \dml_exception
     */
    public function test_get_sso_cookie_name_not_set() {
        $this->resetAfterTest(true);
        $auth = new test_subject();
        $this->assertNull($auth->get_sso_cookie());
    }

    // get_imis_id

    /**
     *
     */
    public function test_get_imis_id_unencrypted() {
        $this->resetAfterTest(true);
        $auth = $this->getMockBuilder(test_subject::class)
            ->setMethods(['get_sso_cookie', 'decrypt'])
            ->getMock();
        $auth->expects($this->once())->method('get_sso_cookie')->willReturn('imis_id');
        $auth->expects($this->never())->method('decrypt');
        $this->assertSame('imis_id', $auth->get_sso_cookie());
    }

    /**
     *
     */
    public function test_get_imis_id_encrypted() {
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
        $this->getDebuggingMessages();
    }

    /**
     *
     */
    public function test_get_imis_id_no_cookie() {
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

    /**
     *
     */
    public function test_get_imis_id_decryption_fails() {
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
        $this->getDebuggingMessages();
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
    /**
     *
     */
    public function test_get_userinfo() {
        $this->resetAfterTest(true);
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
    public function test_logout_page_hook_remove_cookie() {
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
    public function test_logout_page_hook_no_remove_cookie() {
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
    public function test_login_page_hook_nosso() {
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

        $this->assertFalse($auth->pre_loginpage_hook());
    }

    /**
     * @group login_page_hook
     */
    public function test_login_page_hook_no_cookie() {
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

        $this->assertFalse($auth->pre_loginpage_hook());
    }

    /**
     * @group login_page_hook
     */
    public function test_login_page_hook_unencrypted_cookie() {
        global $CFG;

        $CFG->wwwroot = 'http://abc.com';

        $this->resetAfterTest(true);

        $imisid = 'id123';

        $gen = $this->getDataGenerator();
        $gen->create_user([
            'username' => $imisid,
            'idnumber' => $imisid,
            'auth' => 'manual'
        ]);
        $_COOKIE['cookiename'] = $imisid;

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
                $this->callback(function ($user) use ($imisid) {
                    return ($user->username === $imisid);
                })
            );
        $auth->expects($this->once())->method('redirect')->with('http://abc.com/');

        $auth->pre_loginpage_hook();
    }

    /**
     * @group login_page_hook
     */
    public function test_login_page_hook_synch_profile() {
        global $CFG;

        $CFG->wwwroot = 'http://abc.com';

        $this->resetAfterTest(true);

        $imisid = 'id123';

        $gen = $this->getDataGenerator();
        $gen->create_user([
            'username' => $imisid,
            'idnumber' => $imisid,
            'auth' => 'manual'
        ]);
        $_COOKIE['cookiename'] = $imisid;

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
                $this->callback(function ($user) use ($imisid) {
                    return ($user->username === $imisid);
                })
            );
        $auth->expects($this->once())->method('redirect')->with('http://abc.com/');

        $auth->pre_loginpage_hook();
    }

    /**
     * @group login_page_hook
     */
    public function test_login_page_hook_unencrypted_cookie_with_wantsurl() {
        global $CFG;
        $this->resetAfterTest(true);

        $imisid = 'id123';

        $CFG->wwwroot = 'http://abc.com';
        $_SESSION['SESSION']->wantsurl = "http://wantsurl.com/";
        $_COOKIE['cookiename'] = $imisid;

        $imisid = 'id123';

        $gen = $this->getDataGenerator();
        $gen->create_user([
            'username' => $imisid,
            'idnumber' => $imisid,
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
                $this->callback(function ($user) use ($imisid) {
                    return ($user->username == $imisid);
                })
            );
        $auth->expects($this->once())->method('redirect')->with('http://wantsurl.com/');

        $auth->pre_loginpage_hook();
    }


    /**
     * @group login_page_hook
     */
    public function test_login_page_hook_encrypted_cookie() {
        global $CFG;

        $CFG->wwwroot = 'http://abc.com';

        $this->resetAfterTest(true);

        $imisid = 'id123';

        $gen = $this->getDataGenerator();
        $gen->create_user([
            'username' => $imisid,
            'idnumber' => $imisid,
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
            ->willReturn($imisid);
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
                $this->callback(function ($user) use ($imisid) {
                    return ($user->username === $imisid);
                })
            );
        $auth->expects($this->once())->method('redirect')->with('http://abc.com/');

        $auth->pre_loginpage_hook();
    }

    /**
     *
     */
    public function test_login_page_hook_encrypted_token() {
        global $CFG;

        $CFG->wwwroot = 'http://abc.com';

        $this->resetAfterTest(true);

        $imisid = 'id123';

        $gen = $this->getDataGenerator();
        $gen->create_user([
            'username' => $imisid,
            'idnumber' => $imisid,
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
            ->willReturn($imisid);
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
                $this->callback(function ($user) use ($imisid) {
                    return ($user->username === $imisid);
                })
            );
        $auth->expects($this->once())->method('redirect')->with('http://abc.com/');

        $auth->pre_loginpage_hook();
    }

    /**
     * @group login_page_hook
     */
    public function test_login_page_hook_user_not_found() {
        global $CFG, $DB;
        $this->resetAfterTest(true);

        $imisid = 'id123';

        $this->set_field_configs([
            ['firstname', 'FirstName', 'unlocked', 'onlogin'],
            ['lastname', 'LastName', 'unlocked', 'onlogin'],
            ['email', 'EmailAddress', 'unlocked', 'onlogin']
        ]);
        $contact_info = [
            'CustomerType' => 'CustomerType',
            'CustomerTypeCode' => 'CustomerTypeCode',
            'IsMember' => true,
            'FirstName' => 'fname_1',
            'Informal' => 'informal',
            'FullName' => 'fullname_1',
            'LastName' => 'lastname_1',
            'EmailAddress' => 'email@nowhere.com',
            'fld1' => 'fld1_val'
        ];

        $CFG->wwwroot = 'http://abc.com';
        $_SESSION['SESSION']->wantsurl = "http://wantsurl.com/";
        $_COOKIE['cookiename'] = $imisid;

        set_config('sso_cookie_name', 'cookiename', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_is_encrypted', '0', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_login_url', 'val_login_url', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('synch_profile', '0', auth_plugin_imisbridge::COMPONENT_NAME);

        $svcmock = $this->getMockBuilder(service_proxy::class)
            ->setMethods([
                'get_contact_info'
            ])
            ->getMock();
        $svcmock->expects($this->once())
            ->method('get_contact_info')
            ->willReturn($contact_info);

        $authmock = $this->getMockBuilder(test_subject::class)
            ->setMethods([
                'redirect_to_sso_login',
                'redirect',
                'get_service_proxy',
                'decrypt',
                'complete_user_login'
            ])
            ->getMock();
        $authmock->expects($this->never())->method('redirect_to_sso_login');
        $authmock->expects($this->never())->method('decrypt');
        $authmock->expects($this->once())
            ->method('get_service_proxy')
            ->willReturn($svcmock);
        $authmock->expects($this->once())
            ->method('complete_user_login')
            ->with(
                $this->callback(function ($user) use ($imisid) {
                    return ($user->username == $imisid);
                })
            );
        $authmock->expects($this->once())->method('redirect')->with('http://wantsurl.com/');

        $authmock->pre_loginpage_hook();

        $newuser = get_complete_user_data('username', $imisid);
        $this->assertEquals($contact_info['FirstName'], $newuser->firstname);
        $this->assertEquals($contact_info['LastName'], $newuser->lastname);
        $this->assertEquals($contact_info['EmailAddress'], $newuser->email);
    }

    /**
     * No redirect to SSO happens when the SSO URL is not set
     *
     * @group login_page_hook
     *
     */
    public function test_login_page_hook_no_sso_login_url() {
        global $CFG;
        $this->resetAfterTest(true);

        $CFG->wwwroot = 'http://abc.com';
        $_SESSION['SESSION']->wantsurl = "http://wantsurl.com/";
//        $_COOKIE['cookiename'] = 'unecrypted_id';

        set_config('sso_cookie_name', 'cookiename', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('sso_cookie_is_encrypted', '0', auth_plugin_imisbridge::COMPONENT_NAME);
        set_config('synch_profile', '0', auth_plugin_imisbridge::COMPONENT_NAME);

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
        $this->expectException(\moodle_exception::class);

        $auth->pre_loginpage_hook();
    }

}
