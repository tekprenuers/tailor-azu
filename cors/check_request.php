<?php

//this script should run everyday

//This script works on customer's requests

require '../core/functions.php';

//get all requests
$requests = $db->SelectAll("SELECT customers.name AS cus_name, customers.phone AS cus_phone, requests.name AS request_name, requests.deadline, users.fname, customers.gender AS cus_gender, users.email AS user_email FROM requests INNER JOIN customers INNER JOIN users ON requests.user_id = users.user_id AND customers.user_id = users.user_id");

//loop through data
foreach($requests as $key => $request){
    $pronoun = ($request['cus_gender'] == "male") ? "him" : "her";
    //check if deadline will be due in 3 days
    //do that by adding 3 days to the current time and then comparing it 
    //with the stored deadline
    if(strtotime("+3 days", time()) <= intval($request['deadline'])){
        //send mail to user that license will expire in less than 3 days time

        $emailTemp = file_get_contents('../emails/request_close_to_deadline.html');
        $dynamic = array(
            "FNAME" => (!empty($request['fname'])) ? $request['fname'] : "Esteemed Client",
            "CUS_NAME" => $request['cus_name'],
            "CUS_PHONE" => $request['cus_phone'],
            "REQUEST_NAME" => $request['request_name'],
            "DEADLINE" => $request['deadline'],
            "PRONOUN" => $pronoun
        );
        //replace placeholders with actual values
        $body = doDynamicEmail($dynamic, $emailTemp);
        //send mail
        sendMail($request['user_email'], '', "Request Deadline Is Approaching", $body);
    }
}

//try to make it send the mails upto 3 times and not more to avoid bugging them
?>