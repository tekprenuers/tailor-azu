<?php
require 'core/functions.php';
cors();
//use octavalidate
use Validate\octaValidate;

//create new instance
$myForm = new octaValidate('form_login', OV_OPTIONS);
//define rules for each form input name
$valRules = array(
    "pass" => array(
        ["R", "Your password is required"],
        ["MINLENGTH", 8, "Your password must have a minimum of 8 characters"]
    ),
    "email" => array(
        ["R", "Your Email Address is required"],
        ["EMAIL", "Your Email Address is invalid!"]
    )
);
//Check if it is a post request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        //begin validation    
        if ($myForm->validateFields($valRules, $_POST) === true) {
    
            //check if email is registered already
            $user = $db->SelectOne("SELECT * FROM users WHERE email = :email", ['email' => $_POST['email']]);

            if (!$user) {
                doReturn(401, false, ["message" => "Email address does not exist"]);
            }

            //compare password
            if (password_verify($_POST['pass'], $user['pass']) === false) {
                doReturn(401, false, ["message" => "You have provided an Invalid password"]);
            } else {
                //user_id, expiry_time, user is premium
                $loggedInToken = base64_encode($user['user_id']).'::'.strtotime("+24 hours", time()).'::'.base64_encode($user['is_premium']);
                //return success
                doReturn(200, true, ["message" => "Login successful", "token" => $loggedInToken]);
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