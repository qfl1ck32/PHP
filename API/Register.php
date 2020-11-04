<?php
    include 'mysql.php';
    include 'functions.php';
    include 'constants.php';

    session_start();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(404);
        include('404.php');
        die();
    }

    if (c('username') || c('email') || c('password') || c('confirmPassword'))
        return ext('emptyfields');

    if ($_POST['password'] != $_POST['confirmPassword'])
        return ext('differentPasswords');

    $patternEmail = '/\S+@\S+\.\S+/';
    $patternUsername = '/^.{2,15}$/';

    if (!preg_match($patternUsername, $_POST['username']))
        return ext('usernamePattern');
    
    if (!preg_match($patternEmail, $_POST['email']))
        return ext('emailPattern');


    $dataExists = sendQuery("select count(*) as c from users where username = ? or email = ?;", $_POST['username'], $_POST['email']);

    if ($dataExists[0]['c'])
        return ext('userexists');
    
    $uuid = strtoupper(bin2hex(random_bytes(16)));
    $token = bin2hex(openssl_random_pseudo_bytes(64));

    $data = array(
        'username' => $_POST['username'],
        'message' => 'activate your account',
        'url' => $host . '/API/verify.php?emtoken=' . $token
    );

    $confirmationEmail = getConfirmationEmail($data);

    $hashedPassword = password_hash($_POST['password'], PASSWORD_BCRYPT); 

    sendQuery('insert into users values (unhex(?), ?, ?, ?, 0)', $uuid, $_POST['username'], $_POST['email'], $hashedPassword);
    
    $expiry = time() + $confirmationEmailExpiry;

    sendQuery('insert into toConfirm values (unhex(?), ?, ?);', $uuid, $token, $expiry);
    
    $data = array(
        'email' => $_POST['email'],
        'subject' => $confirmEmailSubject,
        'html' => $confirmationEmail
    );

    sendVerificationEmail($data);

    echo json_encode(array(
        'success' => true,
        'email' => $_POST['email']
    ));
?>