<?php
    include 'constants.php';

    function c($x) {
        return !(isset($_POST[$x]) && $_POST[$x]);
    }

    function ext($errorName) {
        return die(json_encode(array(
            'error' => $errorName
        )));
    }

    function Status($status, $message = 0) {
        $ans = array('status' => $status);

        if ($message)
            $ans['message'] = $message;

        return die(json_encode($ans));
    }

    function getConfirmationEmail($data) {
        global $host;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $host . '/Mail.php');
        curl_setopt($ch, CURLOPT_POST, count($data));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $ans = curl_exec($ch);

        curl_close($ch);

        return $ans;
    }

    function trySet($what) {
        global $data;
    
        
        if ($data && isset($data[$what]))
            if ($what == 'image')            
                echo 'src = "data:image/jpg;base64,' . base64_encode($data[$what]) . '"';
            else
                echo 'value = "' . $data[$what] . '" ';
        // else
        //     if ($what == 'state' || $what == 'city')
        //         echo 'disabled';
    }

    function trySetGender($val) {
        global $data;

        if ($data && isset($data['gender']) && $data['gender'] === $val)
            echo 'selected';
    }

    function sendVerificationEmail($data) {
        global $host;
        global $port;

        $fp = fsockopen($host, $port);
        $content = http_build_query($data);

        fwrite($fp, "POST /API/sendMail.php HTTP/1.1\r\n");
        fwrite($fp, "Host: " . $host . "\r\n");
        fwrite($fp, "Content-Type: application/x-www-form-urlencoded\r\n");
        fwrite($fp, "Content-Length: " . strlen($content) . "\r\n");
        fwrite($fp, "Connection: close\r\n");
        fwrite($fp, "\r\n");

        fwrite($fp, $content);
    }
?>