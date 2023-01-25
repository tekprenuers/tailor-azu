<?php
/*
// retrieves statistics that shows up on dashboard page
*/
require '../../core/functions.php';
cors();
//use octavalidate
use Validate\octaValidate;

//create new instance
$myForm = new octaValidate('', OV_OPTIONS);
//define rules for each form input name
$valRules = array(
    "token" => array(
        ["R", "A token is required"]
    )
);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        if ($myForm->validateFields($valRules, $_GET) === true) {
            $user_id = verifyToken($_GET['token']);
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

                $stats = array(
                    "total_customers" => $customers['total'],
                    "total_requests" => $requests['total'],
                    "total_measurements" => $measurements['total']
                );

                doReturn(200, true, $stats);
            }
        } else {
            //return errors  
            doReturn(400, false, ["formError" => $myForm->getErrors()]);
        }
    } catch (Exception $e) {
        error_log($e);
        doReturn(500, false, ["message" => "A server error has occured"]);
    }
} else {
    doReturn(400, false, ["message" => "Invalid request method"]);
}
?>