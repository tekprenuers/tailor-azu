<?php
require 'core/functions.php';
//do cors
cors();
//use octavalidate
use Validate\octaValidate;

//create new instance
$myForm = new octaValidate('form_register', OV_OPTIONS);
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
            $users = $db->SelectAll("SELECT * FROM users WHERE email = :email", ['email' => $_POST['email']]);

            if (count($users)) {
                doReturn(400, false, ["message" => "A user with this email address exists already"]);
            } else {
                //hash password
                $pass = password_hash($_POST['pass'], PASSWORD_BCRYPT);
                $user_id = md5($_POST['email'].uniqid());
                //save to users table
                $insert = $db->Insert("INSERT INTO users (user_id, email, pass, expiry, date_joined) VALUES (:uid, :email, :pass, :exp, :date)", ['uid' => $user_id, 'email' => $_POST['email'], 'pass' => $pass, 'exp' => strtotime("+14 days", time()), 'date' => time()]);
                //subscribe the user to our newsletter
                $newsletter = $db->Insert("INSERT INTO newsletters (email, last_updated) VALUES (:email, :date)", ['email' => $_POST['email'], 'date' =>time()]);
                ///////////////////////////////send mail

                $emailTemp = file_get_contents('emails/register.html');
                $dynamic = array(
                    "EXPIRY_END_DATE" => gmdate("d M Y", strtotime("+14 days", time())),
                    "UID" =>  $user_id
                );
                //replace placeholders with actual values
                $body = doDynamicEmail($dynamic, $emailTemp);
                //send mail
                sendMail($_POST['email'], '', "Account Created Successfully", $body);
                //return response
                doReturn(200, true, ["message" => "Registration successful"]);
            }
        } else {
            doReturn(400, false, ["formError" => $myForm->getErrors()]);
        }
    } catch (Exception $e) {
        error_log($e);
        doReturn(500, false, ["message" => "A server error has occured"]);
    }
}else{
    doReturn(400, false, ["message" => "Invalid request method"]);
}

//check if field names exist then add new error that yit doesnt exist
//before validation, count error object and make sure it is 0
//loop through field list of val rules based on how your data is structured
?>