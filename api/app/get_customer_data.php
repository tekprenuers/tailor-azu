<?php
/*
// retrieves data related to a particular customer (measurement, requests, profile)
*/
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

            $customers = $db->SelectOne("SELECT COUNT(*) AS total FROM customers WHERE user_id = :uid", ['uid' => $user_id]);
            //requests
            $requests = $db->SelectOne("SELECT COUNT(*) AS total FROM requests WHERE user_id = :uid", [
                "uid" => $user_id
            ]);
            $stats = array(
                "total_customers" => $customers['total'],
                "total_requests" => $requests['total']
            );

            doReturn(200, true, $stats);
        }
    } catch (Exception $e) {
        error_log($e);
        doReturn(500, false, ["message" => "A server error has occured"]);
    }
} else {
    doReturn(400, false, ["message" => "Invalid request method"]);
}
?>