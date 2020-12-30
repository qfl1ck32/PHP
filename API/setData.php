<?php
    session_start();

    if (!isset($_SESSION['isLogged']) || !$_SESSION['isLogged'])
        return Status(false, 'You are not logged in.');
    
    include 'functions.php';
    include 'mysql.php';

    if (c('firstName') || c('lastName') || c('dateOfBirth') || c('gender') || c('address') || c('countryId') || c('stateId') || c('cityId') || !isset($_FILES['file']))
        return Status(false, "Missing parameter.");

    if ($_POST['countryId'] == -1 || $_POST['stateId'] == -1 || $_POST['cityId'] == -1)
        return Status(false, "Invalid location.");

    if ($_POST['dateOfBirth'] < date('Y-m-d', strtotime(date('Y-m-d') . '-100 years')) || $_POST['dateOfBirth'] > date('Y-m-d', strtotime(date('Y-m-d') . '-18 years')))
        return Status(false, 'Incorrect birthday.');

    $image = file_get_contents($_FILES['file']['tmp_name']);
    $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);

    if (!($ext == 'jpeg' || $ext == 'png' || $ext == 'jpg'))
        return Status(false, "You should have entered a valid image.");

    $existsPending = sendQuery('select count(*) as c from pendingpersonaldata where id = unhex(?)', $_SESSION['id'])[0];

    if ($existsPending['c'])
        return Status(false, "You already have a pending change.");

    file_put_contents("../Images/pendingPersonalDataImages/" . $_SESSION['id'] . ".png", $image);
    
    $putNewData = sendQuery('insert into pendingPersonalData values (unhex(?), ?, ?, ?, ?, ?, ?, ?, ?);', $_SESSION['id'], $_POST['firstName'], $_POST['lastName'], $_POST['dateOfBirth'], $_POST['gender'], $_POST['address'], $_POST['cityId'],
                            $_POST['stateId'], $_POST['countryId']);
    
    return Status(true, "You have succesfully made a change. You should now wait for approval.");
?>