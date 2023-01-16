<?php

require '../../core/functions.php';
cors();
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
                //check if license is active
                if(!activeLicense($user['expiry'])) doReturn(401, false, ["message" => "Your subscription has expired"]);

                //check if customer exists
                $customer = $db->SelectOne("SELECT * FROM customers WHERE cus_id = :cid AND user_id = :uid", ['cid' => $_POST['cus_id'], 'uid' => $user_id]);
                if (!$customer) {
                    doReturn(400, false, ["message" => "This customer does not exist"]);
                }

                //delete customer
                $db->Remove("DELETE FROM customers WHERE id = :id", ['id' => $customer['id']]);
                //delete profile if exists
                if($customer['image']){
                    if(file_exists(PROFILE_DIR.$customer['image'])){
                        unlink(PROFILE_DIR.$customer['image']);
                    }
                }

                //check for requests
                $requests = $db->SelectAll("SELECT * FROM requests WHERE cus_id = :cid AND user_id = :uid", ['cid' => $customer['cus_id'], 'uid' => $user_id]);
                //delete requests
                if(!empty($requests)){
                    $db->Remove("DELETE FROM requests WHERE cus_id = :cid AND user_id = :uid", ['cid' => $customer['cus_id'], 'uid' => $user_id]);
                    //loop through requests
                    foreach($requests as $key => $req){
                        //delete request image if it exists
                        if($requests[$key]['image']){
                            if(file_exists(PROFILE_DIR.$requests[$key]['image'])){
                                unlink(PROFILE_DIR.$requests[$key]['image']);
                            }
                        }
                    }
                }
                
                //remaining measurements
                
                doReturn(200, true, ["message" => "Customer data has been deleted"]);
            }
        } else {
            //return errors  
            doReturn(400, false, ["formError" => $myForm->getErrors()]);
        }
    } catch (Exception $e) {
        error_log($e);
        doReturn(500, false, ["message" => "A server error has occured"]);
    }
}else{
    doReturn(400, false, ["message" => "Invalid request method"]);
}
?>