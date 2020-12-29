<?php
    include 'mysql.php';
    session_start();

    header('Connection: close');
    ignore_user_abort();

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;

    require __DIR__ . '/PHPMailer/PHPMailer.php';
    require __DIR__ . '/PHPMailer/Exception.php';
    require __DIR__ . '/PHPMailer/SMTP.php';

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host = 'tls://smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = 'tls';
        $mail->Username = 'mybankonline32@gmail.com';
        $mail->Password = getenv('emailPassword');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('mybankonline32@gmail.com', 'myBank Account Administration');
        $mail->addAddress($_POST['email']);
        
        $mail->isHTML(true);
        $mail->Subject = $_POST['subject'];
        $mail->Body = $_POST['html'];
        
        // $mail->AltBody

        if (isset($_POST['attachment']))
            $mail->addStringAttachment($_POST['attachment'], $_POST['attachmentName']);

        $mail->send();
    }

    catch (Exception $e) {
        echo 'Error while sending e-mail: {$mail->ErrorInfo}';
    }

?>