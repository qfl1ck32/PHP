<?php

    session_start();

    include 'functions.php';
    include 'mysql.php';

    if (c('currency'))
        return Status(false, "Missing parameters.");

    $exists = sendQuery('select count(*) as c from currencies where upper(name) = upper(?);', $_POST['currency'])[0]['c'];

    if (!$exists)
        return Status(false, "You chose an unsupported currency.");

    $currentCountry = sendQuery('select country from personaldata where id = unhex(?);', $_SESSION['id'])[0]['country'];

    $countryCode = sendQuery('select sortname as s from countries where name = ?', $currentCountry)[0]['s'];
    $checkNumber = "00";
    $bankIdentifier = "MBNK";
    $sortCode = $_POST['currency'] . 'CRT';
    $accountNumber = "";

    $accountNumberDB = sendQuery('select substring(iban, 15, 8) as accNum from creditcards where id = unhex(?)', $_SESSION['id']);

    if (sizeof($accountNumberDB)) {

        $countOfCurrentCurrency = sendQuery('select count(*) as c from creditcards where id = unhex(?) and substring(iban, 9, 3) = ?;', $_SESSION['id'], $_POST['currency']);

        if ($countOfCurrentCurrency[0]['c'] == 3)
            return Status(false, "You have reached the maximum amount of credit cards for " . $_POST['currency'] . ".");

        $accountNumberDB = $accountNumberDB[0]['accNum'];
        $hasThisCurrency = sendQuery('select max(substring(iban, 23)) as ib from creditcards where id = unhex(?) and substring(iban, 9, 3) = ?', $_SESSION['id'], $_POST['currency'])[0]['ib'];
        
        if (!$hasThisCurrency) { // is null
            $accountNumber = $accountNumberDB . "01";
        }

        else {

            $accountNumber = $accountNumberDB;

            $nthAcc = intval($hasThisCurrency) + 1;

            if ($nthAcc < 10)
                $nthAcc = str_pad($nthAcc, 2, "0", STR_PAD_LEFT);

            $accountNumber .= $nthAcc;

        }
    }

    else {
        $existingAccNums = sendQuery('select substring(iban, 15, 8) as accNum from creditcards;');
        
        $exists = array();

        for ($i = 0; $i < sizeof($existingAccNums); ++$i)
            array_push($exists, $existingAccNums[$i]['accNum']);

        for ($i = 0; $i < 8; ++$i)
            $accountNumber .= rand() % 10;

        while (in_array($accountNumber, $exists)) {
            $accountNumber = "";
            for ($i = 0; $i < 8; ++$i)
                $accountNumber .= rand() % 10;
        }

        $accountNumber .= "01";
    } 

    $IBAN = $countryCode . $checkNumber . $bankIdentifier . $sortCode . $accountNumber;

    $checkSum = $IBAN;

    $IBAN = substr($IBAN, 4) . substr($IBAN, 0, 2). "00";
    $checkSum = "";

    for ($i = 0; $i < strlen($IBAN); ++$i) {
        if ($IBAN[$i] >= 'A' && $IBAN[$i] <= 'Z')
            $checkSum .= ord($IBAN[$i]) - ord('A') + 10;
        else
            $checkSum .= $IBAN[$i];

    }

    $checkSum = 98 - bcmod($checkSum, 97);

    if ($checkSum < 10)
        $checkSum = str_pad($checkSum, 2, "0", STR_PAD_LEFT);

    $IBAN = $countryCode . $checkSum . $bankIdentifier . $sortCode . $accountNumber;


    sendQuery('insert into creditcards values (unhex(?), ?, "Current Account", ?, 0);', $_SESSION['id'], $IBAN, $_POST['currency']);

    return Status(true, "You have succesfully created a new credit card!");

    //sendQuery('insert into creditcards values (unhex(?),')

?>