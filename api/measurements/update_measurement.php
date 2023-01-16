<?php
/*
// stores measurement data for males or females
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
    ),
    "cus_id" => array(
        ["R", "Customer's ID is required!"],
        ["ALPHA_NUMERIC", "Customer ID must contain letters or numbers"]
    ),
    "token" => array(
        ["R", "A token is required"]
    )
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($myForm->validateFields($valRules, $_POST) === true) {
            $user_id = verifyToken($_POST['token']);
            $user = $db->SelectOne("SELECT * FROM users WHERE user_id  = :uid", ['uid' => $user_id]);
            if (!$user) {
                doReturn(401, false, ["message" => "Please login to continue"]);
            } else {
                //check if license is active
                if (!activeLicense($user['expiry']))
                    doReturn(401, false, ["message" => "Your subscription has expired"]);

                //default vars
                $tape_male = $tape_female = null;
                $gender = $_POST['gender'];

                //check if customer has a measurement saved already
                $measurement = $db->SelectOne("SELECT * FROM measurements WHERE user_id = :uid AND cus_id = :cid", [
                    'uid' => $user_id,
                    'cid' => $_POST['cus_id']
                ]);

                //one measurement must be submitted
                //if male is submitted or female is submitted and the both are empty throw fatal failure!
                if ((isset($_POST['tape_male']) || isset($_POST['tape_male'])) && ((empty($_POST['tape_male'])) && empty($_POST['tape_male']))) {
                    doReturn(400, false, ["message" => "You must provide a measurement"]);
                }

                //check if male or female measurements were submitted
                if (isset($_POST['tape_male']) && !empty($_POST['tape_male'])) {
                    //store in array
                    $tape_male = json_decode($_POST['tape_male'], true);
                    //validate data
                    (empty($tape_male)) ? doReturn(400, false, ["message" => "Validation error"]) : null;


                } elseif (isset($_POST['tape_female']) && !empty($_POST['tape_female'])) {
                    //store in array
                    $tape_female = json_decode($_POST['tape_female'], true);
                    //validate data
                    (empty($tape_female)) ? doReturn(400, false, ["message" => "Validation error"]) : null;
                }

                //check if measurement was saved before
                if ($measurement) {
                    //check if male measurements were provided
                    if ($tape_male) {
                        //update male measurement
                        $db->Update("UPDATE measurements SET tape_male = :tape, date_updated = :date WHERE id = :id", [
                            'id' => $measurement['id'],
                            'tape' => json_encode($tape_male),
                            'date' => time()
                        ]);

                    } elseif ($tape_female) {
                        //update male measurement
                        $db->Update("UPDATE measurements SET tape_female = :tape, date_updated = :date WHERE id = :id", [
                            'id' => $measurement['id'],
                            'tape' => json_encode($tape_female),
                            'date' => time()
                        ]);
                    }
                } else {
                    //check if male measurements were provided
                    if ($tape_male) {
                        //insert
                        $db->Insert("INSERT INTO measurements (cus_id, user_id, tape_male, date_updated) VALUES (:cid, :uid, :tape, :date)", [
                            'cid' => $_POST['cus_id'],
                            'uid' => $user_id,
                            'tape' => json_encode($tape_male),
                            'date' => time()
                        ]);
                    } elseif ($tape_female) {
                        //insert
                        $db->Insert("INSERT INTO measurements (cus_id, user_id, tape_female, date_updated) VALUES (:cid, :uid, :tape, :date)", [
                            'cid' => $_POST['cus_id'],
                            'uid' => $user_id,
                            'tape' => json_encode($tape_female),
                            'date' => time()
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