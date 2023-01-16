<?php

require '../../core/functions.php';
cors();
//use octavalidate
use Validate\octaValidate;

//create new instance
$myForm = new octaValidate('', OV_OPTIONS);
//define rules for each form input name
$valRules = array(
    "token" => array(
        ["R", "A token is required"]
    )
);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        if ($myForm->validateFields($valRules, $_GET) === true) {
            $user_id = verifyToken($_GET['token']);
            $user = $db->SelectOne("SELECT * FROM users WHERE user_id  = :uid", ['uid' => $user_id]);
            if (!$user) {
                doReturn(401, false, ["message" => "Please login to continue"]);
            } else {
                $user['image'] = BACKEND_URL.PUBLIC_PROFILE_DIR.$user['image'];
                //hide private data
                unset($user['id']);
                unset($user['user_id']);
                unset($user['pass']);
                unset($user['is_premium']);
                unset($user['date_joined']);
                unset($user['user_id']);

                doReturn(200, true, $user);
            }
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