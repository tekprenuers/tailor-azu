<?php
/*
// returns the measurement data of a customer
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
    ),
    "cus_id" => array(
        ["R", "Customer ID is required"],
        ["ALPHA_NUMERIC", "Customer ID must contain alphabets or numbers"]
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

                $measurement = $db->SelectOne("SELECT measurements.tape_male, measurements.tape_female, measurements.date_updated, customers.name AS cus_name, customers.gender FROM measurements RIGHT JOIN customers ON customers.cus_id = measurements.cus_id WHERE customers.user_id = :uid AND customers.cus_id = :cid", ['uid' => $user_id, 'cid' => $_GET['cus_id']]);

                if (!$measurement) {
                    doReturn(400, false, ["message" => "This Customer does not existt"]);
                }

                (!empty($measurement['date_updated'])) ? $measurement['date_updated'] = gmdate('d-m-Y', $measurement['date_updated']) : null;

                //decode json and reassign
                if($measurement['tape_male']){
                    $measurement['tape_male'] = json_decode($measurement['tape_male']);
                }
                //decode json and reassign
                if($measurement['tape_female']){
                    $measurement['tape_female'] = json_decode($measurement['tape_female']);
                }

                doReturn(200, true, $measurement);
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