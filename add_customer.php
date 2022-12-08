<?php
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
$myForm = new octaValidate('form_register', $options);
//define rules for each form input name
$valRules = array(
    "cus_name" => array(
        ["R", "Your password is required"],
        ["APLHA_SPACES", "Your name must have letters or spaces"]
    ),
    "email" => array(
        ["R", "Your Email Address is required"],
        ["EMAIL", "Your Email Address is invalid!"]
    ),
    "token" => array(
        ["R", "You must be logged in to continue"]
    )
);

if (isset($_POST['token']) && !empty($_POST['token'])) {
    try {
        if (str_contains($_POST['token'], "::")) {
            //check if token is valid
            $token = explode("::", $_POST['token']);
            $user_id = $token[0];
            $time = $token[1];
            if (!$user_id || $time || (time() > intval($time))) {
                http_response_code(401);
                //return errors  
                $retval = array(
                    "success" => false,
                    "message" => "You have to login to continue"
                );
                print_r(json_encode($retval));
                exit();
            } else {

            }
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