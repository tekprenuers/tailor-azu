<?php
//set session 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//check if session exists
if(isset($_SESSION['user']) && !empty($_SESSION['user'])){
    header("Location: renew.php");
    exit();
}

require(dirname(__FILE__). '/../core/functions.php');

//store form response
$formResponse = array(
    "success" => null,
    "message" => null
);

// var_dump($_SESSION);
//reassign values
if (isset($_SESSION['formResponse']) && !empty($_SESSION['formResponse'])) {
    $formResponse['success'] = $_SESSION['formResponse']['success'];
    $formResponse['message'] = $_SESSION['formResponse']['message'];
    //delete session
    unset($_SESSION['formResponse']);
}

// var_dump($formResponse);
//use octavalidate
use Validate\octaValidate;

//create new instance
$myForm = new octaValidate('form_login', OV_OPTIONS);
//define rules for each form input name
$valRules = array(
    "pass" => array(
        ["R", "Your password is required"],
        ["MINLENGTH", 8, "Your password must have a minimum of 8 characters"]
    ),
    "email" => array(
        ["R", "Your Email Address is required"],
        ["EMAIL", "Your Email Address is invalid!"]
    )
);
//Check if it is a post request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        //begin validation    
        if ($myForm->validateFields($valRules, $_POST) === true) {

            //check if email is registered already
            $user = $db->SelectOne("SELECT * FROM users WHERE email = :email", ['email' => $_POST['email']]);

            if (!$user) {
                $_SESSION['formResponse'] = ["success" => false, "message" => "User does not exist"];
                header("Location: login.php") . exit();
            }

            //compare password
            if (password_verify($_POST['pass'], $user['pass']) === false) {
                $_SESSION['formResponse'] = ["success" => false, "message" => "You have provided an invalid password"];
                header("Location: login.php") . exit();
            } else {
                //store token and set expiry time to 1 hour
                //user_id, expiry_time,
                $loggedInToken = base64_encode($user['user_id']) . '::' . strtotime("+1 hour", time());
                $_SESSION['user'] = array(
                    "token" => $loggedInToken,
                    "fname" => (!empty($user['fname'])) ? $user['fname'] : null,
                    "lname" => (!empty($user['lname'])) ? $user['lname'] : null,
                    "email" => (!empty($user['email'])) ? $user['email'] : null
                );
                //set response
                $_SESSION['formResponse'] = ["success" => true, "message" => "Login successful"];
                header("Location: login.php") . exit();
            }
        } else {
            //return errors  
            // doReturn(400, false, ["formError" => $myForm->getErrors()]);
            $_SESSION['formResponse'] = ["success" => false, "message" => "Form validation error"];
            header("Location: login.php") . exit();
        }
    } catch (Exception $e) {
        error_log($e);
        // doReturn(500, false, ["message" => "A server error has occured"]);
        $_SESSION['formResponse'] = ["success" => false, "message" => "A sever error has occrued"];
        header("Location: login.php") . exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <script src="https://unpkg.com/octavalidate@latest/native/validate.js"></script>
    <link href="./assets/css/clients.css" rel="stylesheet" />
    <title>Login | TailorsKit</title>
</head>

<body>
    <section class="mw-500 has-shadow p-4 should-be-centered">
        <?php 
        if (
            isset($formResponse['success']) && is_bool($formResponse['success'])
            && isset($formResponse['message']) && !empty($formResponse['message'])
        ) {
            if ($formResponse['success'] === true):
        ?>
        <p class="alert alert-success mb-3 radius-0 text-center">
            <?php print($formResponse['message']); ?>
        </p>
        <?php elseif ($formResponse['success'] === false):

        ?>
        <p class="alert alert-danger mb-3 radius-0 text-center">
            <?php print($formResponse['message']); ?>
        </p>
        <?php endif;
        }
        ?>
        <div class="text-center mb-3">
            <img width="150px" alt="Tailorskit logo" src="<?php print STATIC_ASSETS_URL.'/img/tailorskit-logo.png'; ?>" />
        </div>
        <form id="form_login" method="post" novalidate>
            <div class="mb-3">
                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                <input name="email" type="email" octavalidate="R,EMAIL" id="inp_email" class="form-control" />
            </div>
            <div class="mb-3">
                <label class="form-label">Password <span class="text-danger">*</span></label>
                <input name="pass" type="password" octavalidate="R" id="inp_pwd" class="form-control" />
            </div>
            <div class="mb-3">
                <button type="submit" form="form_login" class="btn btn-app-primary w-100 radius-0">Login</button>
                <p class="mt-3">Don't have an account? <a href="<?php print(FRONTEND_URL.'/register'); ?>">Click here to create one. </a></p>
            </div>
        </form>
    </section>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
        crossorigin="anonymous"></script>

    <script>
        window.addEventListener('load', () => {
            const $ = (elem) => document.querySelector(elem);
            const $$ = (elem) => document.querySelectorAll(elem);

            //the login form
            $('#form_login').addEventListener('submit', (e) => {

                const ov = new octaValidate('form_login', {
                    strictMode: true
                });

                if (!ov.validate()) {
                    e.preventDefault();
                } else {
                    e.currentTarget.submit();
                }
            })
        })
    </script>
</body>

</html>