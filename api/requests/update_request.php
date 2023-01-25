<?php
/*
// Updates an already existing request
*/

require '../../core/functions.php';
cors();

//use octavalidate
use Validate\octaValidate;

//create new instance
$myForm = new octaValidate('form_upd_request', OV_OPTIONS);
//define rules for each form input name
$valRules = array(
    "token" => array(
        ["R", "A token is required"]
    ),
    "req_id" => array(
        ["R", "Request ID is required"],
        ["ALPHA_NUMERIC", "Request ID must contain alphabets or numbers"]
    ),
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
    "completed" => array(
        ["ALPHA_ONLY", "Completed must be alphabets only"]
    ),
    "price" => array(
        ["DIGITS"]
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

                //check if request exists
                $request = $db->SelectOne("SELECT * FROM requests WHERE requests.user_id = :uid AND requests.req_id = :id", ['uid' => $user_id, 'id' => $_POST['req_id']]);

                if (!$request) {
                    doReturn(400, false, ["message" => "This Request does not exist"]);
                }

                //default vars
                $image = $price = $extra_note = $deadline = $name = $completed = null;

                //reassign variables
                $price = (isset($_POST['price']) && !empty($_POST['price'])) ? $_POST['price'] : $request['price'];
                $extra_note = (isset($_POST['extra_note']) && !empty($_POST['extra_note'])) ? $_POST['extra_note'] : $request['extra_note'];
                $name = (isset($_POST['name']) && !empty($_POST['name'])) ? $_POST['name'] : $request['name'];
                $deadline = (isset($_POST['deadline']) && !empty($_POST['deadline'])) ? strtotime($_POST['deadline']) : $request['deadline'];
                
                //check if due date is in the past
                if(time() > $deadline)  doReturn(400, false, ["message" => "Due date must not be in the past"]);

                $completed = (isset($_POST['completed']) && !empty($_POST['completed'])) ? $_POST['completed'] : $request['completed'];
                //check if user uploaded an image
                $image = (isset($_FILES['image']) && !empty($_FILES['image'])) ? $_FILES['image']['name'] : $request['image'];
                //check if user uploaded an image again
                if ((isset($_FILES['image']) && !empty($_FILES['image']))) {
                    //delete previous profile
                    if(!empty($request['image']) && file_exists(PROFILE_DIR.$request['image'])){
                        unlink(PROFILE_DIR.$request['image']);
                    }
                    $target_file = '../../'.REQUESTS_DIR . $_FILES['image']['name'];
                    //upload file
                    move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
                }

                $db->Update("UPDATE requests SET name = :name, extra_note = :en, image = :image, price = :price, deadline = :deadline, is_completed = :completed WHERE id = :id", [
                    'id' => $request['id'],
                    'name' => $name,
                    'en' => $extra_note,
                    'image' => $image,
                    'price' => $price,
                    'deadline' => $deadline,
                    'completed' => $completed
                ]);

                doReturn(200, true, ["message" => "Request updated successfully"]);
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