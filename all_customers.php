<?php

require 'core/functions.php';
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
                //check if customer exists
                $customers = $db->SelectAll("SELECT * FROM customers WHERE user_id = :uid", ['uid' => $user_id]);
                //loop through data
                foreach ($customers as $key => $data) {
                    //hide private vars
                    unset($customers[$key]['user_id']);
                    unset($customers[$key]['id']);

                    //check if user is a premium user
                    if ($user['is_premium'] != "Yes") {
                        unset($customers[$key]['cus_image']);
                        unset($customers[$key]['cus_phone']);
                        unset($customers[$key]['cus_alt_phone']);
                        unset($customers[$key]['requirement']);
                        unset($customers[$key]['due_date']);
                    }
                    //check if user is premium and customer has an image profile link
                    if ($user['is_premium'] == "Yes") {
                        if($customers[$key]['cus_image']){
                            $customers[$key]['profile'] = BACKEND_URL . PUBLIC_CUSTOMER_PROFILE_DIR . $customers[$key]['cus_image'];
                        }else{
                            unset($customers[$key]['cus_image']);
                        }
                    }
                }
                doReturn(200, true, ["data" => $customers]);
            }
        } else {
            http_response_code(400);
            //return errors  
            $retval = array(
                "success" => false,
                "formError" => $myForm->getErrors()
            );
            print_r(json_encode($retval));
            exit();
        }
    } catch (Exception $e) {
        error_log($e);
        http_response_code(500);
        //return errors  
        $retval = array(
            "success" => false,
            "message" => "A server error has occured"
        );
        print_r(json_encode($retval));
        exit();
    }
}else{
    doReturn(400, false, ["message" => "Invalid request method"]);
}
?>