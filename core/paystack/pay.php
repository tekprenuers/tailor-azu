<?php

require_once (dirname(__FILE__) . '/../functions.php');

require 'src/autoload.php';

//abort if user is not logged in
//add monthly + 3 days
//quarterly +5 days
if(!checkLogin()){
	header("Location: https://givemeastar.com/login?next=pricing#pay-with-paystack");
    exit();
}
if(empty($user)){
    header("Location: https://givemeastar.com/login?next=pricing#pay-with-paystack");
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && 
	isset($_POST['plan']) && 
	!empty($_POST['plan'])){

	$plan = filter_var($_POST['plan'], FILTER_SANITIZE_STRING);

	switch($plan){
        case "Monthly" :
        $amt = 10841;
        break;

        case "Quarterly" :
        $amt = 41571;
        break;

        default :
        $amt = 41571;            
        break;
    }

    if($plan !== 'Monthly' && $plan !== 'Quarterly'){
    	$plan = 'Quarterly';
    }

    $email = $user['user_email'];
    $amount = $amt * 100;
    $reference = $user['username'][0].uniqid(time());

	$paystack = new Yabacon\Paystack(PAYSTACK_SECRET_KEY);
	try
	{
    $tranx = $paystack->transaction->initialize([
        'plan'=>"PLN_k1iq8g9700z69zy",       // The plan created already
        'email'=>$email,         // unique to customers
        'reference'=>$reference, // unique to transactions
    ]);
	} catch(\Yabacon\Paystack\Exception\ApiException $e){
      print_r($e->getResponseObject());
      die($e->getMessage());
    }

    // store transaction reference so we can query in case user never comes back
    // perhaps due to network issue
    //save_last_transaction_reference($tranx->data->reference);

    customInsert("INSERT INTO invoices (email, reference, plan, status, date) 
    	VALUES (:e, :r, :p, :s, :d)",
    	['e' => $user['user_email'],
    	'r' => $tranx->data->reference,
    	'p' => $plan,
    	's' => 'Pending',
    	'd' => gmdate("Y-m-d", time())
    	]
    );

    // redirect to page so User can pay
    header('Location: ' . $tranx->data->authorization_url);

}else{
	header("Location: https://givemeastar.com/pricing#pay-with-paystack");
    exit();
}
?>