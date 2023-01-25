<?php
//set session 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(dirname(__FILE__) . '/../core/functions.php');
//store form response
$error = array(
    "code" => 0,
    "message" => ""
);

// var_dump($_SESSION);
//reassign values
if (isset($_SESSION['error']) && !empty($_SESSION['error'])) {
    $error['code'] = $_SESSION['error']['code'];
    $error['message'] = $_SESSION['error']['message'];
    //delete session
    unset($_SESSION['error']);
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
    <link href="./assets/css/clients.css" rel="stylesheet" />
    <title>PAYMENT ERROR | TailorsKit</title>
    <style>
        .mw-700 {
            max-width: 700px !important;
        }

        .radius-0 {
            border-radius: 0 !important;
        }

        .has-shadow {
            box-shadow: 20px 20px 60px #bebebe, -20px -20px 60px #ffffff !important;
        }
    </style>
</head>

<body>
    <section class="mw-700 m-auto p-4 should-be-centered">
        <?php
        if (
            isset($error['code']) && !empty($error['code'])
            && isset($error['message']) && !empty($error['message'])
        ) {
            if ($error['code'] === 500):
        ?>
        <p class="mb-3">Please send us a screenshot of the error message below</p>
        <section class="alert alert-danger mb-3 radius-0">
            <h5 class="fw-bold">Server Error</h5>
            <p class="mb-0">
                <?php print($error['message']); ?>
            </p>
            <div>
                <a href="<?php print(FRONTEND_URL . '/login'); ?>" class="btn btn-danger mt-3">Go Back</a>
            </div>
        </section>
        <?php else:

        ?>
        <section class="alert alert-danger mb-3 radius-0">
            <h5 class="fw-bold">Payment Error</h5>
            <p class="mb-0">
                <?php print($error['message']); ?>
            </p>
            <div>
                <a href="<?php print(FRONTEND_URL . '/login'); ?>" class="btn btn-danger mt-3">Try Again</a>
            </div>
        </section>
        <?php endif;
        }
        ?>
    </section>
</body>

</html>