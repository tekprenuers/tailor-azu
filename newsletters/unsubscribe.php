<?php
//set session 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require(dirname(__FILE__) . '/../core/functions.php');

//store form response
$response = array(
    "success" => false,
    "message" => null
);

$user_id = null;

if (isset($_GET) && !empty($_GET['uid'])) {
    $user_id = htmlspecialchars($_GET['uid']);
    //check if ID is well formatted
    if (!$user_id || !preg_match("/^[a-z0-9]+$/", $user_id)) {
        $response = ["success" => false, "message" => "Sorry, we couldn't process this request because it is malformed"];
    }
} else {
    $response = ["success" => false, "message" => "Sorry, we couldn't process this request because it is malformed"];
}

//unsubscribe the user
if (isset($_POST) && !empty($_POST['uid']) && !empty($_POST['reason'])) {
    $user_id = htmlspecialchars($_POST['uid']);
    $reason = htmlspecialchars($_POST['reason']);
    //check if ID is well formatted
    if (!$user_id || !preg_match("/^[a-z0-9]+$/", $user_id)) {
        $response = ["success" => false, "message" => "Sorry, we couldn't process this request because it is malformed"];
    }
    $user = $db->SelectOne("SELECT * FROM users WHERE user_id = :uid", [
        'uid' => $user_id
    ]);

    if (!$user) {
        $response = ["success" => false, "message" => "Sorry, the ID provided is invalid"];
    }

    if (empty($response['message'])) {
        $db->Update("UPDATE newsletters SET is_subscribed = :sub, reason = :reason, last_updated = :date WHERE email = :email", [
            'email' => $user['email'],
            'sub' => 'No',
            'reason' => $reason,
            'date' => time()
        ]);
        //return success
        $response = ["success" => true, "message" => "You have been removed from our mailing list"];
    }
}
//9a774dca203306f58f17e8c2e7f0739f
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
    <title>Unsubscribe | TailorsKit</title>
    <style>
        .mw-500 {
            max-width: 500px !important;
        }

        .radius-0 {
            border-radius: 0 !important;
        }

        .has-shadow {
            box-shadow: 20px 20px 60px #bebebe, -20px -20px 60px #ffffff !important;
        }

        .should-be-centered {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: calc(100% - 50px);
            max-width: 500px;
            /* position: relative;
            top: 50%;
            left: 50%;
            transform: translate(-50%, 50%); */
        }
    </style>
</head>

<body>
    <section class="mw-500 has-shadow p-4 should-be-centered">
        <?php
        if (
            isset($response['success']) && is_bool($response['success'])
            && isset($response['message']) && !empty($response['message'])
        ) {
            if ($response['success'] === true):
        ?>
        <p class="alert alert-success mb-3 radius-0 text-center fw-bold">
            <?php print($response['message']); ?>
        </p>
        <script>
            setTimeout(() => {
                window.location.href = "<?php print(FRONTEND_URL); ?>"
            }, 3000)
        </script>
        <?php elseif ($response['success'] === false):

        ?>
        <p class="alert alert-danger mb-3 radius-0 text-center fw-bold">
            <?php print($response['message']); ?>
        </p>
        <?php endif;
        }
        ?>
        <?php if (!$response['success'] && empty($response['message'])): ?>
        <div class="text-center mb-3">
            <img width="150px" alt="Tailorskit logo"
                src="<?php print STATIC_ASSETS_URL . '/img/tailorskit-logo.png'; ?>" />
        </div>
        <h4 class="text-center mb-3">NewsLetters</h4>
        <form id="form_unsubscribe" method="post" novalidate>
            <input id="inp_uid" octavalidate="R,ALPHA_NUMERIC" type="hidden" name="uid"
                value="<?php print($user_id); ?>" />
            <div class="mb-3 alert alert-danger">
                <p class="mb-0">You are about to stop receiving all Emails related to marketing & product updates </p>
            </div>

            <div class="mb-3">
                <label class="form-label">Please tell us why you wish to unsubscribe <span
                        class="text-danger">*</span></label>
                <textarea name="reason" id="inp_reason" class="form-control" maxlength="200" octavalidate="R,TEXT"
                    placeholder="I wish to unsubscribe because ..."></textarea>
            </div>
            <div class="mb-3">
                <button type="submit" form="form_unsubscribe" class="btn btn-danger">Unsubscribe</button>
            </div>
        </form>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelector('#form_unsubscribe').addEventListener('submit', (e) => {
                    const v = new octaValidate('form_unsubscribe', {
                        strictMode: true
                    });
                    if (!v.validate()) {
                        e.preventDefault()
                    } else {
                        e.currentTarget.submit();
                    }
                })
            })
        </script>
        <?php endif; ?>
    </section>
</body>

</html>