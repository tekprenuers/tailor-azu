<?php
/*
// adds a new request
*/

require '../../core/functions.php';
cors();

//use octavalidate
use Validate\octaValidate;

//create new instance
$myForm = new octaValidate('form_new_customer', OV_OPTIONS);
//define rules for each form input name
$valRules = array(
    "name" => array(
        ["R", "Request's Name is required"],
        ["TEXT", "Request's Name contains invalid characters"]
    ),
    "deadline" => array(
        ["R", "Date to be delivered is required"]
    ),
    "extra_note" => array(
        ["R", "Additional data is required"]
    ),
    "cus_id" => array(
        ["R", "Customer ID is required"],
        ["ALPHA_NUMERIC", "Customer ID must have letters or numbers"]
    ),
    "price" => array(
        ["DIGITS"]
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
                    doReturn(401, false, ["message" => "Your subscription has expired", "expired" => true]);

                //default vars
                $image = $price = $extra_note = $deadline = $name = null;

                //reassign variables
                $price = (isset($_POST['price']) && !empty($_POST['price'])) ? $_POST['price'] : null;
                $extra_note = (isset($_POST['extra_note']) && !empty($_POST['extra_note'])) ? $_POST['extra_note'] : null;
                $name = (isset($_POST['name']) && !empty($_POST['name'])) ? $_POST['name'] : null;
                $deadline = (isset($_POST['deadline']) && !empty($_POST['deadline'])) ? strtotime($_POST['deadline']) : null;

                //check if due date is in the past
                if(time() > $deadline)  doReturn(400, false, ["message" => "Due date must not be in the past"]);

                //check if user uploaded an image
                $image = (isset($_FILES['image']) && !empty($_FILES['image'])) ? $_FILES['image']['name'] : null;
                //check if user uploaded an image again
                if ($image) {
                    $target_file = '../../'.REQUESTS_DIR . $_FILES['image']['name'];
                    //upload file
                    move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
                }
                
                //check if customer exists
                $customer = $db->SelectOne("SELECT * FROM customers WHERE cus_id = :cid AND user_id = :uid", ['cid' => $_POST['cus_id'], 'uid' => $user_id]);
                if (!$customer) {
                    doReturn(400, false, ["message" => "Customer does not exist"]);
                }
                
                //request id  
                $reqId = md5(time().$name.$customer['date_added']);
                $db->Insert("INSERT INTO requests (req_id, user_id, cus_id, name, extra_note, image, price, deadline) VALUES (:reqid, :uid, :cid, :name, :en, :image, :price, :deadline)", [
                    'reqid' => $reqId,
                    'uid' => $user_id,
                    'cid' => $_POST['cus_id'],
                    'name' => $name,
                    'en' => $extra_note,
                    'image' => $image,
                    'price' => $price,
                    'deadline' => $deadline
                ]);

                doReturn(200, true, ["message" => "Request created successfully"]);
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