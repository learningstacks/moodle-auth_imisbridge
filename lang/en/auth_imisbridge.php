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

$string['pluginname'] = 'IMIS Bridge SSO Authentication';
$string['auth_imisbridgetitle'] = "IMIS Bridge Authentication Plugin";
$string['auth_imisbridgedescription'] = "Enables Single Sign On with IMIS Bridge";

$string['ssoinformation'] = 'SSO Information';
$string['ssoheading'] = 'SSO information';

$string['sso_login_url_label'] = 'SSO Login URL';
$string['sso_login_url_desc'] = 'The web page URL where SSO users will login when needed<br />
<i>eg. https://yourdomain.com/login</i>';

$string['sso_logout_url_label'] = 'SSO Logout URL';
$string['sso_logout_url_desc'] = 'The web page URL where SSO users will be directed when logging out<br />
<i>eg. https://yourdomain.com/logout</i>';

$string['imis_home_url_label'] = 'IMIS Home URL';
$string['imis_home_url_desc'] = 'URL of the main IMIS site.<br />
<i>eg. https://imis.example.com</i>';

// errors
$string['plugindisabled'] = 'IMIS Bridge SSO Plugin is disabled, please re-enable';

$string['synch_profile_label'] = 'Update profile on login?';
$string['synch_profile_desc'] = "If set to <em>Yes</em>, the users Moodle profile will be updated from IMIS after each successful login.";
$string['synch_profile_err'] = "A value is required.";

$string['create_user_label'] = 'Create new Moodle user account for authenticated IMIS user?';
$string['create_user_desc'] = "If set to <em>Yes</em>, when a user is authenticated in IMIS but a Moodle account does not yet exist, create a new Moodle user account.";

$string['data_mapping_desc'] = "<p>To specify that a User Profile field is to be updated from IMIS:</p>
<ol>
<li>Enter the name of the field as provided by the IMIS Bridge MoodleGetUserProfile web service function.</li>
<li>Set <em>Update Local</em>em> to <em>On Every Login.</li>
<li>Leave <em>Update External</em> set to <em>Never</em>.</li>
<li>Set <em>Lock Value</em> to <em>Unlocked</em>. (Note: If you set this to <em>Unlocked if empty</em>, it will be updated only once. Once a value has been entered no more updates will occur.)</li>
</ol>";

$string['no_lms_user_title'] = 'LMS User Not Found';
$string['no_lms_user_message'] = 'LMS user {$A} not found. Please contact support to resolve this issue.';

$string['no_imis_user_title'] = 'IMIS User Not Found';
$string['no_imis_user_message'] = 'IMIS user {$a} not found. Please contact support to resolve this issue.';

$string['deleted_lms_user_title'] = 'LMS User Has Been Deleted';
$string['deleted_lms_user_message'] = 'LMS user {$a} has been deleted. Please contact support to resolve this issue.';

$string['suspended_lms_user_title'] = 'LMS User Has Been Suspended';
$string['suspended_lms_user_message'] = 'LMS User {$a} has been suspended. Please contact support to resolve this issue.';

$string['imis_home_continue_message'] = 'Pressing the Continue button will return you to the main site.';

$string['privacy:metadata'] = 'The IMISBRIDGE (SSO) authentication plugin does not store any personal data.';
