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

/** @var array $map maps IMIS contact field names to Moodle field names.
 * Intended to be customized for each installation
 */
$map = [
    'FirstName' => 'firstname',
    'LastName' => 'lastname',
    'EmailAddress' => 'email'
];