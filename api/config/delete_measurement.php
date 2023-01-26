<?php

require '../../core/functions.php';
cors();
//use octavalidate
use Validate\octaValidate;

//create new instance
$myForm = new octaValidate('form_delete_customer', OV_OPTIONS);
//define rules for each form input name
$valRules = array(
    "gender" => array(
        ["R", "Gender is required"],
        ["ALPHA_ONLY", "Gender must contain only letters"]
    ),
    "data" => array(
        ["R", "The data to delete is required"],
        ["ALPHA_SPACES", "The data to delete must contain only letters or spaces"]
    )
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($myForm->validateFields($valRules, $_POST) === true) {
            $user_id = verifyJWT();
            $user = $db->SelectOne("SELECT * FROM users WHERE user_id  = :uid", ['uid' => $user_id]);
            if (!$user) {
                doReturn(401, false, ["message" => "Please login to continue"]);
            } else {
                //check if license is active
                if(!activeLicense($user['expiry'])) doReturn(401, false, ["message" => "Your subscription has expired", "expired" => true]);

                //get measurement
                $config = $db->SelectOne("SELECT id, tape_male, tape_female FROM config WHERE user_id = :uid", [
                    'uid' => $user_id
                ]);

                //if data exists
                if(!$config){
                    doReturn(400, false, ["message" => "Measurement data does not exist"]);
                }

                $gender = strtolower($_POST['gender']);

                if($gender == "male"){

                    //check if measurement data is empty
                    (empty($config['tape_male'])) ? doReturn(400, false, ["message" => "Measurement data does not exist"]) : null;
                    //decode data
                    $tape = json_decode($config['tape_male'], true);
                    //find position of data in array
                    $ind = array_search($_POST['data'], $tape);
                    //remove item from arraay
                    unset($tape[$ind]);
                    //store new data 
                    $newData = [];
                    //loop through
                    foreach($tape as $ind => $d){
                        $newData[] = $d;
                    }
                    //update table
                    $db->Update("UPDATE config SET tape_male = :t WHERE id = :id", ['t' => json_encode($newData), 'id' => $config['id']]);

                }elseif($gender == "female"){

                    //check if measurement data is empty
                    (empty($config['tape_female'])) ? doReturn(400, false, ["message" => "Measurement data does not exist"]) : null;
                    //decode data
                    $tape = json_decode($config['tape_female'], true);
                    //find position of data in array
                    $ind = array_search($_POST['data'], $tape);
                    //remove item from arraay
                    unset($tape[$ind]);
                    //store new data 
                    $newData = [];
                    //loop through
                    foreach($tape as $ind => $d){
                        $newData[] = $d;
                    }
                    //update table
                    $db->Update("UPDATE config SET tape_female = :t WHERE id = :id", ['t' => json_encode($newData), 'id' => $config['id']]);
                }else{
                    doReturn(400, false, ["message" => "You have provided an invalid gender"]);
                }

                doReturn(200, true, ["message" => "Measurement data has been deleted"]);
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