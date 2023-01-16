<?php

require '../../core/functions.php';
cors();

//use octavalidate
use Validate\octaValidate;

//create new instance
$myForm = new octaValidate('form_upd_profile', OV_OPTIONS);
//define rules for each form input name
$valRules = array(
    "fname" => array(
        ["R", "Your First Name is required"],
        ["APLHA_SPACES", "Your First name must have letters or spaces"]
    ),
    "lname" => array(
        ["R", "Your Last Name is required"],
        ["APLHA_SPACES", "Your Last must have letters or spaces"]
    ),
    "phone" => array(
        ["R", "Your Primary Phone is required"],
        ["DIGITS"]
    ),
    "alt_phone" => array(
        ["DIGITS"]
    ),
    "token" => array(
        ["R", "A token is required"]
    )
);

$fileRules = array(
    "image" => array(
        ["ACCEPT-MIME", "image/jpg, image/jpeg, image/png"]
    ));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($myForm->validateFields($valRules, $_POST) === true && $myForm->validateFiles($fileRules) === true) {

            $user_id = verifyToken($_POST['token']);

            $user = $db->SelectOne("SELECT * FROM users WHERE user_id  = :uid", ['uid' => $user_id]);
            if (!$user) {
                doReturn(401, false, ["message" => "Please login to continue"]);
            } else {
                //check if license is active
                if(!activeLicense($user['expiry'])) doReturn(401, false, ["message" => "Your subscription has expired"]);

                //reassign variables
                $lname = (isset($_POST['lname']) && !empty($_POST['lname'])) ? $_POST['lname'] : $user['lname'];
                $fname = (isset($_POST['fname']) && !empty($_POST['fname'])) ? $_POST['fname'] : $user['fname'];
                $phone = (isset($_POST['phone']) && !empty($_POST['phone'])) ? $_POST['phone'] : $user['phone'];
                $addr = (isset($_POST['addr']) && !empty($_POST['addr'])) ? $_POST['addr'] : $user['addr'];
                $alt_phone = ((isset($_POST['alt_phone']) && !empty($_POST['alt_phone'])) ? $_POST['alt_phone'] : (!empty($user['alt_phone']))) ? $user['alt_phone'] : null;

                if( (isset($_FILES['image']) && !empty($_FILES['image'])) ) {
                    // $image = $_FILES['image']['name'];
                    $image = time().uniqid().strtolower(substr($_FILES['image']['name'], strrpos($_FILES['image']['name'], ".")));
                }elseif(!empty($user['image'])){
                    $image = $user['image'];
                }else{
                    $image = null;
                }

                /*

                $image = ((isset($_FILES['image']) && !empty($_FILES['image']['name'])) 
                ? ($_FILES['image']['name']) : 
                (!empty($user['image']))) ? $user['image'] : null;

                */

                //check if user uploaded an image
                if (isset($_FILES['image']) && !empty($_FILES['image'])) {
                    //delete previous profile
                    if(!empty($user['image']) && file_exists(PROFILE_DIR.$user['image'])){
                        unlink(PROFILE_DIR.$user['image']);
                    }

                    $target_file = '../../'.PROFILE_DIR . $image;
                    //upload file
                    move_uploaded_file($_FILES['image']['tmp_name'], $target_file);

                }

                //check if data exists already
                $upd = $db->Update("UPDATE users SET fname = :fname, lname = :lname, phone = :phone, alt_phone = :alt_phone, image = :img, addr = :addr WHERE id = :id", [
                    'fname' => $fname,
                    'lname' => $lname,
                    'phone' => $phone, 
                    'img' => $image,
                    'alt_phone' => $alt_phone, 
                    'addr' => $addr, 
                    'id' => $user['id']
                ]);

                doReturn(200, true, ["message" => "Profile updated successfully"]);
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