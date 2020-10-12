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

$string['redirecturllabel'] = 'Add redirect URL';
$string['redirecturldesc'] = 'Adds redirect URL at the end of the return URL <br />
<i>eg. https://yourdomain.com/login?redirect=http://yourmoodle.com/course</i>';

$string['authmetode'] = 'Authentication Method';
$string['authmetodelabel'] = 'Authentication methods';
$string['authmetodedesc'] = 'If selected, SSO will log in only users with selected authentication method';

// errors
$string['plugindisabled'] = 'IMIS Bridge SSO Plugin is disabled, please re-enable';

$string['unabletoauth'] = 'Unable to Authenticate User';
$string['userdoesnotexist'] = 'User does not exist';
$string['error401'] = 'ERROR 401';
$string['loginthroughthemainwebsite'] = 'Please login through the Main Website';

$string['synch_profile_label'] = 'Update profile on login?';
$string['synch_profile_desc'] = "If set to <em>Yes</em>, the users Moodle profile will be updated from IMIS after each successful login.";
$string['synch_profile_err'] = "A value is required.";
$string['moodle_user_not_found'] = 'The Moodle user with imis_id = $a was not found';

$string['create_user_label'] = 'Create new Moodle user account for authenticated IMIS user?';
$string['create_user_desc'] = "If set to <em>Yes</em>, when a user is authenticated in IMIS but a Moodle account does not yet exist, create a new Moodle user account.";

$string['data_mapping_desc'] = "<p>To specify that a User Profile field is to be updated from IMIS:</p>
<ol>
<li>Enter the name of the field as provided by the IMIS Bridge MoodleGetUserProfile web service function.</li>
<li>Set <em>Update Local</em>em> to <em>On Every Login.</li>
<li>Leave <em>Update External</em> set to <em>Never</em>.</li>
<li>Set <em>Lock Value</em> to <em>Unlocked</em>. (Note: If you set this to <em>Unlocked if empty</em>, it will be updated only once. Once a value has been entered no more updates will occur.)</li>
</ol>";

$string['no_moodle_account'] = '<h1>No active Moodle account for IMIS user {$a}.</h1>
<p>A Moodle account must be created before the LMS can be accessed, or the option to Create New Moodle Users must be set</p>';

$string['no_imis_account'] = '<h1>No active IMIS account.</h1>
<p>The user does not exist in IMIS./<p>';

$string['no_token'] = '<h1>User is not logged into IMIS.</h1>
<p>Either an account does not exists or it is suspended or deleted./<p>
<p>A Moodle account must be created before the LMS can be accessed, or the option to Create New Moodle Users must be set</p>';

$string['deleted_user'] = '<h1>User account is marked as deleted for user {$a}.</h1>
<p>A Moodle account exists for this user but is marked as deleted. Please contact support to resolve this issue.</p>';

$string['suspended_user'] = '<h1>User account is marked as suspended for user {$a}.</h1>
<p>A Moodle account exists for this user but is marked as deleted. Please contact support to resolve this issue.</p>';

$string['privacy:metadata'] = 'The IMISBRIDGE (SSO) authentication plugin does not store any personal data.';
