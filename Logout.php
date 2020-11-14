<?php
    include './API/mysql.php';

    session_start();

    $sessId = sendQuery('select sessionId from users where id = unhex(?);', $_SESSION['id']);
    if (session_id() == $sessId)
        sendQuery('update users set sessionId = 0 where id = unhex(?);', $_SESSION['id']);

    return header('location: /Index.php');
?>