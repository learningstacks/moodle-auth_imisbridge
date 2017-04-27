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

$string['pluginname'] = 'IMIS Bridge SSO Authentication';
$string['auth_imisbridgetitle'] = "IMIS Bridge Authentication Plugin";
$string['auth_imisbridgedescription'] = "Enables Single Sign On with IMIS Bridge";

$string['ssoinformation'] = 'SSO Information';
$string['ssoheading'] = 'SSO information';

$string['sso_login_url_label'] = 'SSO Login URL';
$string['sso_login_url_desc'] = 'The web page URL where SSO users will login when needed<br />
<i>eg. http://yourdomain.com/login</i>';

$string['sso_logout_url_label'] = 'SSO Logout URL';
$string['sso_logout_url_desc'] = 'The web page URL where SSO users will be directed when logging out<br />
<i>eg. http://yourdomain.com/logout</i>';

$string['redirecturllabel'] = 'Add redirect URL';
$string['redirecturldesc'] = 'Adds redirect URL at the end of the return URL <br />
<i>eg. http://yourdomain.com/login?redirect=http://yourmoodle.com/course</i>';

$string['cookieinformation'] = 'Cookie Information';


$string['sso_cookie_name_label'] = 'SSO Cookie name';
$string['sso_cookie_name_desc'] = "The name of the cookie provided by the SSO system</i>.<br /> 
<i>eg. USER</i>";

$string['sso_cookie_domain_label'] = 'SSO Cookie domain';
$string['sso_cookie_domain_desc'] = 'The domain of the cookie issued by the IMIS Bridge<br />
<i>eg. .yourdomain.com</i>';

$string['sso_cookie_path_label'] = 'SSO Cookie path';
$string['sso_cookie_path_desc'] = 'The path of the cookie issued by the IMIS Bridge<br />
<i>eg. /</i>';

$string['sso_cookie_remove_on_logout_label'] = 'Remove cookie on log out?';
$string['sso_cookie_remove_on_logout_desc'] = "Should the SSO cookie be removed when the user logs out?";

$string['sso_cookie_remove_is_encrypted_label'] = 'Is the cookie encrypted?';
$string['sso_cookie_remove_is_encrypted_desc'] = "If checked, the cookie must be decrypted.";


$string['authmetode'] = 'Authentication Method';

$string['authmetodelabel'] = 'Authentication methods';
$string['authmetodedesc'] = 'If selected, SSO will log in only users with selected authentication method';

// errors
$string['plugindisabled'] = 'IMIS Bridge SSO Plugin is disabled, please re-enable';

$string['unabletoauth'] = 'Unable to Authenticate User';
$string['userdoesnotexist'] = 'User does not exist';
$string['error401'] = 'ERROR 401';
$string['loginthroughthemainwebsite'] = 'Please login through the Main Website';

$string['sso_login_url_is_required'] = 'A valid URL is required';
$string['sso_logout_url_is_required'] = 'A valid URL is required';
$string['sso_cookie_name_is_required'] = 'A cookie name is required';
$string['sso_cookie_path_is_required'] = 'A valid path is required';
$string['sso_cookie_domain_is_required'] = 'A valid domain is required';
$string['sso_cookie_remove_on_logout_is_required'] = 'A value is required';
$string['sso_cookie_is_encrypted_is_required'] = 'A value is required';

$string['data_mapping_desc'] = "<p>To specify that a User Profile field is to be updated from IMIS:</p>
<ol>
<li>Enter the name of the field as provided by the getContacvt web service function.</li>
<li>Set <em>Update Local</em>em> to <em>On Every Login.</li>
<li>Leave <em>Update External</em> set to <em>Never</em>.</li>
<li>Set <em>Lock Value</em> to <em>Unlocked</em>. (Note: If you set this to <em>Unlocked if empty</em>, it will be updated only once. Once a value has been entered no more updates will occur.)</li>
</ol>";