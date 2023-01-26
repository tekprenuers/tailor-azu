<?php
require 'core/functions.php';
cors();

//Check if it is a post request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
            //verify JWT
            $user_id = verifyJWT();

            //check if user id exists
            $user = $db->SelectOne("SELECT * FROM users WHERE user_id = :uid", ['uid' => $user_id]);

            if (!$user) {
                doReturn(401, false, ["message" => "User does not exist"]);
            }
            
            doReturn(200, true, ["message" => "Token verified"]);

    } catch (Exception $e) {
        error_log($e);
        doReturn(500, false, ["message" => "A server error has occured"]);
    }
}else{
    doReturn(400, false, ["message" => "Invalid request method"]);
}
?>