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

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2017053000;        // The current plugin version (Date: YYYYMMDDXX)
$plugin->release = '1.1.0';             // Changed redirect parameter to id
$plugin->requires  = 2016120502;        // Requires this Moodle version
$plugin->component = 'auth_imisbridge';       // Full name of the plugin (used for diagnostics)
$plugin->dependencies = array(
    'local_imisbridge' => 2017040400,
);

