<?php
/*
// retrieves statistics that shows up on dashboard page
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

                //customers
                $customers = $db->SelectOne("SELECT COUNT(*) AS total FROM customers WHERE user_id = :uid", ['uid' => $user_id]);

                //requests
                $requests = $db->SelectOne("SELECT COUNT(*) AS total FROM requests WHERE is_completed =  :res AND user_id = :uid", [
                    "uid" => $user_id,
                    "res" => "No" 
                ]);

                //measurements
                $measurements = $db->SelectOne("SELECT COUNT(*) AS total FROM measurements WHERE user_id = :uid", [
                    "uid" => $user_id
                ]);

                //configured measurements
                $meas_upd = $db->SelectOne("SELECT * FROM config WHERE (tape_male IS NOT NULL AND tape_female IS NOT NULL) AND user_id = :uid", [
                    "uid" => $user_id
                ]);

                $stats = array(
                    "profile_updated" => (checkUpdatedProfile($user)) ? true : false,
                    "measurement_updated" => (!empty($meas_upd)) ? true : false,
                    "total_customers" => intval($customers['total']),
                    "total_requests" => intval($requests['total']),
                    "total_measurements" => intval($measurements['total'])
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