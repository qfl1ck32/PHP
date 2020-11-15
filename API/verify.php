<?php
    include 'mysql.php';

    session_start();

    $query = $_SERVER['QUERY_STRING'];
    $queries = array();
    parse_str($query, $queries);

    $kindOfToken = isset($queries['pwtoken']) ? 'pwtoken' : 'emtoken';

    $token = $queries[$kindOfToken];
    
    $id = sendQuery('select hex(id) from toRecover where token = ?;', $token);

    $isConfirmed = sendQuery('delete from ' . ($kindOfToken == 'pwtoken' ? 'toRecover' : 'toConfirm')  . ' where token = ?', $token);

    if (!$isConfirmed)
        die(header('location: /404.php'));
    
    if ($kindOfToken == 'emtoken') {
        $_SESSION['confirmation'] = true;
        die(header('location: /Login.php'));
    }

    if (!isset($id[0]))
        die(header('location: /404.php'));

    $id = $id[0]['hex(id)'];

    $_SESSION['resetPassword'] = true;
    $_SESSION['id'] = $id;

    die(header('location: /Reset.php'));
?>