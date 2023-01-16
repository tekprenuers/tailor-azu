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
    "requirement" => array(
        ["TEXT", "Customer's requirement contains invalid characters"]
    ),
    "addr" => array(
        ["R", "Customer's Home Address is required"],
        ["TEXT", "Customer's Home address contains invalid characters"]
    ),
    "token" => array(
        ["R", "A token is required"]
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
            $user_id = verifyToken($_POST['token']);
            $user = $db->SelectOne("SELECT * FROM users WHERE user_id  = :uid", ['uid' => $user_id]);
            if (!$user) {
                doReturn(401, false, ["message" => "Please login to continue"]);
            } else {
                //check if license is active
                if (!activeLicense($user['expiry']))
                    doReturn(401, false, ["message" => "Your subscription has expired"]);

                $cus_name = $_POST['lname'] . ' ' . $_POST['fname'];
                $cus_id = md5(substr($cus_name, 0, 5) . uniqid());

                //default vars
                $cus_image = $cus_phone = $cus_alt_phone = $requirement = $due_date = $email = $addr = $gender = null;

                //reassign variables
                $cus_phone = (isset($_POST['phone']) && !empty($_POST['phone'])) ? '+234' . $_POST['phone'] : null;
                $email = (isset($_POST['email']) && !empty($_POST['email'])) ? $_POST['email'] : null;
                $addr = (isset($_POST['addr']) && !empty($_POST['addr'])) ? $_POST['addr'] : null;
                $cus_alt_phone = (isset($_POST['alt_phone']) && !empty($_POST['alt_phone'])) ? '+234' . $_POST['alt_phone'] : null;
                $gender = (isset($_POST['gender']) && !empty($_POST['gender'])) ? $_POST['gender'] : null;

                //check if customer exists already
                $customer = $db->SelectOne("SELECT * FROM customers WHERE (phone = :phone OR alt_phone = :alt_phone OR email = :email) AND user_id = :uid", ['phone' => $cus_phone, 'alt_phone' => $cus_alt_phone, 'email' => $email, 'uid' => $user_id]);
                if ($customer) {
                    doReturn(400, false, ["message" => "You have already added this customer"]);
                }

                //check if user uploaded an image
                $cus_image = (isset($_FILES['image']) && !empty($_FILES['image'])) ? $_FILES['image']['name'] : null;
                $requirement = (isset($_POST['requirement']) && !empty($_POST['requirement'])) ? $_POST['requirement'] : null;
                $due_date = (isset($_POST['due_date']) && !empty($_POST['due_date'])) ? strtotime($_POST['due_date']) : null;
                //check if user uploaded an image again
                if ($cus_image) {
                    $target_file = '../../'.PROFILE_DIR . $_FILES['image']['name'];
                    //upload file
                    move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
                }

                $db->Insert("INSERT INTO customers (user_id, cus_id, name, gender, image, phone, alt_phone, email, addr, date_added, requirement, due_date) VALUES (:uid, :cid, :name, :gender, :image, :phone, :alt_phone, :email, :addr, :date_added, :req, :due_date)", [
                    'uid' => $user_id,
                    'cid' => $cus_id,
                    'name' => $cus_name,
                    'gender' => $gender,
                    'image' => $cus_image,
                    'phone' => $cus_phone,
                    'alt_phone' => $cus_alt_phone,
                    'email' => $email,
                    'addr' => $addr,
                    'date_added' => time(),
                    'req' => $requirement,
                    'due_date' => $due_date
                ]);

                doReturn(200, true, ["message" => "Customer added successfully"]);
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