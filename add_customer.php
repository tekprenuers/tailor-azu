<?php

require 'core/functions.php';
//use octavalidate
use Validate\octaValidate;

//create new instance
$myForm = new octaValidate('form_register', OV_OPTIONS);
//define rules for each form input name
$valRules = array(
    "cus_name" => array(
        ["R", "Customer name is required"],
        ["APLHA_SPACES", "Customer name must have letters or spaces"]
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
                //check if customer exists already
                $customer = $db->SelectOne("SELECT * FROM customers WHERE cus_name = :name AND user_id = :uid", ['name' => $_POST['cus_name'], 'uid' => $user_id]);
                if ($customer) {
                    doReturn(400, false, ["message" => "You have added this customer before"]);
                }
                $cus_id = md5(substr($_POST['cus_name'], 0, 5).uniqid());
                //default vars
                $cus_image = $cus_phone = $cus_alt_phone = $requirement = $due_date = null;
                //check if user is premium or basic
                if ($user['is_premium'] == "Yes") {
                    //check if user uploaded an image
                    $cus_image = (isset($_FILES['cus_image']) && !empty($_FILES['cus_image'])) ? $_FILES['cus_image'] : null;
                    $cus_phone = (isset($_POST['cus_phone']) && !empty($_POST['cus_phone'])) ? $_POST['cus_phone'] : null;
                    $cus_alt_phone = (isset($_POST['cus_alt_phone']) && !empty($_POST['cus_alt_phone'])) ? $_POST['cus_alt_phone'] : null;
                    $requirement = (isset($_POST['requirement']) && !empty($_POST['requirement'])) ? $_POST['requirement'] : null;
                    $due_date = (isset($_POST['due_date']) && !empty($_POST['due_date'])) ? $_POST['due_date'] : null;
                    //check if user uploaded an image again
                    if ($cus_image) {
                        $target_file = CUSTOMER_PROFILE_DIR . $_FILES['cus_image']['name'];
                        //upload file
                        move_uploaded_file($_FILES['cus_name']['tmp_name'], $target_file);
                    }
                }

                $db->Insert("INSERT INTO customers (user_id, cus_id, cus_name, cus_image, cus_phone, cus_alt_phone, date_added, requirement, due_date) VALUES (:uid, :cid, :name, :image, :phone, :alt_phone, :date_added, :req, :due_date)", [
                    'uid' => $user_id,
                    'cid' => $cus_id,
                    'name' => $_POST['cus_name'],
                    'image' => $cus_image,
                    'phone' => $cus_phone,
                    'alt_phone' => $cus_alt_phone,
                    'date_added' => time(),
                    'req' => $requirement,
                    'due_date' => $due_date
                ]);

                doReturn(200, true, ["message" => "Customer added successfully"]);

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
}else{
    doReturn(400, false, ["message" => "Invalid request method"]);
}
?>