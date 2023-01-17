<?php
require 'core/functions.php';
//do cors
cors();
//use octavalidate
use Validate\octaValidate;

//create new instance
$myForm = new octaValidate('form_reset_pass', OV_OPTIONS);
//define rules for each form input name
$valRules = array(
    "pass" => array(
        ["R", "Your password is required"],
        ["MINLENGTH", 8, "Your password must have a minimum of 8 characters"]
    ),
    "email" => array(
        ["R", "Your Email Address is required"],
        ["EMAIL", "Your Email Address is invalid!"]
    ),
    "hash" => array(
        ["R", "Hash is required"]
    )
);
//Check if it is a post request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        //begin validation    
        if ($myForm->validateFields($valRules, $_POST) === true) {

            $hash = strtolower(trim(urldecode($_POST['hash'])));
            $email = strtolower(trim(urldecode($_POST['email'])));
            $pass = trim($_POST['pass']);

            //check if email is registered already
            $user = $db->SelectOne("SELECT * FROM users WHERE email = :email", ['email' => $email]);

            if (!$user) {
                doReturn(400, false, ["message" => "Email address does not exist"]);
            }
            //verify hash
            if ($hash !== hash("sha256", $user['pass'])) {
                doReturn(400, false, ["message" => "Password Reset Link is invalid"]);
            }
            //update password
            $newPass = password_hash($pass, PASSWORD_BCRYPT);
            //update db
            $upd = $db->Update("UPDATE users SET pass = :pass WHERE id = :id", ['pass' => $newPass, 'id' => $user['id']]);

            ///////////////////////////////send mail

            $emailTemp = file_get_contents('emails/reset_completed.html');
            $dynamic = array(
                "FNAME" => (!empty($user['fname'])) ? $user['fname'] : "Esteemed Client",
                "TIME" => gmdate("d M Y", time()),
                "UID" => $user['user_id']
            );
            //replace placeholders with actual values
            $body = doDynamicEmail($dynamic, $emailTemp);
            //send mail
            sendMail($_POST['email'], '', "Password Reset Completed", $body);
            //return response
            doReturn(200, true, ["message" => "Password has been updated"]);
        } else {
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