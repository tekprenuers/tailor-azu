<?php
//set session 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
//require functions file
require_once(dirname(__FILE__) . '/../core/functions.php');
//require paystack autoload file
require_once(dirname(__FILE__) . '/../core/paystack/src/autoload.php');
//init paystack
$paystack = new Yabacon\Paystack(PAYSTACK_SECRET_KEY);
//check if token is supplied
if ($_SERVER['REQUEST_METHOD'] == "GET" && isset($_GET['token']) && !empty($_GET['token'])) {
    try {
        $Token = htmlspecialchars(urldecode($_GET['token']));
        //verify token
        $token = explode("::", $Token);
        $user_id = base64_decode($token[0]);
        $time = $token[1];
        // $premium = base64_decode( $token[2] );
        if (!$user_id || !$time || (time() > intval($time))) {
            $_SESSION['error'] = array(
                "code" => 400,
                "message" => "[INVALID_TOKEN] something is not right, please login to continue"
            );
            header("Location: error.php");
            //[INVALID_TOKEN] something is not right, please login to continue;
            exit();
        }
        //get the user
        $user = $db->SelectOne("SELECT * FROM users WHERE user_id  = :uid", ['uid' => $user_id]);
        if (!$user) {
            $_SESSION['error'] = array(
                "code" => 400,
                "message" => "[INVALID_USER_ID] Something is not right, please login to continue"
            );
            header("Location: error.php");
            //[INVALID_USER_ID] something is not right, please login to continue;
            exit();
        }
        //check if user has updated profile
        if (!checkUpdatedProfile($user)) {
            $_SESSION['error'] = array(
                "code" => 400,
                "message" => "[INCOMPLETE_PROFILE] Please update your profile and try again"
            );
            header("Location: error.php");
            //[INCOMPLETE_PROFILE] Please update your profile and try again;
            exit();
        }
        //generate txn reference
        $reference = 'tk-' . $user['fname'][0] . uniqid(time());
        //init transaction
        $tranx = $paystack->transaction->initialize([
            "amount" => '',
            'plan' => "PLN_k1iq8g9700z69zy",
            // The plan is created already
            'email' => "ugorji757@gmail.com",
            // unique to customers
            'reference' => $reference,
            // unique to transactions
            'callback_url' => BACKEND_URL . 'subscriptions/verify.php'
        ]);
        //store in database
        $db->Insert("INSERT INTO transactions (user_id, reference, payment_method, date_created) VALUES (:uid, :ref, :method, :date)", [
            'uid' => $user_id,
            'ref' => $reference,
            'method' => 'paystack',
            'date' => time()
        ]);
        // redirect to page so User can pay
        header('Location: ' . $tranx->data->authorization_url);
    } catch (Exception $e) {
        error_log($e);
        $_SESSION['error'] = array(
            "code" => 500,
            "message" => "[SERVER_ERROR] A server error has occured on " . gmdate('D M d, Y H:i:s:a', time())
        );
        header("Location: error.php");
        //return some error here
        //A server error has occured
        // header('Location: ' .FRONTEND_URL.'/license');
        exit();
    }
} else {
    $_SESSION['error'] = array(
        "code" => 400,
        "message" => "[INVALID_REQUEST] Something went wrong. Please try to login again"
    );
    header("Location: error.php");
    exit();
    // [INVALID_REQUEST] Something went wrong. Please try to login again
}
?>