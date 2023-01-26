<?php

//
//--> This script deletes customer's measurement data
//

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
    )
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($myForm->validateFields($valRules, $_POST) === true) {
            $user_id = verifyJWT();
            $user = $db->SelectOne("SELECT * FROM users WHERE user_id  = :uid", ['uid' => $user_id]);
            if (!$user) {
                doReturn(401, false, ["message" => "Please login to continue"]);
            } else {
                //check if license is active
                if(!activeLicense($user['expiry'])) doReturn(401, false, ["message" => "Your subscription has expired", "expired" => true]);

                //check if customer exists
                $tape = $db->SelectOne("SELECT * FROM measurements WHERE cus_id = :cid AND user_id = :uid", ['cid' => $_POST['cus_id'], 'uid' => $user_id]);

                if (!$tape) {
                    doReturn(400, false, ["message" => "This measurement does not exist"]);
                }
                
                //delete customer
                $db->Remove("DELETE FROM measurements WHERE id = :id", ['id' => $tape['id']]);
                
                doReturn(200, true, ["message" => "Measurement data has been deleted"]);
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