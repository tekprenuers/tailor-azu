<?php

require '../../core/functions.php';
cors();
//use octavalidate
use Validate\octaValidate;

//create new instance
$myForm = new octaValidate('form_get_customer', OV_OPTIONS);
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
                    doReturn(401, false, ["message" => "Your subscription has expired", "expired" => true]);

                //check if customer exists
                $customer = $db->SelectOne("SELECT *, measurements.date_updated AS tape_last_updated FROM customers LEFT JOIN measurements ON measurements.cus_id = customers.cus_id AND measurements.user_id = customers.user_id WHERE customers.user_id = :uid AND customers.cus_id = :cid", ['uid' => $user_id, 'cid' => $_GET['cus_id']]);

                if(!$customer){
                    doReturn(400, false, ["message" => "Customer does not exist"]);
                }

                //hide private vars
                unset($customer['user_id']);
                unset($customer['id']);
                //check if user is premium and customer has an image profile link
                if (!empty($customer['image'])) {
                    $customer['image'] = BACKEND_URL . PUBLIC_PROFILE_DIR . $customer['image'];
                } else {
                    unset($customer['image']);
                }
                $customer['lname'] = explode(' ', $customer['name'])[0];
                $customer['fname'] = explode(' ', $customer['name'])[1];
                
                $customer['date_added'] = gmdate('D M d, Y', $customer['date_added']);

                //check if tape_male exists then decode
                (!empty($customer['tape_male'])) ? $customer['tape_male'] = json_decode($customer['tape_male']) : null;
                //do thesame for tape_female
                (!empty($customer['tape_female'])) ? $customer['tape_female'] = json_decode($customer['tape_female']) : null;
                // /2021-11-05 15:00:00
                $customer['tape_last_updated_jsformatted'] = gmdate('Y-M-d H:m:s', $customer['tape_last_updated']);
                //reassign last updated
                $customer['tape_last_updated'] = gmdate('D M d, Y', $customer['tape_last_updated']);
                //get requests
                $requests = $db->SelectAll("SELECT requests.name, requests.price, requests.extra_note, requests.due_date, requests.is_completed FROM requests WHERE user_id = :uid AND cus_id = :cid", ['uid' => $user_id, 'cid' => $_GET['cus_id']]);
                //append to variable
                $customer['requests'] = $requests;

                //return data
                doReturn(200, true, $customer);
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