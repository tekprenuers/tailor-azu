<?php

require '../../core/functions.php';
cors();
//use octavalidate
use Validate\octaValidate;

//create new instance
$myForm = new octaValidate('form_get_customers', OV_OPTIONS);
//build custom rule
$myForm->customRule("SEARCH", "/^[a-zA-Z0-9+@. ]+$/", "Search query contains invalid characters");
//define rules for each form input name
$valRules = array(
    "token" => array(
        ["R", "A token is required"]
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
                $customers = $db->SelectAll("SELECT * FROM customers WHERE user_id = :uid", ['uid' => $user_id]);
                if (isset($_GET['search']) && !empty($_GET['search'])) {
                    $search = $_GET['search'];
                    $customers = $db->SelectAll("SELECT * FROM customers  WHERE user_id = :uid AND (phone = :s OR alt_phone = :s OR name LIKE  CONCAT('%', :s, '%'))", ['uid' => $user_id, 's' => $search]);
                }

                //loop through data
                foreach ($customers as $key => $data) {
                    //hide private vars
                    unset($customers[$key]['user_id']);
                    unset($customers[$key]['id']);

                    //check if user is premium and customer has an image profile link
                    if ($customers[$key]['image']) {
                        $customers[$key]['image'] = BACKEND_URL . PUBLIC_PROFILE_DIR . $customers[$key]['image'];
                    } else {
                        unset($customers[$key]['image']);
                    }
                    $customers[$key]['date_added'] = gmdate('M d Y', $customers[$key]['date_added']);

                    // //requests
                    // $requests = $db->SelectAll("SELECT requests.name, requests.image, requests.price, requests.extra_note, requests.due_date, requests.is_completed FROM requests WHERE user_id = :uid AND cus_id = :cid", ['uid' => $user_id, 'cid' => $customers[$key]['cus_id']]);
                    // //append to variable
                    // $customers[$key]['requests'] = $requests;
                }

                doReturn(200, true, $customers);
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