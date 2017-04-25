<?php

global $OUTPUT;

// The variable $config is automatically retrieved from the config table by moodle

$ssonames = array(
    'sso_login_url',
    'sso_logout_url',
    'sso_cookie_name',
    'sso_cookie_path',
    'sso_cookie_domain',
    'sso_cookie_remove_on_logout',
    'sso_cookie_is_encrypted'
);

// Make sure all items have a value
foreach ($ssonames as $ssoname) {
    if (!isset ($config->$ssoname)) {
        $config->$ssoname = '';
    }
}

?>

<style>
    input[type="text"] {
        width: 95% !important;
    }
</style>

<table cellspacing="0" cellpadding="5" border="0">

    <tr>
        <td>
            <h4><?php print_string('ssoinformation', 'auth_imisbridge') ?></h4>
        </td>
    </tr>

    <tr valign="top" class="required">
        <td align="right"><label
                for="sso_login_url"><?php print_string('sso_login_url_label', 'auth_imisbridge') ?></label>:
        </td>
        <td>
            <input name="sso_login_url" type="text" size="30" value="<?php echo $config->sso_login_url ?>"/><br/>
            <?php if (isset($err['sso_login_url'])) {
                echo $OUTPUT->error_text($err['sso_login_url']);
            } ?>
        </td>
        <td>
            <?php print_string('sso_login_url_desc', 'auth_imisbridge') ?>
        </td>
    </tr>

    <tr valign="top" class="required">
        <td align="right">
            <label for="sso_logout_url"><?php print_string('sso_logout_url_label', 'auth_imisbridge') ?></label>:
        </td>
        <td>
            <input name="sso_logout_url" type="text" size="30" value="<?php echo $config->sso_logout_url ?>"/><br/>
            <?php if (isset($err['sso_logout_url'])) {
                echo $OUTPUT->error_text($err['sso_logout_url']);
            } ?>
        </td>
        <td>
            <?php print_string('sso_logout_url_desc', 'auth_imisbridge') ?>
        </td>
    </tr>


    <tr>
        <td/>
    </tr>
    <tr>
        <td>
            <h4><?php print_string('cookieinformation', 'auth_imisbridge') ?></h4>
        </td>
    </tr>

    <tr valign="top" class="required">
        <td align="right">
            <label for="sso_cookie_name"><?php print_string('sso_cookie_name_label', 'auth_imisbridge') ?></label>:
        </td>
        <td>
            <input name="sso_cookie_name" type="text" size="30" value="<?php echo $config->sso_cookie_name ?>"/><br/>
        </td>
        <td>
            <?php print_string('sso_cookie_name_desc', 'auth_imisbridge') ?>
        </td>
    </tr>

    <tr valign="top" class="required">
        <td align="right">
            <label for="sso_cookie_path"><?php print_string('sso_cookie_path_label', 'auth_imisbridge') ?></label>:
        </td>
        <td>
            <input name="sso_cookie_path" type="text" size="30" value="<?php echo $config->sso_cookie_path ?>"/><br/>
        </td>
        <td>
            <?php print_string('sso_cookie_path_desc', 'auth_imisbridge') ?>
        </td>
    </tr>

    <tr valign="top" class="required">
        <td align="right">
            <label for="sso_cookie_domain"><?php print_string('sso_cookie_domain_label', 'auth_imisbridge') ?></label>:
        </td>
        <td>
            <input name="sso_cookie_domain" type="text" size="30"
                   value="<?php echo $config->sso_cookie_domain ?>"/><br/>
        </td>
        <td>
            <?php print_string('sso_cookie_domain_desc', 'auth_imisbridge') ?>
        </td>
    </tr>

    <tr valign="top" class="required">
        <td align="right">
            <label
                for="sso_cookie_remove_on_logout"><?php print_string('sso_cookie_remove_on_logout_label', 'auth_imisbridge') ?></label>:
        </td>
        <td>
            <?php echo html_writer::select_yes_no('sso_cookie_remove_on_logout', $config->sso_cookie_remove_on_logout === '1'); ?>
        </td>
        <td>
            <?php print_string('sso_cookie_remove_on_logout_desc', 'auth_imisbridge') ?>
        </td>
    </tr>

    <tr valign="top" class="required">
        <td align="right">
            <label
                for="sso_cookie_is_encrypted"><?php print_string('sso_cookie_remove_is_encrypted_label', 'auth_imisbridge') ?></label>:
        </td>
        <td>
            <?php echo html_writer::select_yes_no('sso_cookie_is_encrypted', $config->sso_cookie_is_encrypted === '1'); ?>
        </td>
        <td>
            <?php print_string('sso_cookie_remove_is_encrypted_desc', 'auth_imisbridge') ?>
        </td>
    </tr>

    <?php print_auth_lock_options($this->authtype, $user_fields, get_string('auth_fieldlocks_help', 'auth'), false, false); ?>

</table>