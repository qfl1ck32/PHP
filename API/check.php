<?php
    include 'mysql.php';
    include 'functions.php';

    if (c('data') || c('type')) 
        return Status(false, "Missing parameters.");

    if ($_POST['type'] != 'username' && $_POST['type'] != 'email')
        return Status(false, "Wrong type.");

    return Status(true, sendQuery("select count(*) from users where " . $_POST['type'] . " = ?", $_POST['data'])[0]['count(*)']);
?>