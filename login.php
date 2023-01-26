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
                //user_id, expiry_time,
                // $loggedInToken = base64_encode($user['user_id']) . '::' . strtotime("+24 hours", time());
                //return success
                $retval = array(
                    "message" => "Login successful",
                    //store user id in JWT payload
                    "token" => doJWT(["uid" => $user['user_id']])
                );
                //check if user has updated his profile
                if (checkUpdatedProfile($user)) {
                    $retval["user"] = array(
                        "fname" => $user['fname'],
                        "image" => (!empty($user['image'])) ? BACKEND_URL . '/'.PUBLIC_PROFILE_DIR . $user['image'] : null
                    );
                    $retval["profileUpdated"] = true;
                } else {
                    $retval["profileUpdated"] = false;
                }
                //do response
                doReturn(200, true, $retval);
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