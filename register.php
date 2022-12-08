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
    try { //begin validation    
        if ($myForm->validateFields($_POST, $valRules) === true) {

            //check if email is registered already
            $users = $db->SelectAll("SELECT * FROM users WHERE email = :email", ['email' => $_POST['email']]);

            if (count($users)) {
                http_response_code(400);
                $retval = array(
                    "success" => false,
                    "message" => "A user with this Email exists already"
                );
                print_r(json_encode($retval));
                exit();
            } else {
                //hash password
                $pass = password_hash($_POST['pass'], PASSWORD_BCRYPT);
                $user_id = md5($_POST['email'].uniqid());
                //save to database
                $insert = $db->Insert("INSERT INTO users (user_id, email, pass, date_joined) VALUES (:uid, :email, :pass, :date)", ['uid' => $user_id, 'email' => $_POST['email'], 'pass' => $pass, 'date' => time()]);

                //return success
                http_response_code(200);
                $retval = array(
                    "success" => true,
                    "message" => "Registration successful"
                );
                print_r(json_encode($retval));
                exit();
            }
        } else {
            http_response_code(400);
            //return errors  
            $retval = array(
                "success" => false,
                "formError" => $myForm->getErrors()
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

//check if field names exist then add new error that yit doesnt exist
//before validation, count error object and make sure it is 0
//loop through field list of val rules based on how your data is structured
?>