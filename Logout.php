<?php
    include './API/mysql.php';
    session_start();
    session_destroy();

    $query = $conn->prepare('update users set sessionId = 0 where username = ?');
    $query->bind_param('s', $_SESSION['username']);
    $query->execute();

    header('location: /Index.php');
?>