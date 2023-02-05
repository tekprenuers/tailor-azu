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
if ($_SERVER['REQUEST_METHOD'] == "GET" && isset($_GET['reference']) && !empty($_GET['reference'])) {
    try {
        $reference = htmlspecialchars($_GET['reference']);
        if(!$reference){
            $_SESSION['error'] = array(
                "code" => 400,
                "message" => "[REFERENCE_NOT_FOUND] Something is not right, please retry your payment again"
            );
            header("Location: error.php");
            exit();
        }
        //get the transaction
        $transData = $db->SelectOne("SELECT *, transactions.id AS trans_id, transactions.date_created AS trans_date FROM transactions INNER JOIN users ON users.user_id = transactions.user_id WHERE transactions.reference = :ref AND transactions.status = :stat" ,[
            'ref' => $reference,
            'stat' => "PENDING"
        ]);
        if(!$transData){
            $_SESSION['error'] = array(
                "code" => 400,
                "message" => "[REFERENCE_IS_INVALID] Something is not right, please retry your payment again"
            );
            header("Location: error.php");
            exit();
        }
        //init verify method
        $tranx = $paystack->transaction->verify([
            'reference'=>$reference, // unique to transactions
        ]);
        //check status of transaction
        if ('success' === $tranx->data->status) {
            //update transaction 
            $db->Update("UPDATE transactions SET status = :stat WHERE id = :id", [
                'stat' => "PAID",
                'id' => $transData['trans_id']
            ]);
            //users expiry time
            $expiry = intval($transData['expiry']);
            //check if previous license expired
            if(time() >= $expiry){
                //reset expiry
                $expiry = strtotime("+1 year", time());
            }else{
                //previous license did not expire, so roll over
                $expiry = strtotime("+1 year", $expiry);
            }
            //update user
            $db->Update("UPDATE users SET expiry = :expiry WHERE user_id = :user_id", [
                'expiry' => $expiry,
                'user_id' => $transData['user_id']
            ]);
            ///////////////////////////////send mail
            
            $emailTemp = file_get_contents('../emails/license_renewed.html');
            $dynamic = array(
                "FNAME" => (!empty($transData['fname'])) ? $transData['fname'] : "Esteemed Client",
                "EXPIRY_END_TIME" => gmdate("d M Y", $expiry),
                "UID" => $transData['user_id']
            );
            //replace placeholders with actual values
            $body = doDynamicEmail($dynamic, $emailTemp);
            //send mail
            sendMail($transData['email'], $transData['fname'], "Your License Has Been Renewed", $body);
            //go back to dashboard
            $dashUrl = FRONTEND_URL."/dashboard/license?success=true&ref=$reference";
            header("Location: $dashUrl");
            exit();
        }else{
            //update transaction 
            $db->Update("UPDATE transactions SET status = :stat WHERE id = :id", [
                'stat' => "FAILED",
                'id' => $transData['trans_id']
            ]);
            ///////////////////////////////send mail
            
            $emailTemp = file_get_contents('../emails/failed_payment.html');
            $dynamic = array(
                "FNAME" => (!empty($transData['fname'])) ? $transData['fname'] : "Esteemed Client",
                "REFERENCE" => $reference,
                "TIME" => gmdate("d M Y", intval($transData['trans_date'])),
                "UID" => $transData['user_id']
            );
            //replace placeholders with actual values
            $body = doDynamicEmail($dynamic, $emailTemp);
            //send mail
            sendMail($transData['email'], $transData['fname'], "Payment Unsuccessful", $body);
            //return error
            $_SESSION['error'] = array(
                "code" => 400,
                "message" => "[PAYMENT_FAILED] Your payment was unsuccessful. Reference ID: $reference"
            );
            header("Location: error.php");
            exit();
        }
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