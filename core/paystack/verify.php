<?php

require_once (dirname(__FILE__) . '/../../app.php');
require 'src/autoload.php';

//abort if user is not logged in

if(!checkLogin()){
	header("Location: login?next=renew");
    exit();
}

if(empty($user)){
    header("Location: login?next=renew");
    exit();
}

$reference = isset($_GET['reference']) ? $_GET['reference'] : '';
if(!$reference){
    die('No reference supplied');
}

// initiate the Library's Paystack Object
$paystack = new Yabacon\Paystack('sk_live_5f5da6e836da1e6f7a3a4be85b1575a1b3acb9fa');
try
{
// verify using the library
$tranx = $paystack->transaction->verify([
    'reference'=>$reference, // unique to transactions
]);
      
} catch(\Yabacon\Paystack\Exception\ApiException $e){
   	print_r($e->getResponseObject()); 
    die($e->getMessage());
    //$err = base64_encode("A Fatal Error has occured");
    //$url = "http://localhost/gmas/payment-status?error=".$err;
    //header("Location: $url");
}

if ('success' === $tranx->data->status) {
	//var_dump(json_encode($tranx));
	//exit();
      
	//Query transaction
	$email = $user['user_email'];
    $trans = customQuery("SELECT * FROM invoices WHERE email = :email AND status = :sta", ['email' => $email, 'sta' => 'Pending'], 'single');
      
    if(!$trans){
        //ref does not exist or status is already verified or ref does not belong to customer
        $err = base64_encode("Invalid Transaction!");
        $url = "https://givemeastar.com/paystack/status?error=".$err;
        header("Location: $url");
        exit();
    }else{
        //ref exists and belongs to the customer
        
        switch($trans['plan']){
            case "Monthly" :
            $amt = 10841;
            $days = "+33 days";
            $next_up_tmp =strtotime($days, time());
            break;

            case "Quarterly" :
            $amt = 41571;
            $days="+129 days";
            $next_up_tmp =strtotime($days, time());
            break;

            default :
            $err = base64_encode("Invalid Plan!");
            $url = "https://givemeastar.com/paystack/status?error=".$err;
            header("Location: $url");
            break;
        }

        $body = '
     <!doctype>
<html>

<head>
    <style>
        @import url(\'https://fonts.googleapis.com/css2?family=Lora&family=Mukta:wght@200&display=swap\');

        body {
            font-family: \'Lora\', serif;
        }
    </style>

</head>

<body style="margin:0px;max-height:100%">
    <div>
        <img src="https://static.givemeastar.com/images/gmas.jpg" width="40px">
        <div style="margin-top:20px;">
            <h2 style="margin:10px 0px 0px;text-align:center">THANK YOU</h2>
            <p style="margin:0px 0px;color:#6a6a6a" align="center">Payment Successful</p>
            <section style="padding:20px">
                <article style="margin: 10px 0px 10px 0px;">
                    <p style="font-size:1.2em">Hello <b>'.$user['username'].',</b></p>
                    <p style="margin-top:5px;font-size:1.2em">Your <b>'.$plan.'</b> License has been renewed with an additional <b>'.str_replace('+', '', $days).'</b> added to your Account.</b></p>
                    <p style="font-size:1.2em">If your widgets were <b>disabled</b>, they will be enabled automatically. You don\'t need to contact support.</p>
                    <div align="center" class="button-container" style="padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px;">
                        <!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-spacing: 0; border-collapse: collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;"><tr><td style="padding-top: 10px; padding-right: 10px; padding-bottom: 10px; padding-left: 10px" align="center"><v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="" style="height:31.5pt;width:129.75pt;v-text-anchor:middle;" arcsize="10%" stroke="false" fillcolor="#00d1b2"><w:anchorlock/><v:textbox inset="0,0,0,0"><center style="color:#ffffff; font-family:Arial, sans-serif; font-size:16px"><![endif]-->
                        <div
                            style="text-decoration:none;display:inline-block;color:#ffffff;background-color:#00d1b2;border-radius:4px;-webkit-border-radius:4px;-moz-border-radius:4px;width:auto; width:auto;;border-top:1px solid #00d1b2;border-right:1px solid #00d1b2;border-bottom:1px solid #00d1b2;border-left:1px solid #00d1b2;padding-top:5px;padding-bottom:5px;font-family:Arial, Helvetica Neue, Helvetica, sans-serif;text-align:center;mso-border-alt:none;word-break:keep-all;">
                            <span style="padding-left:20px;padding-right:20px;font-size:16px;display:inline-block;letter-spacing:undefined;"><span style="font-size: 16px; line-height: 2; word-break: break-word; mso-line-height-alt: 32px;"><a
                                        href="https://givemeastar.com/login" style="text-decoration:none;color:#fff">LOGIN NOW</a></span></span></div>
                        <!--[if mso]></center></v:textbox></v:roundrect></td></tr></table><![endif]-->
                    </div>
                    <p style="margin-top:10px;text-align:center;">Thank you for choosing GMAS</p>
                </article>
            </section>
        </div>

    </div>
</body>

</html>
';
        
        $next_up;
        $amount = $amt *100;
        //if some days are left
        if(time() > strtotime($user['next_up'])){
            $next_up = gmdate("Y-m-d H:i:s", $next_up_tmp);
        }else{
            $next_up = gmdate("Y-m-d H:i:s", strtotime($days, strtotime($user['next_up'])));
        }
        //https://givemeastar.com/paystack/verify?trxref=S164777944362371e73b37d2&reference=S164777944362371e73b37d2
        //var_dump($tranx->data->amount === $amount);
        //var_dump($tranx->customer->email === $user['user_email']);
        //var_dump($tranx->customer->email);
        //exit();
        if(($tranx->data->amount === $amount) && 
        	($tranx->data->customer->email === $user['user_email'])){

        	//Update transaction
        	$updSql = "UPDATE invoices SET status = :status WHERE id = :id";
        	$updTrans = customUpdate($updSql, ['status' => 'Success', 'id' => $trans['id']]);
            
        	//Update license
        	$licSql = "UPDATE users SET plan = :plan, next_up = :next_up WHERE id = :id";
        	$updLic = customUpdate($licSql, ['plan' => $trans['plan'], 'id' => $user['id'], 'next_up' => $next_up]);
        	
        	//rewrite session
        	$_SESSION['user_data']['plan'] = $trans['plan'];
            $_SESSION['user_data']['has_tried_pro'] = "Yes";
            $_SESSION['user_data']['next_up'] = $next_up;
            
        	//create a function for mail templates then send mail contnt
        	//SEND MAIL
        	$sendMail = sendMail($user['user_email'], $user['username'], 'License Renewal', $body, 'accounts@givemeastar.com', 'GMAS');
            
        	$url = "https://givemeastar.com/license";
        	header("Location: $url");
        	exit();
        }else{
        	$err = base64_encode('Invalid Transaction. Please contact support');
        	$url = "https://givemeastar.com/paystack/status?error=".$err;
         	header("Location: $url");
        	exit(); //verification failed
        }
      }
    }else{
        $err = base64_encode('Payment Not Succsesful');
        $url = "https://givemeastar.com/paystack/status?error=".$err;
         header("Location: $url");
        exit(); //verification failed
    }
?>