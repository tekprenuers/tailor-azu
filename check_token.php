<?php
require 'core/functions.php';
cors();
//use octavalidate
use Validate\octaValidate;

//create new instance
$myForm = new octaValidate('check_token', OV_OPTIONS);
//define rules for each form input name
$valRules = array(
    "token" => array(
        ["R", "A valid token is required"]
    )
);
//Check if it is a post request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        //begin validation    
        if ($myForm->validateFields($valRules, $_GET) === true) {
            //decode token
            $loggedInToken = explode("::", $_GET['token']);

            //check token
            if(count($loggedInToken) < 2){
                doReturn(401, false, ["message" => "Invalid token"]);
            }

            //check if user id exists
            $user = $db->SelectOne("SELECT * FROM users WHERE user_id = :id", ['id' => base64_decode($loggedInToken[0])]);

            if (!$user) {
                doReturn(401, false, ["message" => "User does not exist"]);
            }

            //check time
            if(time() > intval($loggedInToken[1])){
                doReturn(401, false, ["message" => "Invalid Token"]);
            }

            doReturn(200, true, ["message" => "Token verified"]);
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