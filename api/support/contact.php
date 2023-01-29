<?php
//creates a ticket for users logged into the dashboard

require '../../core/functions.php';
cors();

//use octavalidate
use Validate\octaValidate;

//create new instance
$myForm = new octaValidate('', OV_OPTIONS);
//define rules for each form input name
$valRules = array(
    "title" => array(
        ["R", "Title is required"],
        ["TEXT", "Title contains invalid characters"]
    ),
    "email" => array(
        ["R", "Email is required"],
        ["EMAIL"]
    ),
    "category" => array(
        ["R", "Category is required"],
        ["ALPHA_ONLY", "Category contains invalid characters"]
    ),
    "desc" => array(
        ["R", "Description is required"],
        ["TEXT", "Description contains invalid characters"]
    )
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($myForm->validateFields($valRules, $_POST) === true) {

                //default vars
                $title = $desc = $category = $email = null;

                //reassign variables
                $email = $_POST['email'];
                $title = $_POST['title'];
                $desc =  $_POST['desc'];
                $category =  $_POST['category'];

                //SEND MAIL TO ME
                ///////////////////////////////send mail

                $emailTemp = file_get_contents('../../emails/support.html');
                $dynamic = array(
                    "EMAIL" => $email,
                    "TITLE" =>  $title,
                    "DESC" => $desc,
                    "CATEGORY" => $category
                );
                //replace placeholders with actual values
                $body = doDynamicEmail($dynamic, $emailTemp);
                //send mail
                sendMail("ugorji757@gmail.com", "Support Team", $title, $body);

                doReturn(200, true, ["message" => "Your message was sent successfully"]);
            
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