<?php
/*
// stores measurement configuration data for both male or female 
// I feel this script ain't perfect yet
*/

require '../../core/functions.php';
cors();

//use octavalidate
use Validate\octaValidate;

//create new instance
$myForm = new octaValidate('form_new_customer', OV_OPTIONS);
//define rules for each form input name
$valRules = array(
    "gender" => array(
        ["R", "Gender is required"],
        ["ALPHA_ONLY", "Gender must contain only letters"]
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
                if (!activeLicense($user['expiry']))
                    doReturn(401, false, ["message" => "Your subscription has expired", "expired" => true]);

                //default vars
                $tape_male = $tape_female = null;
                $gender = $_POST['gender'];

                //check if user has a config column created
                $config = $db->SelectOne("SELECT * FROM config WHERE user_id = :uid", [
                    'uid' => $user_id
                ]);

                //one measurement must be submitted
                //if male is submitted or female is submitted and the both are empty, failure!
                if ((isset($_POST['tape_male']) || isset($_POST['tape_male'])) && ((empty($_POST['tape_male'])) && empty($_POST['tape_male']))) {
                    doReturn(400, false, ["message" => "You must provide a measurement"]);
                }

                // //check if valid data is submitted (Values must be separated by a comma)
                // if( isset($_POST['tape_male']) && !empty($_POST['tape_male']) ){
                //     ( strpos($_POST['tape_male'], ',') === false) ? doReturn(400, false, ["message" => "You must provide a valid measurement"]) : null;
                // }
                // //check if valid data is submitted (Values must be separated by a comma)
                // if( isset($_POST['tape_female']) && !empty($_POST['tape_female']) ){
                //     ( strpos($_POST['tape_female'], ',') === false) ? doReturn(400, false, ["message" => "You must provide a valid measurement"]) : null;
                // }

                //check if male or female measurements were submitted
                if (isset($_POST['tape_male']) && !empty($_POST['tape_male'])) {

                    //validate data
                    (strpos($_POST['tape_male'], ',') === false) ? doReturn(400, false, ["message" => "Validation error"]) : null;
                    //store in array
                    $tape_male = explode(',', $_POST['tape_male']);

                } elseif (isset($_POST['tape_female']) && !empty($_POST['tape_female'])) {

                    //validate data
                    (strpos($_POST['tape_female'], ',') === false) ? doReturn(400, false, ["message" => "Validation error"]) : null;
                    //store in array
                    $tape_female = explode(',', $_POST['tape_female']);

                }

                //check if configuration has been created before
                if ($config) {
                    //check if male measurements were provided
                    if ($tape_male) {
                        //update male measurement
                        $db->Update("UPDATE config SET tape_male = :tape WHERE id = :id", [
                            'id' => $config['id'],
                            'tape' => json_encode($tape_male)
                        ]);

                    } elseif ($tape_female) {
                        //update male measurement
                        $db->Update("UPDATE config SET tape_female = :tape WHERE id = :id", [
                            'id' => $config['id'],
                            'tape' => json_encode($tape_female)
                        ]);
                    }
                } else {
                    //check if male measurements were provided
                    if ($tape_male) {
                        //insert
                        $db->Insert("INSERT INTO config (user_id, tape_male) VALUES (:uid, :tape)", [
                            'uid' => $user_id,
                            'tape' => json_encode($tape_male)
                        ]);
                    } elseif ($tape_female) {
                        //insert
                        $db->Insert("INSERT INTO config (user_id, tape_female) VALUES (:uid, :tape)", [
                            'uid' => $user_id,
                            'tape' => json_encode($tape_female)
                        ]);
                    }
                }

                doReturn(200, true, ["message" => "Measurement data was updated successfully"]);
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