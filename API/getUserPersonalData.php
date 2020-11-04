<?php
    include 'mysql.php';
    include 'functions.php';

    if (!isset($_POST['id']))
        return Status("error", "Missing parameter.");


    $data = sendQuery('select * from personaldata where id = unhex(?);', $_POST['id']);

    die($data);
?>