<?php
    include 'mysql.php';

    if (!isset($_POST['data'])) 
        die("Invalid argument.");

    $data = $_POST['data'];
    $type = strpos($data, '@') ? 'email' : 'username';

    $query = $conn->prepare("select count(*) from users where " . $type . " = ?");
    $query->bind_param('s', $data);
    $query->execute();
    $query->bind_result($ans);
    $query->fetch();

    echo $ans;
?>