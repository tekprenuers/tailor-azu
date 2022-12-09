<?php

require 'core/functions.php';
//use octavalidate
use Validate\octaValidate;

//create new instance
$myForm = new octaValidate('form_update_customer', OV_OPTIONS);
//define rules for each form input name
$valRules = array(
    "cus_id" => array(
        ["R", "Customer ID is required"],
        ["APLHA_NUMERIC", "Customer ID must have letters or numbers"]
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
                //check if customer exists
                $customer = $db->SelectOne("SELECT * FROM customers WHERE cus_id = :cid AND user_id = :uid", ['cid' => $_POST['cus_id'], 'uid' => $user_id]);
                if (!$customer) {
                    doReturn(400, false, ["message" => "This customer does not exist"]);
                }
                //default vars
                $cus_image = $cus_phone = $cus_alt_phone = $requirement = $due_date = null;
                $cus_name = (isset($_POST['cus_name']) && !empty($_POST['cus_name'])) ? $_POST['cus_name'] : $customer['cus_name'];
                //check if user is premium or basic
                if ($user['is_premium'] == "Yes") {
                    //check if user uploaded an image
                    $cus_image = (isset($_FILES['cus_image']) && !empty($_FILES['cus_image'])) ? $_FILES['cus_image'] : $customer['cus_image'];
                    $cus_phone = (isset($_POST['cus_phone']) && !empty($_POST['cus_phone'])) ? $_POST['cus_phone'] : $customer['cus_phone'];
                    $cus_alt_phone = (isset($_POST['cus_alt_phone']) && !empty($_POST['cus_alt_phone'])) ? $_POST['cus_alt_phone'] : $customer['cus_alt_phone'];
                    $requirement = (isset($_POST['requirement']) && !empty($_POST['requirement'])) ? $_POST['requirement'] : $customer['requirement'];
                    $due_date = (isset($_POST['due_date']) && !empty($_POST['due_date'])) ? $_POST['due_date'] : $customer['due_date'];
                    //check if user uploaded an image again
                    if ($cus_image) {
                        //check if there is an image saved before
                        if($customer['cus_image']){
                            //check if the image file exists
                            if(file_exists(CUSTOMER_PROFILE_DIR.$customer['cus_image'])){
                                //remove the file
                                unlink(CUSTOMER_PROFILE_DIR.$customer['cus_image']);
                            }
                        }
                        //new file to save
                        $target_file = CUSTOMER_PROFILE_DIR . $_FILES['cus_image']['name'];
                        //upload file
                        move_uploaded_file($_FILES['cus_name']['tmp_name'], $target_file);
                    }
                }

                $db->UPDATE("UPDATE customers SET cus_name = :name, cus_image = :image, cus_phone = :phone, cus_alt_phone = :alt_phone, requirement = :req, due_date = :due_date WHERE id = :id ", [
                    'id' => $customer['id'],
                    'name' => $cus_name,
                    'image' => $cus_image,
                    'phone' => $cus_phone,
                    'alt_phone' => $cus_alt_phone,
                    'req' => $requirement,
                    'due_date' => $due_date
                ]);
                doReturn(200, true, ["message" => "Customer data has been updated"]);
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