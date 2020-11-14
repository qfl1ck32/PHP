<?php
    include 'mysql.php';
    include 'functions.php';
    include 'constants.php';

    if (!isset($_POST['data']))
        return Status(false, 'Missing parameter.');

    if (!$_POST['data'])
        return Status(false, 'Missing credential.');

    $ans = sendQuery('select count(*) as c from users where username = ? or email = ?;', $_POST['data'], $_POST['data'])[0]['c'];

    if (!$ans)
        return Status(false, 'There is no user with the given credentials.');

    $ans = sendQuery('select hex(id) as id, username, email from users where username = ? or email = ?;', $_POST['data'], $_POST['data'])[0];

    $id = $ans['id'];
    $username = $ans['username'];
    $email = $ans['email'];

    $exists = sendQuery('select count(*) as c from toRecover where id = unhex(?)', $id)[0]['c'];
    
    $currentTime = time();
    $token = bin2hex(openssl_random_pseudo_bytes(64));
    $newExpiry = time() + $resetPasswordExpiry;

    if ($exists) {
        $expiry = sendQuery('select expiry from toRecover where id = unhex(?);', $id)[0]['expiry'];

        if ($expiry > $currentTime) {
            $difference = $expiry - $currentTime;
            $time = gmdate("i:s", $difference);
            $arg = "'" . $email . "'";
            return Status(false, 'There has already been sent a recovery e-mail to you.<br>Please <a href="#" onclick="recoveryButton.click()">try again</a> in ' . $time . '.');
        }

        else {
            sendQuery('update toRecover set token = ?, expiry = ? where id = unhex(?);', $token, $newExpiry, $id);
        }
        
    }

    else {
        sendQuery('insert into toRecover values (unhex(?), ?, ?);', $id, $token, $newExpiry);
    }
    
    $data = array(
        'username' => $username,
        'message' => 'reset your password',
        'url' => $host . '/API/verify.php?pwtoken=' . $token
    );

    $emailHTML = getConfirmationEmail($data);

    $data = array(
        'email' => $email,
        'subject' => $resetPasswordSubject,
        'html' => $emailHTML
    );

    sendVerificationEmail($data);


    return Status(true, 'We have succesfully sent you a recovery link on your e-mail.');
?>