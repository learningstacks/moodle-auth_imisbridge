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

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__.'/test_base.php');

/**
 * Test class for adhoc tasks.
 *
 * @package auth_imisbridge
 * @category auth
 * @copyright 2017 Learning Stacks LLC {@link https://learningstacks.com/}
 * @license   All Rights Reserved
 */
class auth_imisbridge_integration_testcase extends test_base
{
    // get_service_proxy
    /**
     *
     */
    public function test_get_service_proxy()
    {
        $this->resetAfterTest(true);
        $this->markTestIncomplete();
    }

    // decrypt

    // get_user_info

}
