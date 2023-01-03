<?php
require 'core/functions.php';
cors();
//use octavalidate
use Validate\octaValidate;

//create new instance
$myForm = new octaValidate('form_reset', OV_OPTIONS);
//define rules for each form input name
$valRules = array(
    "email" => array(
        ["R", "Your Email Address is required"],
        ["EMAIL", "Your Email Address is invalid!"]
    )
);
//Check if it is a post request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['hash']) && !empty($_GET['hash'])) {
    try {
        $hash = strtolower(trim(urldecode($_GET['hash'])));
        $email = strtolower(trim(urldecode($_GET['email'])));
        //begin validation    
        if ($myForm->validateFields($valRules, $_GET) === true) {
    
            //check if email is registered already
            $user = $db->SelectOne("SELECT * FROM users WHERE email = :email", ['email' => $email]);

            if (!$user) {
                doReturn(400, false, ["message" => "Email address does not exist"]);
            }
            //check reset link
            if($hash !== hash("sha256", $user['pass'])){
                doReturn(400, false, ["message" => "Password Reset Link is invalid"]);
            }
            //send email
            doReturn(200, true, ["message" => "Password Reset Link was verified"]);
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