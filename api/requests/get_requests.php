<?php
/*
// returns requests related to a customer using the customer's ID
*/
require '../../core/functions.php';
cors();
//use octavalidate
use Validate\octaValidate;

//create new instance
$myForm = new octaValidate('', OV_OPTIONS);
//build custom rule
$myForm->customRule("SEARCH", "/^[a-zA-Z0-9+@. ]+$/", "Search query contains invalid characters");
//define rules for each form input name
$valRules = array(
    "token" => array(
        ["R", "A token is required"]
    ),
    "cus_id" => array(
        ["R", "Customer's ID is required!"],
        ["ALPHA_NUMERIC", "Customer ID must contain letters or numbers"]
    ),
    "search" => array(
        ["SEARCH"]
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
                $customer = $db->SelectOne("SELECT * FROM customers WHERE user_id = :uid AND cus_id = :cid", ['uid' => $user_id, 'cid' => $_GET['cus_id']]);

                if(!$customer){
                    doReturn(400, false, ["message" => "Customer does not exist"]);
                }

                unset($customer['user_id']);
                unset($customer['id']);
                //get requests
                $requests = $db->SelectAll("SELECT * FROM requests WHERE user_id = :uid AND cus_id = :cid", ['uid' => $user_id, 'cid' => $_GET['cus_id']]);

                //SEARCH QUERY
                if (isset($_GET['search']) && !empty($_GET['search'])) {
                    $search = $_GET['search'];
                    $requests = $db->SelectAll("SELECT * FROM requests WHERE user_id = :uid AND (name LIKE  CONCAT('%', :s, '%') OR extra_note LIKE CONCAT('%', :s, '%'))", ['uid' => $user_id, 's' => $search]);
                }

                foreach ($requests as $key => $data) {
                    //hide private vars
                    unset($requests[$key]['user_id']);
                    unset($requests[$key]['id']);
                    //check if image is not empty
                    if($requests[$key]['image']){
                        $requests[$key]['image'] = BACKEND_URL . REQUESTS_DIR  . $requests[$key]['image'];
                    }
                    //format date
                    $requests[$key]['deadline'] = gmdate('d-m-Y', $requests[$key]['deadline']);
                }

                doReturn(200, true, ['requests' => $requests, 'customer' => $customer]);
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