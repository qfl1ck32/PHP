<?php

    session_start();

    function bigNumMod($x, $y) {
        $take = 5;
        $mod = '';

        do {
            $a = (int) $mod.substr($x, 0, $take);
            $x = substr($x, $take);
            $mod = $a % $y;
        } while (strlen($x));

        return (int) $mod;
    }

    include 'functions.php';
    include 'mysql.php';

    if (c('currencyId'))
        return Status(false, "Missing parameters.");

    $chosenCurrency = sendQuery('select name from currencies where id = ?;', $_POST['currencyId']);

    if (!isset($chosenCurrency[0]))
        return Status(false, "You chose an unsupported currency.");

    $chosenCurrency = $chosenCurrency[0]['name'];

    $currentCountry = sendQuery('select sortname, name from countries where id = (select countryId from personaldata where id = unhex(?));', $_SESSION['id'])[0];

    $countryCode = $currentCountry['sortname'];
    $currentCountry = $currentCountry['name'];

    $checkNumber = "00";
    $bankIdentifier = "MBNK";
    $sortCode = $chosenCurrency . 'CRT';
    $accountNumber = "";

    $accountNumberDB = sendQuery('select substring(iban, 15, 8) as accNum from creditcards where id = unhex(?)', $_SESSION['id']);

    if (sizeof($accountNumberDB)) {

        $countOfCurrentCurrency = sendQuery('select count(*) as c from creditcards where id = unhex(?) and substring(iban, 9, 3) = ?;', $_SESSION['id'], $chosenCurrency);

        if ($countOfCurrentCurrency[0]['c'] == 3)
            return Status(false, "You have reached the maximum amount of credit cards for " . $chosenCurrency . ".");

        $accountNumberDB = $accountNumberDB[0]['accNum'];
        $hasThisCurrency = sendQuery('select max(substring(iban, 23)) as ib from creditcards where id = unhex(?) and substring(iban, 9, 3) = ?', $_SESSION['id'], $chosenCurrency)[0]['ib'];
        
        if (!$hasThisCurrency) {
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

    $checkSum = 98 - bigNumMod($checkSum, 97);

    if ($checkSum < 10)
        $checkSum = str_pad($checkSum, 2, "0", STR_PAD_LEFT);

    $IBAN = $countryCode . $checkSum . $bankIdentifier . $sortCode . $accountNumber;


    sendQuery('insert into creditcards values (unhex(?), ?, "Current Account", ?, 10);', $_SESSION['id'], $IBAN, $_POST['currencyId']);

    $currency = sendQuery('select name from currencies where id = ?;', $_POST['currencyId'])[0]['name'];

    $cc = array('IBAN' => $IBAN, 'balance' => 10, 'currency' => $currency, 'type' => 'Current Account');

    return Status(true, "You have succesfully created a new credit card for " . $chosenCurrency . "<br>with IBAN " . $IBAN . ".", $cc);

?>