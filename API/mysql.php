<?php

    $servername = 'eu-cdbr-west-03.cleardb.net';
    $username = 'ba9d9921cf4199';
    $password = '9537b747';
    $dbName = 'heroku_fc50c5a255e2b86';

    // $servername = 'localhost';
    // $username = 'root';
    // $password = '';
    // $dbName = 'hr';

    $conn = new mysqli($servername, $username, $password, $dbName);

    if ($conn->connect_error)
        die("Connection failed: " . $conn->connect_error);

    function sendQuery($query, ...$bindParams) {
        global $conn;
        
        try {
            $types = "";
            $ans = -1;

            foreach ($bindParams as $param)
                $types .= gettype($param)[0];

            $q = $conn->prepare($query);

            if ($types != "")
                $q->bind_param($types, ...$bindParams);

            $q->execute();
            $r = $q->get_result();

            if ($r != false)
                $ans = $r->fetch_all(MYSQLI_ASSOC);

            else
                $ans = mysqli_affected_rows($conn);
                
            $q->close();
        }

        catch (Exception $e) {
            echo $e;
        }

        return $ans;
    }
?>