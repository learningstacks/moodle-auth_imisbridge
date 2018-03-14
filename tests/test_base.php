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

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__.'/../auth.php');

interface iServiceProxy
{
    public function decrypt($encrypted_text);

    public function moodle_update($data);
}

class test_subject extends \auth_plugin_imisbridge
{
    public function get_config()
    {
        return parent::get_config();
    }

    public function get_imis_id()
    {
        return parent::get_imis_id();
    }
}

/**
 * Test class for adhoc tasks.
 *
 * @package auth_imisbridge
 * @category auth
 * @copyright 2017 Learning Stacks LLC
 * @license   All Rights Reserved
 */
abstract class test_base extends \advanced_testcase
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


}
