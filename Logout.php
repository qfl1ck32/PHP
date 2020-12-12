<?php
    include './API/mysql.php';

    session_start();

    if (!isset($_SESSION['isLogged']) || $_SESSION['isLogged'] != true)
        return header('location: /Index.php');

    $sessId = sendQuery('select sessionId as sid from users where id = unhex(?);', $_SESSION['id'])[0]['sid'];

    if (session_id() == $sessId)
        sendQuery('update users set sessionId = 0 where id = unhex(?);', $_SESSION['id']);

    session_destroy();

    return header('location: /Index.php');
?>