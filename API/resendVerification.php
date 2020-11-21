<?php
    include 'mysql.php';
    include 'functions.php';
    include 'constants.php';

    if (!isset($_POST['email']))
        die('Missing parameter.');

    $data = sendQuery('select hex(id) as id, username from users where email = ?;', $_POST['email'])[0];
    $id = $data['id'];
    $username = $data['username'];
    $exists = sendQuery('select count(*) as count from toConfirm where id = unhex(?);', $id);

    if (!isset($exists[0]))
        die('Account already verified.');

    $currentTime = time();
    
    $expiry = sendQuery('select expiry from toConfirm where id = unhex(?);', $id);

    if (!isset($expiry[0]))
        die('Account already verified.');

    $expiry = $expiry[0]['expiry'];

    if ($expiry > $currentTime) {
        $difference = $expiry - $currentTime;
        $time = gmdate("i:s", $difference);
        $arg = "'" . $_POST['email'] . "'";
        die('There has already been sent a verification e-mail to you. Please <b><a href = "#" class="resendLink" onclick = "resendVerification(' . $arg . ')">try again</a></b> in ' . $time . '.');
    }

    $token = bin2hex(openssl_random_pseudo_bytes(64));
    $expiry = time() + $confirmationEmailExpiry;

    sendQuery('update toConfirm set token = ?, expiry = ? where id = unhex(?);', $token, $expiry, $id);

    $data = array(
        'username' => $username,
        'message' => 'activate your account',
        'url' => $host . '/API/verify.php?emtoken=' . $token,
        'type' => 'emailConfirmation'
    );

    $confirmationEmail = getConfirmationEmail($data);

    $data = array(
        'email' => $_POST['email'],
        'subject' => $confirmEmailSubject,
        'html' => $confirmationEmail
    );

    sendVerificationEmail($data);

    echo 'Done! A new link has been sent to your e-mail. :)';
?>