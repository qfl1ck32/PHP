<?php
    session_start();

    if (!isset($_SESSION['isLogged']) || !$_SESSION['isLogged'])
        return Status(false, 'You are not logged in.');
    
    include 'functions.php';
    include 'mysql.php';

    if (c('firstName') || c('lastName') || c('dateOfBirth') || c('gender') || c('address') || c('country') || c('state') || c('city') || !isset($_FILES['file']))
        return Status(false, "Missing parameter.");

    $image = file_get_contents($_FILES['file']['tmp_name']);
    $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);

    if (!($ext == 'jpeg' || $ext == 'png' || $ext == 'jpg'))
        return Status(false, "You should have entered a valid image.");

    $existsPending = sendQuery('select count(*) as c from pendingpersonaldata where id = unhex(?)', $_SESSION['id'])[0];

    if ($existsPending['c'])
        return Status(false, "You already have a pending change.");

    file_put_contents("../Images/pendingPersonalDataImages/" . $_SESSION['id'] . ".png", $image);
    
    $putNewData = sendQuery('insert into pendingPersonalData values (unhex(?), ?, ?, ?, ?, ?, ?, ?, ?);', $_SESSION['id'], $_POST['firstName'], $_POST['lastName'], $_POST['dateOfBirth'], $_POST['gender'], $_POST['address'], $_POST['city'],
                            $_POST['state'], $_POST['country']);
    
    return Status(true, "You have succesfully made a change. You should now wait for approval.");
?>