<?php
//import octavalidate
require 'core/octaValidate-PHP/src/Validate.php';
//import database class
require 'core/class_db.php';
//import functions
require 'core/functions.php';

//instantiate class
$db = new DatabaseClass();

//use it
use Validate\octaValidate;

//set configuration
$options = array(
    "stripTags" => true,
    "strictMode" => true
);
//create new instance
$myForm = new octaValidate('form_login', $options);
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
        if ($myForm->validateFields($_POST, $valRules) === true) {
    
            //check if email is registered already
            $user = $db->SelectOne("SELECT * FROM users WHERE email = :email", ['email' => $_POST['email']]);

            if (!$user) {
                http_response_code(401);
                $retval = array(
                    "success" => false,
                    "message" => "Email address does not exist"
                );
                print_r(json_encode($retval));
                exit();
            }

            //compare password
            if (password_verify($_POST['pass'], $user['secret']) === false) {
                http_response_code(401);
                $retval = array(
                    "success" => false,
                    "message" => "You have provided an Invalid password"
                );
                print_r(json_encode($retval));
                exit();
            } else {
                $loggedInToken = $user['user_id'].'::'.strtotime("+24 hours", time());
                //return success
                http_response_code(200);
                $retval = array(
                    "success" => true,
                    "message" => "Login successful",
                    "token" => $loggedInToken
                );
                print_r(json_encode($retval));
                exit();
            }
        } else {
            http_response_code(400);
            //return errors  
            $retval = array(
                "success" => false,
                "formError" => json_encode($myForm->getErrors())
            );
            print_r(json_encode($retval));
            exit();
        }
    } catch (Exception $e) {
        error_log($e);
        http_response_code(500);
        //return errors  
        $retval = array(
            "success" => false,
            "message" => "A server error has occured"
        );
        print_r(json_encode($retval));
        exit();
    }
}
?>