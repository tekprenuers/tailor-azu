<?php

require '../../core/functions.php';
cors();
//use octavalidate
use Validate\octaValidate;

//create new instance
$myForm = new octaValidate('form_get_measurement', OV_OPTIONS);
//define rules for each form input name
$valRules = array(
    "token" => array(
        ["R", "A token is required"]
    ),
    "cus_id" => array(
        ["R", "Customer's ID is required!"],
        ["ALPHA_NUMERIC", "Customer ID must contain letters or numbers"]
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
                    doReturn(401, false, ["message" => "Your subscription has expired"]);

                //get measurement
                $measurement = $db->SelectOne("SELECT * FROM customers LEFT JOIN measurements ON (measurements.cus_id = customers.cus_id) AND customers.user_id = :uid", [
                    'uid' => $user_id
                ]);

                //hide private vars
                unset($measurement['user_id']);
                unset($measurement['id']);
                //check if user is premium and customer has an image profile link
                if ($measurement['image']) {
                    $measurement['image'] = BACKEND_URL . PUBLIC_PROFILE_DIR . $measurement['image'];
                } else {
                    unset($measurement['image']);
                }
                $measurement['lname'] = explode(' ', $measurement['name'])[0];
                $measurement['fname'] = explode(' ', $measurement['name'])[1];
                
                $measurement['date_added'] = gmdate('d-m-Y', $measurement['date_added']);

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