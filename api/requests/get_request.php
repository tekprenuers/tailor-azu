<?php
/*
// returns a single request
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
    "req_id" => array(
        ["R", "Request ID is required"],
        ["ALPHA_NUMERIC", "Request ID must contain alphabets or numbers"]
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

                $request = $db->SelectOne("SELECT requests.name, requests.image, requests.price, requests.extra_note, requests.due_date, requests.is_completed, customers.name AS cus_name FROM requests INNER JOIN customers ON requests.cus_id = customers.cus_id WHERE requests.user_id = :uid AND requests.req_id = :id", ['uid' => $user_id, 'id' => $_GET['req_id']]);

                if (!$request) {
                    doReturn(400, false, ["message" => "This Request does not exist"]);
                }

                $request['due_date'] = gmdate('Y-m-d', $request['due_date']);
                //check if image is not empty
                if($request['image']){
                    $request['image'] = BACKEND_URL . REQUESTS_DIR  . $request['image'];
                }
                // //hide private vars
                // unset($request['user_id']);
                // unset($request['cus_id']);

                doReturn(200, true, $request);
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