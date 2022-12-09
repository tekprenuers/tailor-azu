<?php

require 'core/functions.php';
//use octavalidate
use Validate\octaValidate;

//create new instance
$myForm = new octaValidate('form_delete_customer', OV_OPTIONS);
//define rules for each form input name
$valRules = array(
    "cus_id" => array(
        ["R", "Customer ID is required"],
        ["APLHA_NUMERIC", "Customer ID must have letters or numbers"]
    ),
    "token" => array(
        ["R", "A token is required"]
    )
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($myForm->validateFields($valRules, $_POST) === true) {
            $user_id = verifyToken($_POST['token']);
            $user = $db->SelectOne("SELECT * FROM users WHERE user_id  = :uid", ['uid' => $user_id]);
            if (!$user) {
                doReturn(401, false, ["message" => "Please login to continue"]);
            } else {
                //check if customer exists
                $customer = $db->SelectOne("SELECT * FROM customers WHERE cus_id = :cid AND user_id = :uid", ['cid' => $_POST['cus_id'], 'uid' => $user_id]);
                if (!$customer) {
                    doReturn(400, false, ["message" => "This customer does not exist"]);
                }
                //delete customer
                $db->Remove("DELETE FROM customers WHERE id = :id", ['id' => $customer['id']]);
                //delete profile if exists
                if($customer['cus_image']){
                    if(file_exists(CUSTOMER_PROFILE_DIR.$customer['cus_image'])){
                        unlink(CUSTOMER_PROFILE_DIR.$customer['cus_image']);
                    }
                }
                doReturn(200, true, ["message" => "Customer data has been deleted"]);
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