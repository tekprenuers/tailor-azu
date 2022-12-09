<?php

define('CUSTOMER_PROFILE_DIR', 'uploads/profile/');
define('BACKEND_URL', 'http://localhost/tailorAzu/');
define('PUBLIC_CUSTOMER_PROFILE_DIR', 'uploads/profile/');

//import database class
require 'class_db.php';

//import octavalidate
require 'octaValidate-PHP/src/Validate.php';
//instantiate class
$db = new DatabaseClass();

//set configuration
define('OV_OPTIONS', array(
    "stripTags" => true,
    "strictMode" => true
));

function doError(int $status = 400, $error)
{
    //Easily print out errors to the user
    $retval = array(
        "success" => false,
        "data" => $error
    );
    http_response_code($status);
    return (print_r(json_encode($retval)) . exit());
}

function verifyToken(string $Token) {

    if (strpos($Token, "::")) {
        //check if token is valid
        $token = explode("::", $Token);
        $user_id = base64_decode( $token[0] );
        $time = $token[1];
        $plan = base64_decode( $token[2] );
        if (!$user_id || !$time || !$plan || (time() > intval($time))) {
            http_response_code(401);
            //return errors  
            $retval = array(
                "success" => false,
                "message" => "You have to login to continue"
            );
            print_r(json_encode($retval));
            exit();
        }else{
            //if successful, return the user_id
            return $user_id;
        }
    }
}
function doReturn(int $status = 400, bool $success = false, $data)
{
    //Easily print out errors to the user
    $retval = array(
        "success" => $success,
        "data" => $data
    );
    http_response_code($status);
    return (print_r(json_encode($retval)) . exit());
}

//do stats function for price and num of customers
?>