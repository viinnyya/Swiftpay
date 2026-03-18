<?php
// this file could contain helpers for prompting/validating passcode
require_once 'auth.php';

function checkPasscodeOrRedirect($userId, $pass) {
    if (!verifyPasscode($userId, $pass)) {
        die('Invalid passcode');
    }
}
?>