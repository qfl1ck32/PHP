<?php
    session_start();

    if (!isset($_SESSION['isLogged']) || !$_SESSION['isLogged'])
        return Status(false, 'You are not logged in.');
    
    include 'functions.php';
    include 'mysql.php';

    if (c('firstName') || c('lastName') || c('dateOfBirth') || c('gender') || c('address') || c('country') || c('state') || c('city') || !isset($_FILES['file']))
        return Status(false, "Missing parameter.");

    $image = file_get_contents($_FILES['file']['tmp_name']);

    $existsPending = sendQuery('select count(*) as c from pendingpersonaldata where id = unhex(?)', $_SESSION['id'])[0];

    if ($existsPending['c'])
        return Status(false, "You already have a pending change.");
    
    $putNewData = sendQuery('insert into pendingPersonalData values (unhex(?), ?, ?, ?, ?, ?, ?, ?, ?, ?);', $_SESSION['id'], $_POST['firstName'], $_POST['lastName'], $_POST['dateOfBirth'], $_POST['gender'], $_POST['address'], $_POST['city'],
                            $_POST['state'], $_POST['country'], $image);
    
    return Status(true, "You have succesfully made a change. You should now wait for approval.");
?>