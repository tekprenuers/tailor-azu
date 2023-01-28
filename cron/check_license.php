<?php

//this script should run everyday

//sends a mail or text if the user's license has expired

require '../core/functions.php';

//get all users
$users = $db->SelectAll("SELECT * FROM users");

//loop through data
foreach($users as $key => $user){
    //check if license will be due in 3 days
    //do that by adding 3 days to the current time and then comparing it 
    //with the expiry time of the user's license
    if(strtotime("+3 days", time()) <= intval($user['expiry'])){
        //send mail to user that license will expire in less than 3 days time

        $emailTemp = file_get_contents('../emails/license_about_to_expire.html');
        $dynamic = array(
            "FNAME" => (!empty($user['fname'])) ? $user['fname'] : "Esteemed Client",
            'EXPIRY_END_DATE' => gmdate("d M Y", intval($user['expiry'])),
            "UID" => $user['user_id']
        );
        //replace placeholders with actual values
        $body = doDynamicEmail($dynamic, $emailTemp);
        //send mail
        sendMail($user['email'], '', "Renew Your License Before It Expires", $body);
    }  
    //check if license is not active at all
    elseif(!activeLicense($user['expiry'])){
        //send email to user that license has expired

        $emailTemp = file_get_contents('../emails/license_expired.html');
        $dynamic = array(
            "FNAME" => (!empty($user['fname'])) ? $user['fname'] : "Esteemed Client",
            "UID" => $user['user_id']
        );
        //replace placeholders with actual values
        $body = doDynamicEmail($dynamic, $emailTemp);
        //send mail
        sendMail($user['email'], '', "Renew Your License", $body);
    }
}

//try to make it send the mails upto 3 times and not more to avoid bugging them
?>