<?php

require '../../core/functions.php';
cors();
//use octavalidate
use Validate\octaValidate;

//create new instance
$myForm = new octaValidate('form_get_measurement', OV_OPTIONS);
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
                //check if license is active
                if (!activeLicense($user['expiry']))
                    doReturn(401, false, ["message" => "Your subscription has expired"]);

                //get measurement
                $config = $db->SelectOne("SELECT tape_male, tape_female FROM config WHERE user_id = :uid", [
                    'uid' => $user_id
                ]);

                //if data exists
                if($config){
                    //hide private vars
                    unset($config['user_id']);
                    unset($config['id']);
                    //decode and reassign
                    $config['tape_male'] = json_decode($config['tape_male']);
                    $config['tape_female'] = json_decode($config['tape_female']);
                }

                doReturn(200, true, $config);
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