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
            //check if license is active
            if (!activeLicense($user['expiry']))
                doReturn(401, false, ["message" => "Your subscription has expired", "expired" => true]);

            //get measurement
            $config = $db->SelectOne("SELECT tape_male, tape_female FROM config WHERE user_id = :uid", [
                'uid' => $user_id
            ]);

            //if data exists
            if ($config) {
                //hide private vars
                unset($config['user_id']);
                unset($config['id']);
                //decode and reassign
                $config['tape_male'] = json_decode($config['tape_male']);
                $config['tape_female'] = json_decode($config['tape_female']);
            }

            doReturn(200, true, $config);
        }
    } catch (Exception $e) {
        error_log($e);
        doReturn(500, false, ["message" => "A server error has occured"]);
    }
} else {
    doReturn(400, false, ["message" => "Invalid request method"]);
}
?>