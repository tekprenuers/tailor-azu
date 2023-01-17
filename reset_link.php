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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        //begin validation    
        if ($myForm->validateFields($valRules, $_POST) === true) {

            //check if email is registered already
            $user = $db->SelectOne("SELECT * FROM users WHERE email = :email", ['email' => $_POST['email']]);

            if (!$user) {
                doReturn(400, false, ["message" => "Email address does not exist"]);
            }
            //generate reset link
            //try to add time limit too
            $link = ORIGIN . '/reset?email=' . $user['email'] . '&hash=' . hash("sha256", $user['pass']);

            ///////////////////////////////send mail
            
            $emailTemp = file_get_contents('emails/reset_link.html');
            $dynamic = array(
                "FNAME" => (!empty($user['fname'])) ? $user['fname'] : "Esteemed Client",
                "RESET_LINK" => $link,
                "UID" => $user['user_id']
            );
            //replace placeholders with actual values
            $body = doDynamicEmail($dynamic, $emailTemp);
            //send mail
            sendMail($_POST['email'], '', "Reset Your Password", $body);
            //return response
            doReturn(200, true, ["message" => "Please check your email for instructions", "link" => $link]);
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