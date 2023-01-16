<?php

require '../../core/functions.php';
cors();
//use octavalidate
use Validate\octaValidate;

//create new instance
$myForm = new octaValidate('', OV_OPTIONS);
//define rules for each form input name
$valRules = array(
    "cus_id" => array(
        ["R", "Customer ID is required"],
        ["APLHA_NUMERIC", "Customer ID must have letters or numbers"]
    ),
    "req_id" => array(
        ["R", "Request ID is required"],
        ["ALPHA_NUMERIC", "Request ID must contain alphabets or numbers"]
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

                //check if request exists
                $request = $db->SelectOne("SELECT * FROM requests WHERE cus_id = :cid AND user_id = :uid AND req_id = :id", ['cid' => $_POST['cus_id'], 'uid' => $user_id, 'id' => $_POST['req_id']]);
                if (!$request) {
                    doReturn(400, false, ["message" => "This Request does not exist"]);
                }
                //delete request
                $db->Remove("DELETE FROM requests WHERE id = :id", ['id' => $request['id']]);

                //delete request image if it exists
                if($request['image']){
                    if(file_exists('../../'.REQUESTS_DIR.$request['image'])){
                        unlink('../../'.REQUESTS_DIR.$request['image']);
                    }
                }
                doReturn(200, true, ["message" => "Request has been deleted"]);
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