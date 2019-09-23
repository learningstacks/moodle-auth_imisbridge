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
require_once(__DIR__.'/../auth.php');

/**
 * Interface iServiceProxy
 * @package auth_imisbridge\tests
 */
interface iServiceProxy
{
    /**
     * @param $encrypted_text
     * @return mixed
     */
    public function decrypt($encrypted_text);

    /**
     * @param $data
     * @return mixed
     */
    public function moodle_update($data);
}


/**
 * Test class for adhoc tasks.
 *
 * @package auth_imisbridge
 * @category auth
 * @copyright 2017 Learning Stacks LLC {@link https://learningstacks.com/}
 * @license   All Rights Reserved
 */
abstract class test_base extends \advanced_testcase
{

}
