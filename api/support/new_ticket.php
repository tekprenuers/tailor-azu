<?php
//creates a ticket for users logged into the dashboard

require '../../core/functions.php';
cors();

//use octavalidate
use Validate\octaValidate;

//create new instance
$myForm = new octaValidate('', OV_OPTIONS);
//define rules for each form input name
$valRules = array(
    "title" => array(
        ["R", "Title is required"],
        ["TEXT", "Title contains invalid characters"]
    ),
    "category" => array(
        ["R", "Category is required"],
        ["ALPHA_ONLY", "Category contains invalid characters"]
    ),
    "desc" => array(
        ["R", "Description is required"],
        ["TEXT", "Description contains invalid characters"]
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

                //default vars
                $image = $title = $desc = $category = null;

                //reassign variables
                $title = $_POST['title'];
                $desc =  $_POST['desc'];
                $category =  $_POST['category'];

                //check if user uploaded an image
                $image = (isset($_FILES['image']) && !empty($_FILES['image'])) ? $_FILES['image']['name'] : null;
                //check if user uploaded an image again
                if ($image) {
                    $target_file = '../../'.SUPPORT_DIR . $_FILES['image']['name'];
                    //upload file
                    move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
                }
                $db->Insert("INSERT INTO tickets (user_id, category, image, title, description, date_created) VALUES (:uid, :cat, :image, :title, :desc, :date)", [
                    'uid' => $user_id,
                    'cat' => $category,
                    'image' => $image,
                    'title' => $title,
                    'desc' => $desc,
                    'date' => time()
                ]);

                doReturn(200, true, ["message" => "Your message was sent successfully"]);
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