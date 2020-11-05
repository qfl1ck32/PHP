<?php
    include './API/mysql.php';
    session_start();
    session_destroy();

    $query = $conn->prepare('update users set isLogged = false where username = ?');
    $query->bind_param('s', $_SESSION['username']);
    $query->execute();

    header('location: /index.php');
?>