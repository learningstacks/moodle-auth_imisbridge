<?php

function send_response($code, $contentType, $body) {
    header("content-type: $contentType", true, $code);
    echo $body;
    die;
}

function send_error(string $body) {
    send_response(500, 'text/plain; charset=utf-8', $body);
}

function send_xml(string $body) {
    send_response(200, 'text/xml; charset=utf-8', $body);
}

function send_profile(string $imisid)
{
    if (in_array($imisid, ['active_user', 'suspended_user', 'deleted_user', 'new_user'])) {
        $xml = file_get_contents(__DIR__ . '/profile.xml');
    } else {
        $xml = file_get_contents(__DIR__ . '/profile_missing.xml');
    }
    send_xml(str_replace('{{imisid}}', $imisid, $xml));
}

$path = $_SERVER["PATH_INFO"];
if ($path == "/MoodleGetUserProfileByToken") {
    // The token is never encrypted during test
    if (empty($_REQUEST["token"])) {
        send_error("Missing parameter: encryptedText.");
    } else {
        send_profile($_REQUEST["token"]);
    }
} elseif ($path == "/MoodleGetUserProfile") {
    if (empty($_REQUEST["ID"])) {
        send_error("Missing parameter: token.");
    } else {
        send_profile($_REQUEST["ID"]);
    }
} elseif ($path == "/asiDecrypt") {
    if (empty($_REQUEST["encryptedText"])) {
        send_error("Missing parameter: ID.");
    } else {
        $decryptedText = $encryptedText = $_REQUEST["encryptedText"];
        if ($encryptedText == "invalid_token") {
            send_error("Invalid token");
        }
        else {
            $body = '<?xml version="1.0" encoding="utf-8"?><string xmlns="http://www.atsol.org/wsMoodle/">' . $decryptedText . '</string>';
            send_xml($body);
        }
    }
} else {
    $fn = str_replace("/", "", $path);
    send_error("$fn Web Service method name is not valid.");
}


/*

content-type: text/xml; charset=utf-8

encrypt activeUser
 <?xml version="1.0" encoding="utf-8"?>
<string xmlns="http://www.atsol.org/wsMoodle/">823F592B9660BEFD9AA5FC0CBFCD4123</string>

decrypt
<?xml version="1.0" encoding="utf-8"?>
<string xmlns="http://www.atsol.org/wsMoodle/">activeUser</string>

decrypt error
content-type: text/plain; charset=utf-8
status: 500

MoodleGetUserProfile
valid
status:200

Not found
status: 200


 */
