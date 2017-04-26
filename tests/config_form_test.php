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
class config_form_testcase extends test_base
{

    public function setUp()
    {
        $_COOKIE = [];
    }

    public function test_basics()
    {
        $this->resetAfterTest(true);
        $this->markTestIncomplete();
    }

}
