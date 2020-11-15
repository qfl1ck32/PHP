<?php
    include 'mysql.php';
    include 'functions.php';

    if (c('data')) 
        return Status(false, "Missing parameters.");

    $data = $_POST['data'];
    $type = strpos($data, '@') ? 'email' : 'username';

    return Status(true, sendQuery("select count(*) from users where " . $type . " = ?", $data)[0]['count(*)']);
?>