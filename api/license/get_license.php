<?php

require '../../core/functions.php';
cors();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $user_id = verifyJWT();
        $user = $db->SelectOne("SELECT * FROM users WHERE user_id  = :uid", ['uid' => $user_id]);
        if (!$user) {
            doReturn(401, false, ["message" => "Please login to continue"]);
        } else {
            $retval = array(
                "next_renewal" => gmdate("d M, Y", intval($user['expiry'])),
                "expired" => false
            );
            if (time() >= intval($user['expiry'])) {
                $retval['expired'] = true;
                unset($retval['next_renewal']);
            }
            doReturn(200, true, $retval);
        }
    } catch (Exception $e) {
        error_log($e);
        doReturn(500, false, ["message" => "A server error has occured"]);
    }
} else {
    doReturn(400, false, ["message" => "Invalid request method"]);
}
?>