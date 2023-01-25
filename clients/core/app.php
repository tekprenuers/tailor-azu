<?php

//set session 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//check if user is logged in
if(!isset($_SESSION['user']) || empty($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

//delete session if time has expired
if(isset($_SESSION['user']) && !empty($_SESSION['user']['token'])){
    $token = $_SESSION['user']['token'];
    //get the user id
    $user_id = base64_decode(explode('::', $_SESSION['user']['token'])[0]);
    //get the time
    $time = explode('::', $_SESSION['user']['token'])[1];
    //check if is has expired
    if(time() >= $time){
        unset($_SESSION['user']);
        header("Location: login.php");
        exit();
    }
}