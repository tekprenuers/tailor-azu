<?php

require '../../core/functions.php';
cors();

//use octavalidate
use Validate\octaValidate;

//create new instance
$myForm = new octaValidate('form_new_customer', OV_OPTIONS);
//define rules for each form input name
$valRules = array(
    "fname" => array(
        ["R", "Customer's First Name is required"],
        ["APLHA_SPACES", "Customer's First name must have letters or spaces"]
    ),
    "lname" => array(
        ["R", "Customer's Last Name is required"],
        ["APLHA_SPACES", "Customer's Last must have letters or spaces"]
    ),
    "gender" => array(
        ["R", "Gender is required"],
        ["ALPHA_ONLY", "Gender must contain only letters"]
    ),
    "cus_id" => array(
        ["R", "Customer's ID is required!"],
        ["ALPHA_NUMERIC", "Customer ID must contain letters or numbers"]
    ),
    "phone" => array(
        ["R", "Customer's Primary Phone is required"],
        ["DIGITS"]
    ),
    "alt_phone" => array(
        ["DIGITS"]
    ),
    "email" => array(
        ["EMAIL"]
    ),
    "addr" => array(
        ["R", "Customer's Home Address is required"],
        ["TEXT", "Customer's Home address contains invalid characters"]
    )
);

$fileRules = array(
    "image" => array(
        ["ACCEPT-MIME", "image/jpg, image/jpeg, image/png"]
    )
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($myForm->validateFields($valRules, $_POST) === true && $myForm->validateFiles($fileRules) === true) {
            $user_id = verifyJWT();
            $user = $db->SelectOne("SELECT * FROM users WHERE user_id  = :uid", ['uid' => $user_id]);
            if (!$user) {
                doReturn(401, false, ["message" => "Please login to continue"]);
            } else {
                //check if license is active
                if (!activeLicense($user['expiry']))
                    doReturn(401, false, ["message" => "Your subscription has expired", "expired" => true]);

                //check if customer exists already
                $customer = $db->SelectOne("SELECT * FROM customers WHERE user_id = :uid AND cus_id = :cid", ['cid' => $_POST['cus_id'], 'uid' => $user_id]);

                //update data if customer exists
                if (!$customer) {
                    doReturn(400, false, ["message" => "This customer does not exist!"]);
                }

                //default vars
                $cus_image = $cus_phone = $cus_alt_phone = $email = $addr = $gender = null;

                //reassign variables
                $cus_phone = (isset($_POST['phone']) && !empty($_POST['phone'])) ? '+234' . $_POST['phone'] : $customer['phone'];
                $cus_name = ((isset($_POST['lname']) && !empty($_POST['lname'])) && isset($_POST['fname']) && !empty($_POST['fname'])) ? $_POST['lname'] . ' ' . $_POST['fname'] : $customer['name'];
                $email = (isset($_POST['email']) && !empty($_POST['email'])) ? $_POST['email'] : $customer['email'];
                $addr = (isset($_POST['addr']) && !empty($_POST['addr'])) ? $_POST['addr'] : $customer['addr'];
                $cus_alt_phone = (isset($_POST['alt_phone']) && !empty($_POST['alt_phone'])) ? '+234' . $_POST['alt_phone'] : $customer['alt_phone'];
                $gender = (isset($_POST['gender']) && !empty($_POST['gender'])) ? $_POST['gender'] : $customer['gender'];

                //check if user uploaded an image
                $cus_image = (isset($_FILES['image']) && !empty($_FILES['image'])) ? $_FILES['image']['name'] : $customer['image'];

                //check if user changed customer's image
                if (isset($_FILES['image']) && !empty($_FILES['image'])) {
                    //delete previous profile
                    if(!empty($customer['image']) && file_exists(PROFILE_DIR.$customer['image'])){
                        unlink(PROFILE_DIR.$customer['image']);
                    }
                    $target_file = '../../'.PROFILE_DIR . $_FILES['image']['name'];
                    //upload file
                    move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
                }

                $db->Insert("UPDATE customers SET name = :name, gender = :gender, image = :img, phone = :phone, alt_phone = :alt_phone, email = :email, addr = :addr WHERE id = :id", [
                    'id' => $customer['id'],
                    'name' => $cus_name,
                    'gender' => $gender,
                    'img' => $cus_image,
                    'phone' => $cus_phone,
                    'alt_phone' => $cus_alt_phone,
                    'email' => $email,
                    'addr' => $addr
                ]);

                doReturn(200, true, ["message" => "Customer updated successfully"]);
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