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

use auth_plugin_imisbridge as auth_imisbridge;
use local_imisbridge\service_proxy;

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
class profile_sync_testcase extends test_base
{

    protected function set_field_configs($field_config)
    {
        foreach ($field_config as $item) {
            list($name, $map, $lock, $update) = $item;
            set_config("field_map_$name", $map, 'auth/imisbridge');
            set_config("field_lock_$name", $lock, 'auth/imisbridge');
            set_config("field_updatelocal_$name", $update, 'auth/imisbridge');
        }
    }

    protected function get_svc_mock($newinfo)
    {
        $svc = $this->getMockBuilder(service_proxy::class)
            ->setMethods([
                'get_contact_info'
            ])
            ->getMock();
        $svc->expects($this->once())
            ->method('get_contact_info')
            ->willReturn((array)$newinfo);

        return $svc;
    }

    protected function get_auth_mock($svc)
    {
        $auth = $this->getMockBuilder(auth_imisbridge::class)
            ->setMethods([
                'get_service_proxy'
            ])
            ->getMock();
        $auth->expects($this->any())
            ->method('get_service_proxy')
            ->willReturn($svc);

        return $auth;
    }

    public function setUp()
    {
        $_COOKIE = [];


    }

    public function test_basics()
    {
        global $DB;
        $this->resetAfterTest(true);

        $gen = $this->getDataGenerator();

        $fld1 = $DB->insert_record('user_info_field', (object)[
            'shortname' => 'fld1_shortname',
            'name' => 'fld1_name',
            'datatype' => 'text',
            'categoryid' => 1,
        ]);

        $user = $gen->create_user([
            'username' => 'abc',
            'firstname' => 'a',
            'lastname' => 'b',
            'email' => 'c.c.com',
            'idnumber' => '1',
        ]);

        $this->set_field_configs([
            ['firstname', 'FirstName', 'unlocked', 'onlogin'],
            ['lastname', 'LastName', 'unlocked', 'onlogin'],
            ['email', 'EmailAddress', 'unlocked', 'onlogin'],
            ['profile_field_fld1_shortname', 'fld1', 'unlocked', 'onlogin']
        ]);

        $newinfo = (object)[
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

        $svc = $this->get_svc_mock($newinfo);
        $auth = $this->get_auth_mock($svc);

        $auth->synch_user_record($user);

        $newuser = get_complete_user_data('id', $user->id);
        $this->assertEquals($newinfo->FirstName, $newuser->firstname);
        $this->assertEquals($newinfo->LastName, $newuser->lastname);
        $this->assertEquals($newinfo->EmailAddress, $newuser->email);
        $this->assertEquals($newinfo->fld1, $newuser->profile['fld1_shortname']);
    }

    public function test_field_locked()
    {
        global $DB;
        $this->resetAfterTest(true);
        $gen = $this->getDataGenerator();

        $origuser = $gen->create_user([
            'firstname' => 'a',
        ]);

        $this->set_field_configs([
            ['firstname', 'FirstName', 'locked', 'onlogin'],
        ]);

        $newinfo = (object)[
            'FirstName' => 'fname_1',
        ];

        $svc = $this->get_svc_mock($newinfo);
        $auth = $this->get_auth_mock($svc);

        $auth->synch_user_record($origuser);

        $newuser = get_complete_user_data('id', $origuser->id);
        $this->assertEquals($origuser->firstname, $newuser->firstname);
    }

}
