<?php
    session_start();

    if (!isset($_SESSION['admin']) || !$_SESSION['admin'])
        return Status(false, 'You are not allowed to post here.');

    include './API/mysql.php';
    include './API/functions.php';

    if (!isset($_SESSION['isLogged']) || !$_SESSION['isLogged'])
        die(header('location: /404.php'));

    $sessId = sendQuery('select sessionId as sid from users where id = unhex(?);', $_SESSION['id'])[0]['sid'];

    if (session_id() != $sessId) {
        session_destroy();
        die(header('location: /404.php'));
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        if (!c('getCards')) {

            $hasPersonalData = sendQuery('select ID from personaldata where id = unhex(?);', $_POST['ID']);
            
            if (!sizeof($hasPersonalData))
                return Status(-1, 0);

            $creditCards = sendQuery('select iban, type, (select name from currencies where id = currencyId) currency, balance from creditCards where id = unhex(?);', $_POST['ID']);

            if (!sizeof($creditCards))
                return Status(false, 0);

            $cc = array();

            foreach ($creditCards as $creditCard) {
                $cc[] = array('IBAN' => $creditCard['iban'], 'type' => $creditCard['type'], 'currency' => $creditCard['currency'], 'balance' => $creditCard['balance']);
            }

            return Status(true, $cc);
        }

        if (!c('getUsers')) {

            $users = sendQuery('select hex(ID) ID, (select count(p.id) from pendingpersonaldata p where p.id = u.id) hasPending, username, email from users u order by hasPending DESC;');

            $cc = array();

            foreach ($users as $user) {
                $cc[] = array('ID' => $user['ID'], 'hasPending' => $user['hasPending'], 'username' => $user['username'], 'email' => $user['email']);
            }

            return Status(true, $cc);
        }

        if (!c('buyItem')) {

            if (c('itemId') || c('IBAN'))
                return Status(false, "Missing parameter(s).");

            $itemId = $_POST['itemId'];
            $IBAN = $_POST['IBAN'];

            $item = sendQuery('select name, price, currency, storename from shoppingitems where id = ?', $itemId);

            if (!isset($item[0]))
                return Status(false, "The item does not exist.");

            $creditCard = sendQuery('select balance, (select name from currencies where id = currencyId) currency from creditcards where id = unhex(?) and iban = ?;', $_POST['ID'], $IBAN);

            if (!isset($creditCard[0]))
                return Status(false, "The given IBAN does not belong to you or does not exist.");

            $item = $item[0];
            $creditCard = $creditCard[0];

            $price = $item['price'];
            $itemCurrency = $item['currency'];

            $balance = $creditCard['balance'];
            $creditCardCurrency = $creditCard['currency'];

            $transactionReference = strtoupper(bin2hex(random_bytes(16)));
            $type = 'POS Purchase';
            $description = 'POS Purchase at ' . $item['storename'] . '.';

            if ($itemCurrency === $creditCardCurrency) {
                if ($price > $balance) {
                    return Status(false, "Your balance is too low.");
                }

                sendQuery('update creditcards set balance = balance - ? where IBAN = ?;', round($price, 2), $IBAN);
                sendQuery('insert into transactions values (?, ?, ?, now(), ?, ?, ?);', $transactionReference, $IBAN, $type, $description, round($price, 2), round($balance - $price, 2));
                
                return Status(true, "You have succesfully made the purchase.");
            }

            $exchangeRatesData = json_decode(file_get_contents('https://api.exchangeratesapi.io/latest?symbols=' . $itemCurrency . '&base=' . $creditCardCurrency));
            $rate = ($exchangeRatesData -> {'rates'}) -> {$itemCurrency};

            $itemConvertedPrice = round($price / $rate, 2);

            if ($itemConvertedPrice > $balance)
                return Status(false, "Your balance is too low.");

            $description = $description . ' | Automatic currency conversion took place (1 ' . $creditCardCurrency . ' = ' . round($rate, 6) . ' ' . $itemCurrency . ' @ ' . $exchangeRatesData -> {'date'} . ').';

            sendQuery('update creditcards set balance = balance - ? where IBAN = ?;', $itemConvertedPrice, $IBAN);

            sendQuery('insert into transactions values (?, ?, ?, now(), ?, ?, ?);', $transactionReference, $IBAN, $type, $description, $itemConvertedPrice, round($balance - $itemConvertedPrice, 2));

            return Status(true, "You have succesfully made the purchase.");

        }

        if (!c('simulateTransaction')) {
            if (c('fromIBAN') || c('toIBAN') || c('description') || c('amount'))
                return Status(false, "Missing parameter(s).");

            if (!is_numeric($_POST['amount']) || $_POST['amount'] <= 0)
                return Status(false, "Invalid amount.");

            if (strlen($_POST['description']) > 32)
                return Status(false, "Description too long.");

            $_POST['description'] = htmlspecialchars($_POST['description']);

            $checkIsHisIban = sendQuery('select balance from creditcards where IBAN = ? and id = unhex(?);', $_POST['fromIBAN'], $_POST['ID']);

            if (!isset($checkIsHisIban[0])) {
                return Status(false, "Invalid sender IBAN.");
            }

            if ($_POST['amount'] > $checkIsHisIban[0]['balance'])
                return Status(false, "You are trying to send more money than your current balance.");

            $checkIbanExists = sendQuery('select hex(id) as id from creditcards where IBAN = ?;', $_POST['toIBAN']);

            if (!isset($checkIbanExists))
                return Status(false, "The given IBAN does not belong to anyone.");

            if ($checkIbanExists[0]['id'] === $_POST['ID'])
                return Status(false, "You can not send money to yourself.");

            
            $senderCurrency = substr($_POST['fromIBAN'], 8, 3);
            $receiverCurrency = substr($_POST['toIBAN'], 8, 3);

            $amountToSend = 0;
            $hasExchangedMoney = false;

            if ($senderCurrency != $receiverCurrency) {

                $exchangeRate = json_decode(file_get_contents('https://api.exchangeratesapi.io/latest?symbols=' . $receiverCurrency . '&base=' . $senderCurrency));
                $date = $exchangeRate -> {'date'};
                $convertedCurrency = $exchangeRate -> {'rates'} -> {$receiverCurrency};

                if (isset($_POST['ignoreCurrencyConvert']) && $_POST['ignoreCurrencyConvert'] == "true") {
                    $amountToSend = round($_POST['amount'] * $convertedCurrency, 2);
                }

                else {
                
                    $checkHaveReceiverCurrency = sendQuery('select IBAN from creditcards where id = unhex(?) and substr(iban, 9, 3) = ?;', $_POST['ID'], $receiverCurrency);

                    if (isset($checkHaveReceiverCurrency[0])) {
                        return Status(-1, "You have other credit card(s) with the same currency.<br>If you wish to send money from one of those, feel free to do so by clicking one of them."
                        . "<br>Otherwise, press the 'Simulate' button again to send from the current chosen credit card.", $checkHaveReceiverCurrency, 0);
                    }

                    $receiverId = sendQuery('select hex(id) as id from creditcards where IBAN = ?', $_POST['toIBAN'])[0]['id'];
                    $checkHasSenderCurrency = sendQuery('select IBAN from creditcards where id = unhex(?) and substr(iban, 9, 3) = ?;', $receiverId, $senderCurrency);

                    if (isset($checkHasSenderCurrency[0]))
                        return Status(-1, "The receiver has other credt card(s) with the same currency.<br>If you wish to send money to one of those, feel free to do so by clicking one of them."
                        . "<br>Otherwise, press the 'Simulate' button again to send from the current chosen credit card.", $checkHasSenderCurrency, 1);

                    $amountToSend = round($_POST['amount'] * $convertedCurrency, 2);
                    
                    $hasExchangedMoney = true;
                }
            }

            if ($amountToSend == 0)
                $amountToSend = $_POST['amount'];

            $transactionReference = strtoupper(bin2hex(random_bytes(16)));

            sendQuery('update creditcards set balance = balance + ? where iban = ?;', round($amountToSend, 2), $_POST['toIBAN']);
            sendQuery('update creditCards set balance = balance - ? where iban = ?;', round($_POST['amount'], 2), $_POST['fromIBAN']);

            $senderName = sendQuery("select concat(firstName, ' ', lastName) name from personalData where id = unhex(?);", $_POST['ID'])[0]['name'];
            $receiverName = sendQuery("select concat(firstName, ' ', lastName) name from personalData where id = (select id from creditCards where iban = ?);", $_POST['toIBAN'])[0]['name'];

            $sendBalance = sendQuery('select balance from creditcards where iban = ?;', $_POST['fromIBAN'])[0]['balance'];
            $receiveBalance = sendQuery('select balance from creditcards where iban = ?;', $_POST['toIBAN'])[0]['balance'];

            $type1 = "Sent money";
            $description1 = $_POST['description'];

            $type2 = "Received money";
            $description2 = $_POST['description'];

            if ($hasExchangedMoney) {
                $conversionMessage = ' | Automatic currency conversion took place (1 ' . $senderCurrency . ' = ' . round($convertedCurrency, 6) . ' ' . $receiverCurrency . ' @ ' . $date . ').';
                $description1 .= $conversionMessage;
                $description2 .= $conversionMessage;
            }

            $description1 .= ' | Receiver: ' . $receiverName . ', IBAN: ' . $_POST['toIBAN'];
            $description2 .= ' | Sender: ' . $senderName . ', IBAN: ' . $_POST['fromIBAN'];

            sendQuery('insert into transactions values (?, ?, ?, now(), ?, ?, ?), (?, ?, ?, now(), ?, ?, ?);', 
                        $transactionReference, $_POST['fromIBAN'], $type1, $description1, round($_POST['amount'], 2), round($sendBalance, 2),
                        $transactionReference, $_POST['toIBAN'], $type2, $description2, round($amountToSend, 2), round($receiveBalance, 2));

            return Status(true, "The transaction succesfully took place.<br>Check the 'transactions' tab for more details.");
        }

        if (!c('checkIBANExists')) {
            if (c('IBAN'))
                return Status(false, "Missing parameter.");

            $checkIbanIsSelf = sendQuery('select hex(id) as id from creditcards where IBAN = ?', $_POST['IBAN']);

            // make more efficient - only one query

            if (isset($checkIbanIsSelf[0]) && $checkIbanIsSelf[0]['id'] == $_POST['ID'])
                return Status(false, "You can not send money to yourself.");

            $ibanExists = sendQuery('select concat(firstName, " ", lastName) as name from personalData where id = (select id from creditcards where IBAN = ?);', $_POST['IBAN']);

            if (!sizeof($ibanExists))
                return Status(false, "There is no credit card account with the given IBAN.");

            return Status(true, $ibanExists[0]['name']);
        }

        if (!c('modifyBalance')) {

            if ($_POST['modifyBalance'] < 0)
                return Status(false, 'You can not have a negative balance.');

            sendQuery('update creditCards set balance = ? where iban = ?;', $_POST['modifyBalance'], $_POST['IBAN']);

            return Status(true, 'Successfully updated.');
        }

        if (!c('getPersonalData')) {
            $personalData = sendQuery('select firstName, lastName, dateOfBirth, gender, address, (select name from cities where id = cityid) city, (select name from states where id = stateid) state, (select name from countries where id = countryid) country from personaldata where id = unhex(?);', $_POST['ID']);

            $pendingPersonalData = sendQuery('select firstName, lastName, dateOfBirth, gender, address, (select name from cities where id = cityid) city, (select name from states where id = stateid) state, (select name from countries where id = countryid) country from pendingpersonaldata where id = unhex(?);', $_POST['ID']);

            if (sizeof($personalData)) {
                $personalData = $personalData[0];
                if ($image = @file_get_contents("Images/personalDataImages/" . $_POST['ID'] . ".png"))
                    $personalData['image'] = 'data:image/jpg;base64,' . base64_encode($image);
            }
            
            else
                $personalData = false;

            if (sizeof($pendingPersonalData)) {
                $pendingPersonalData = $pendingPersonalData[0];

                if ($image = @file_get_contents("Images/pendingPersonalDataImages/" . $_POST['ID'] . ".png"))
                    $pendingPersonalData['image'] = 'data:image/jpg;base64,' . base64_encode($image);
            }
            else
                $pendingPersonalData = false;

            return Status(true, "Success.", $personalData, $pendingPersonalData);
        }

        if (!c('acceptChange')) {
            sendQuery('insert into personaldata (select * from pendingpersonaldata where id = unhex(?));', $_POST['ID']);
            sendQuery('delete from pendingpersonaldata where id = unhex(?);', $_POST['ID']);

            rename('Images/pendingPersonalDataImages/' . $_POST['ID'] . '.png', 'Images/personalDataImages/' . $_POST['ID'] . '.png');

            return Status(true, "Success.");
        }

        if (!c('rejectChange')) {
            sendQuery('delete from pendingpersonaldata where id = unhex(?);', $_POST['ID']);

            unlink('Images/pendingPersonalDataImages/' . $_POST['ID'] . '.png');

            return Status(true, "Success");
        }

        if (!c('deleteIBAN')) {
            sendQuery('delete from creditcards where IBAN = ?;', $_POST['deleteIBAN']);

            return Status(true, "Successfully deleted.");
        }

        $data = sendQuery('select type, (select name from currencies where id = currencyId) currency, balance from creditcards where id = unhex(?) and iban = ?', $_POST['ID'], $_POST['IBAN']);

        if (!isset($data[0]))
            return Status(false, "The given IBAN either does not exist or it does not belong to any of your credit cards.");

        $data = $data[0];

        if (!c('filterByDate')) {
            $transactions = sendQuery('select date, type, description, amount, balance, hex(reference) as reference from transactions where iban = ? and date >= ? and date <= ? order by date desc;', $_POST['IBAN'], 
                $_POST['fromDate'], date('Y-m-d', strtotime($_POST['toDate'] . ' +1 day')));

            die(json_encode(array(
                'status' => true,
                'currency' => $data['currency'],
                'transactions' => $transactions
            )));
        }

        $transactions = sendQuery('select date, type, description, amount, balance, hex(reference) as reference from transactions where iban = ? order by date desc;', $_POST['IBAN']);
            
        $currentCurrencyImg = (glob('Images/countryFlags/' . substr($data['currency'], 0, 2) . '.png'))[0];

        die(json_encode(array(
            'status' => true,
            'type' => $data['type'],
            'currency' => $data['currency'],
            'balance' => $data['balance'] . ' ' . $data['currency'],
            'img' => $currentCurrencyImg,
            'transactions' => $transactions
        )));
    }

    $currencies = sendQuery('select * from currencies;');

    $currencyWithImg = array();

    foreach ($currencies as $currentCurrency) {
        $name = $currentCurrency['name'];
        $id = $currentCurrency['id'];
        $img = glob('Images/countryFlags/' . substr($name, 0, 2) . '.png');

        $currencyWithImg[] = array('id' => $id, 'name' => $name, 'src' => $img[0]);
    }


    $shoppingItemsTemp = sendQuery('select * from shoppingitems order by currency;');

    $shoppingItems = array();

    foreach ($shoppingItemsTemp as $shoppingItem) {
        $shoppingItems[] = array('id' => $shoppingItem['id'], 'name' => $shoppingItem['name'], 'price' => $shoppingItem['price'], 'currency' => $shoppingItem['currency'], 'storename' => $shoppingItem['storename']);
    }
    
?>

<!DOCTYPE HTML>

<html>

    <head>
    
        <meta charset = 'UTF-8'>
        <meta name = 'viewport' content = 'width = device-width, initial-scale = 1.0'>

        <link rel = 'stylesheet' type = 'text/css' href = 'CSS/Administrate.css'>
        <link rel = 'stylesheet' type = 'text/css' href = 'CSS/Animations.css'>

        <link rel = 'stylesheet' type = 'text/css' href = 'https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css' integrity = 'sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2' crossorigin = 'anonymous'>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
        
        <link rel = 'icon' href = 'Images/Icon.png'>

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js" type="text/javascript"></script>
        <script src = 'https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js' integrity = 'sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx' crossorigin = 'anonymous'></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>

        <script src = "https://kit.fontawesome.com/7218cc0d0e.js" crossorigin="anonymous"></script>

        <script src = 'JS/Administrate.js' defer></script>
        <script src = 'JS/Buttons.js' defer></script> 

        <title>Administrate</title>

    </head>

    <body class = 'bg-img'>
    
        <nav class = 'navbar navbar-expand-md navbar-dark bg-blue'>
            <a href = '/Index.php' class = 'navbar-brand d-md-none font-weight-bold font-italic border border-light p-1 rounded-pill' href = '#'>myBank</a>
            <button class = 'navbar-toggler ml-auto' type = 'button' data-toggle = 'collapse' data-target = '#navbarSupportedContent' aria-controls = 'navbarSupportedContent' aria-expanded = 'false' aria-label = 'Toggle navigation'>
                    <span class = 'navbar-toggler-icon'></span>
            </button>

            <div class = 'd-md-flex d-block w-100'>

                <div class = 'collapse navbar-collapse mx-auto w-auto justify-content-center' id = 'navbarSupportedContent'>
                    
                    <div class = 'navbar-nav mx-auto'>    
                        <ul class = 'navbar-nav mr-auto'>
                            <li class = 'nav-item'>
                                <a id = 'home' class = 'nav-link' href = '/Index.php'>Home</a>
                            </li>

                            <?php
                                if (isset($_SESSION['isLogged']) && $_SESSION['isLogged'])
                                    echo " <li class = 'nav-item'>
                                                <a id = 'onlineBanking' href = '#' class = 'nav-link'>Banking</a>
                                            </li>";
                            ?>
                            
                            <li class = 'nav-item'>
                                <a href = '/Index.php' class = 'navbar-brand mx-2 d-none d-md-inline font-weight-bold font-italic border border-light p-1 rounded-pill'>myBank</a>
                            </li>

                            <?php
                                if (isset($_SESSION['isLogged']) && $_SESSION['isLogged'])
                                    echo " <li class = 'nav-item'>
                                                <a id = 'personalData' href = '#' class = 'nav-link'>Personal Data</a>
                                            </li>";
                            ?>

                            <li class = 'nav-item'>
                                <a id = 'about' class = 'nav-link' href = '#'>About</a>
                            </li>
                        </ul>
                    </div>

                </div>

            </div>

        </nav>

        <div class = 'container-fluid text-right py-4'>

            <?php
                if (isset($_SESSION['admin']) && $_SESSION['admin'])
                    echo '<button id = "admin" class = "btn btn-outline-primary btn-sm border rounded-pill text-white mr-2 active">Administrate accounts</button>';

                echo "<button id = 'logout' class = 'btn btn-outline-primary btn-sm border rounded-pill text-white'>Logout</button>";

            ?>

        </div>
      
       <div id = 'mainDiv' class = 'container mb-4'>

            <div class = 'container d-flex justify-content-center text-light'>
                <h1 class = 'display-5 font-italic'>Administrate</h1>
            </div>

            <hr class = 'mt-0'>

           <div class = 'row py-4'>
                <div style = 'overflow: hidden;' class = 'container'>
                    <ul id = 'usersList' style = 'max-height: 288px; overflow-y: scroll;' class = 'list-group flex-row pb-2 pr-2'></ul>
                </div>

                <div style = 'overflow: hidden;' class = 'container'>
                    <ul id = 'creditCardsList' style = 'max-height: 288px; overflow-y: scroll;' class = 'list-group flex-row pb-2 pr-2'></ul>

                    <div class = 'container pt-4 text-center'>
                        <button id = 'createCard' class = 'btn btn-outline-primary btn-sm border rounded-pill text-white mb-2' data-toggle = 'modal' data-target = '#modalCenter'>Create a new credit card</button>
                        
                        <button id = 'simulateTransaction' class = 'btn btn-outline-primary btn-sm border rounded-pill text-white mb-2' data-toggle = 'modal' data-target = '#modalCenter3'>Simulate a transaction</button>
                        
                        <div class = 'modal fade' id = 'modalCenter3' tabindex = '-1' role = 'dialog' aria-labelledby = 'modalCenterTitle' aria-hidden = 'true'>
                            <div class = 'modal-dialog modal-dialog-centered' role = 'document'>
                                <div class = 'modal-content'>

                                    <div class = 'modal-header'>
                                        <h5 class = 'modal-title w-100' id = 'modalTitle'>Simulate a transaction</h5>
                                        <button type = 'button' class = 'close' data-dismiss = 'modal' aria-label = 'Close'>
                                            <span aria-hidden = 'true'>&times;</span>
                                            </button>
                                    </div>

                                    <div class = 'modal-body'>
                                    
                                        <div id = 'messageSimulateTransaction' class = 'text-center mb-2'></div>

                                        <div class = 'form-group text-left'>

                                            <div class = 'd-flex justify-content-around mb-2'>
                                                <button associatedPage = 'sendMoneyContainer' class = 'btn btn-outline-primary btn-md border rounded-pill sendBuyButtons active' id = 'sendMoney'>Send money</button>
                                                <button associatedPage = 'buyItemContainer' class = 'btn btn-outline-primary btn-md border rounded-pill sendBuyButtons' id = 'buyItem'>Buy item</button>
                                            </div>

                                            <hr>

                                            <label for = 'transactionIBANFrom'>Selected credit card</label>

                                            <div class = 'input-group'>
                                                <div class = 'input-group-prepend'>
                                                    <span class = 'input-group-text'>
                                                        <img style = 'width: 16px; height: 16px;' id = 'transactionSimImage'></img>
                                                    </span>
                                                </div>

                                                <input disabled class = 'form-control text-center' id = 'transactionSimIBANFrom' name = 'transactionSimIBANFrom'></input>

                                                <div class = 'input-group-append'>
                                                    <span id = 'transactionSimBalance' class = 'input-group-text'></span>
                                                </div>
                                            </div>

                                            <hr>

                                            <div id = 'sendMoneyContainer' class = 'form-group currentContainer'>
                                                <label for = 'transactionSimIBANTo'>Receiver's IBAN</label>
                                                <input autocomplete = 'off' class = 'form-control' name = 'transactionSimIBANTo' id = 'transactionSimIBANTo'>

                                                <label for = 'transactionSimReceiverName'>Reicever's name</label>
                                                <input disabled class = 'form-control' name = 'transactionSimReceiverName' id = 'transactionSimReceiverName' placeholder = 'This will be autocompleted after you fill in the IBAN.'>

                                                <label for = 'transactionSimDescription'>Description</label>
                                                <input autocomplete = 'off' class = 'form-control' name = 'transactionSimDescription' id = 'transactionSimDescription'>

                                                <label for = 'transactionSimAmount'>Amount</label>

                                                <div class = 'input-group'>
                                                    <input autocomplete = 'off' class = 'form-control' name = 'transactionSimAmount' id = 'transactionSimAmount'>

                                                    <div class = 'input-group-append'>
                                                        <span id = 'transactionSimCurrency' class = 'input-group-text'></span>
                                                    </div>
                                                </div>
                                                

                                            </div>

                                            <div id = 'buyItemContainer' style = 'display: none;' class = 'form-group'>
                                                <label for = 'transactionSimItem'>Choose an item</label>
                                                <select data-live-search = 'true' data-live-search-style = 'startsWith' class = 'form-control selectpicker show-tick' id = 'chosenItem' name = 'chosenItem'>
                                                    <?php
                                                            foreach ($shoppingItems as $shoppingItem) {
                                                                echo "<option data-subtext = '" . $shoppingItem['storename'] . "' value = '" . $shoppingItem['id'] . "'>" . $shoppingItem['name'] . ' | ' . $shoppingItem['price'] . ' ' . $shoppingItem['currency'] . "</option>\n";
                                                            }
                                                    ?>
                                                </select>
                                            </div>

                                        </div>
                                        
                                    </div>

                                    <div class = 'modal-footer'>
                                        <button id = 'closeModal' type = 'button' class = 'btn btn-secondary' data-dismiss = 'modal'>Close</button>
                                        <button disabled id = 'simulateTransactionButton' type = 'button' class = 'btn btn-primary'>Simulate</button>
                                    </div>

                                </div>
                            </div>
                        </div>
                </div>
        </div>

        <div class = 'container mb-2'>
                <div class = 'input-group d-flex justify-content-between'>
                    <button associatedPage = 'bankData' id = 'switchToBank' class = 'switchContentButton btn btn-outline-primary btn-sm border rounded-pill text-white active mt-2'>Bank</button>
                    <button associatedPage = 'personalDataDiv' id = 'switchToPersonal' class = 'switchContentButton btn btn-outline-primary btn-sm border rounded-pill text-white mt-2'>Personal</button>
                </div>
             </div>

            <div id = 'bankData' class = 'col-lg border rounded offset-lg-0 mt-4 mt-lg-0 p-4 px-4 mb-2 mx-2'>

                <div class = 'modal fade' id = 'modalCenter' tabindex = '-1' role = 'dialog' aria-labelledby = 'modalCenterTitle' aria-hidden = 'true'>
                    <div class = 'modal-dialog modal-dialog-centered' role = 'document'>
                        <div class = 'modal-content'>

                            <div class = 'modal-header text-center'>
                                <h5 class = 'modal-title w-100' id = 'modalTitle'>Create a new credit card</h5>
                                <button type = 'button' class = 'close' data-dismiss = 'modal' aria-label = 'Close'>
                                    <span aria-hidden = 'true'>&times;</span>
                                    </button>
                            </div>

                            <div class = 'modal-body'>
                            
                                    <div id = 'messageCreateCreditCard' class = 'text-center'></div>

                                <form>
                                    <div class = 'form-group'>
                                            <label for = 'currency'>Currency</label>
                                            <select data-live-search = 'true' data-live-search-style = 'startsWith' class = 'form-control selectpicker show-tick' id = 'createCardWithCurrency' name = 'currency'>
                                            <?php
                                                    foreach ($currencyWithImg as $curr) {
                                                        echo "<option value = '" . $curr['id'] . "'>" . $curr['name'] . "</option>\n";
                                                    }
                                            ?>
                                        </select>
                                    </div>
                                </form>
                            </div>

                            <div class = 'modal-footer'>
                                <button id = 'closeModal' type = 'button' class = 'btn btn-secondary' data-dismiss = 'modal'>Close</button>
                                <button id = 'createCreditCard' type = 'button' class = 'btn btn-primary'>Create</button>
                            </div>

                        </div>
                    </div>
                </div>

                <div id = 'allCreditCards' class = 'container'>

                    <div class = 'd-flex justify-content-around mb-2'>
                        <button associatedPage = 'creditCardMainData' class = 'btn btn-outline-primary btn-md border rounded-pill text-white ccDataButtons active' id = 'details'>Details</button>
                        <button associatedPage = 'creditCardTransactionsData' class = 'btn btn-outline-primary btn-md border rounded-pill text-white ccDataButtons' id = 'transactions'>Transactions</button>
                        <button associatedPage = 'creditCardModifyData' class = 'btn btn-outline-primary btn-md border rounded-pill text-white ccDataButtons' id = 'modify'>Modify</button>
                    </div>
                
                    <hr>

                    <div class = 'container currentDataPage' id = 'creditCardMainData'>
                            <div class = 'text-white'>
                                <h5 class = 'font-weight-bold'>Account type</h5>
                                <h6 class = 'ml-2 font-italic' id = 'accountType'>N/A</h6>
                            </div>

                            <div class = 'text-white mt-4'>
                                <h5 class = 'font-weight-bold'>IBAN</h5>
                                <h6 class = 'ml-2 font-italic' id = 'IBAN'>N/A</h6>
                            </div>

                            <div class = 'text-white mt-4'>
                                <h5 class = 'font-weight-bold'>Currency</h5>
                                <h6 class = 'ml-2 font-italic' id = 'currency'>N/A</h6>
                            </div>

                            <hr>

                            <div class = 'text-white mt-4'>
                                <h5 class = 'font-weight-bold'>Available balance</h5>
                                <h6 class = 'ml-2 font-italic' id = 'balance'>N/A</h6>
                            </div>
                    </div>

                    <div class = 'row h-50 text-center' id = 'creditCardMainDataSpinner'>
                        <div class = 'col-md-12 my-auto'>
                            <div id = 'spinner' class = 'spinner-grow text-primary' role = 'status'></div>
                        </div>
                    </div>

                    
                    <div class = 'container' id = 'creditCardTransactionsData'>
                        <div class = 'text-white'>
                            <div id = 'filterTransactionsByDateDiv' class = 'form-group text-center'>
                                <div class = 'input-group'>
                                    <input class = 'form-control mr-2' type = 'date' max = <?php echo date('Y-m-d'); ?> id = 'transactionsFromDate'>
                                    <input class = 'form-control ml-2 mr-2' type = 'date' max = <?php echo date('Y-m-d'); ?> id = 'transactionsToDate'>
                                    <button id = 'filterTransactionsByDate' type = 'button' class = 'btn btn-outline-primary btn-md border rounded-pill text-white ml-2'>Filter</button>
                                </div>
                            </div>
                            <ul id = 'transactionsList' class = 'list-group' style = 'max-height: 200px; overflow-y: scroll;'></ul>
                        </div>

                        <div id = 'missingTransactions' class = 'text-white text-center'>
                        </div>

                        <div class = 'text-white text-center'>
                            <button class = 'btn btn-outline-primary btn-md border rounded-pill text-white mt-4' id = 'exportTransactions' data-toggle = 'popover' data-placement = 'bottom'>Export transactions to e-mail</button>
                        </div>
                    </div>

                    <div class = 'container' id = 'creditCardModifyData'>
                        <div class = 'text-white'>
                            <div class = 'input-group'>
                                <input class = 'form-control' id = 'modifyBalance'>
                                <button id = 'modifyBalanceButton' type = 'button' class = 'btn btn-outline-primary btn-md border rounded-pill text-white ml-2'>Modify balance</button>
                            </div>
                            
                            <div id = 'modifyAlert' class = 'alert text-center mt-2'></div>

                            <div class = 'container text-center'>
                                <button id = 'deleteBankAccount' type = 'button' class = 'btn btn-outline-primary btn-md border rounded-pill text-white mt-2'  data-toggle = 'modal' data-target = '#modalCenterDelete'>Delete bank account</button>
                            </div>
                        </div>
                    </div>


                    <div class = 'modal fade' id = 'modalCenter2' tabindex = '-1' role = 'dialog' aria-labelledby = 'modalCenterTitle' aria-hidden = 'true'>
                        <div class = 'modal-dialog modal-dialog-centered' role = 'document'>
                            <div class = 'modal-content'>

                                <div class = 'modal-header text-center'>
                                    <h5 class = 'modal-title w-100' id = 'modalTitle'>Transaction details</h5>
                                    <button type = 'button' class = 'close' data-dismiss = 'modal' aria-label = 'Close'>
                                        <span aria-hidden = 'true'>&times;</span>
                                        </button>
                                </div>

                                <div class = 'modal-body'>
                                    <div class = 'container bg-light'>
                                        <div class = 'row font-weight-bold'>
                                            Transaction date
                                        </div>
                                        
                                        <div id = 'transactionDate' class = 'row font-italic'></div>
                                    </div>

                                    <div class = 'container bg-light mt-4'>
                                        <div class = 'row font-weight-bold'>
                                            Description
                                        </div>

                                        <div id = 'transactionDescription' class = 'row font-italic'></div>
                                    </div>

                                    <div class = 'container bg-light mt-4'>
                                        <div class = 'row font-weight-bold'>
                                            Amount
                                        </div>

                                        <div id = 'transactionAmount' class = 'row font-italic'></div>
                                    </div>

                                    <div class = 'container bg-light mt-4'>
                                        <div class = 'row font-weight-bold'>
                                            Balance
                                        </div>

                                        <div id = 'transactionBalance' class = 'row font-italic'></div>
                                    </div>

                                    <div class = 'container bg-light mt-4'>
                                        <div class = 'row font-weight-bold'>
                                            Transaction Reference
                                        </div>

                                        <div id = 'transactionReference' class = 'row font-italic'></div>
                                    </div>

                                </div>

                                <div class = 'modal-footer'>
                                    <button id = 'closeModal' type = 'button' class = 'btn btn-secondary' data-dismiss = 'modal'>Close</button>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class = 'modal fade' id = 'modalCenterDelete' tabindex = '-1' role = 'dialog' aria-labelledby = 'modalCenterTitle' aria-hidden = 'true'>
                        <div class = 'modal-dialog modal-dialog-centered' role = 'document'>
                            <div class = 'modal-content'>
                                <div class = 'modal-header text-center'>
                                        <h5 class = 'modal-title w-100' id = 'modalTitle'>Delete credit card</h5>
                                        <button type = 'button' class = 'close' data-dismiss = 'modal' aria-label = 'Close'>
                                            <span aria-hidden = 'true'>&times;</span>
                                            </button>
                                </div>

                                <div class = 'modal-body'>
                                    <div id = 'creditCardToDelete'></div>
                                </div>

                                <div class = 'modal-footer'>
                                    <button id = 'confirmModalDelete' type = 'button' class = 'btn btn-primary' data-dismiss = 'modal'>Confirm</button> 
                                    <button id = 'closeModalDelete' type = 'button' class = 'btn btn-secondary' data-dismiss = 'modal'>Close</button>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>

                    <div id = 'missingCreditCards' class = 'container text-center text-white'>

                        <h4>Uh-oh!</h4>

                        <hr>

                        <h6>It seems like this user got no credit cards.</h6>
                        
                    </div>

                    <div id = 'missingPersonalData' class = 'container text-center text-white'>
                        <h4>Uh-oh!</h4>

                        <h6>It seems like this user hasn't put any information in the "Personal data" tab.</h6>

                    </div>

            </div>

            <div id = 'personalDataDiv' class = 'col-lg border rounded offset-lg-0 mt-4 mt-lg-0 p-4 px-4 mb-2 mx-2 text-white'>

                <div class = 'row'>
                    <div id = 'personalDataOld' class = 'container col col-10 col-sm-6 col-xl-3 col-lg-5'>

                        <div class = 'container bg-primary text-center border rounded'>Current personal data</div>
                        
                        <div class = "form-group">
                            <label for = "firstName">First name: </label>
                            <input disabled autocomplete = "off" id = 'firstNameOld' class = "form-control" type = "text" name = "firstName">
                        </div>
                            
                        <div class = "form-group">
                            <label for = "lasttName">Last name: </label>
                            <input disabled autocomplete = "off" id = 'lastNameOld' class = "form-control" type = "text" name = "lastName">
                        </div>

                        <div class = "form-group">
                            <label for = "dateOfBirth">Date of birth: </label>
                            <input disabled autocomplete = "off" id = 'dateOfBirthOld' class = "form-control" type = "date" name = "dateOfBirth">
                        </div>

                        <div class = "form-group">
                            <label for = "gender">Gender: </label>
                            <select disabled class = "form-control custom-select" name = "gender" id = "genderOld">
                                <option value = "M">M</option>
                                <option value = "F">F</option>
                            </select>
                        </div>

                        <div class = "form-group">
                            <label for = "address">Address: </label>
                            <input disabled autocomplete = "off" id = 'addressOld' class = "form-control" type = "text" name = "address">
                        </div>

                        <div class = "form-group">
                            <label for = "country">Country: </label>
                            <input disabled autocomplete = "off" id = 'countryOld' class = "form-control" type = "text" name = "country">
                            <div id = "countries" class = "container countries bg-info mt-2"></div>

                        </div>

                        <div class = "form-group">
                            <label for = "state">State: </label>
                            <input disabled autocomplete = "off" id = 'stateOld' class = "form-control" type = "text" name = "state">
                            <div id = "states" class = "container states bg-info mt-2"></div>
                            
                        </div>

                        <div class = "form-group">
                            <label for = "city">City: </label>
                            <input disabled autocomplete = "off" id = 'cityOld' class = "form-control" type = "text" name = "city">
                            <div id = "cities" class = "container cities bg-info mt-2"></div>
                        
                        </div>

                        <div class = "form-group">
                            <div class = "container text-center" id = "wrapImage">
                                <img class = 'img-thumbnail' id = 'imageOld' name = 'image'></img>
                            </div>
                        </div>

                    </div>

                    <div id = 'personalDataNew' class = 'container col col-10 col-sm-6 col-xl-3 col-lg-5'>

                        <div class = 'container bg-primary text-center border rounded'>Pending personal data</div>

                        <div class = "form-group">
                            <label for = "firstName">First name: </label>
                            <input disabled autocomplete = "off" id = 'firstNameNew' class = "form-control" type = "text" name = "firstName">
                        </div>
                            
                        <div class = "form-group">
                            <label for = "lasttName">Last name: </label>
                            <input disabled autocomplete = "off" id = 'lastNameNew' class = "form-control" type = "text" name = "lastName">
                        </div>

                        <div class = "form-group">
                            <label for = "dateOfBirth">Date of birth: </label>
                            <input disabled autocomplete = "off" id = 'dateOfBirthNew' class = "form-control" type = "date" name = "dateOfBirth">
                        </div>

                        <div class = "form-group">
                            <label for = "gender">Gender: </label>
                            <select disabled class = "form-control custom-select" name = "gender" id = "genderNew">
                                <option value = "M">M</option>
                                <option value = "F">F</option>
                            </select>
                        </div>

                        <div class = "form-group">
                            <label for = "address">Address: </label>
                            <input disabled autocomplete = "off" id = 'addressNew' class = "form-control" type = "text" name = "address">
                        </div>

                        <div class = "form-group">
                            <label for = "country">Country: </label>
                            <input disabled autocomplete = "off" id = 'countryNew' class = "form-control" type = "text" name = "country">
                            <div id = "countries" class = "container countries bg-info mt-2"></div>

                        </div>

                        <div class = "form-group">
                            <label for = "state">State: </label>
                            <input disabled autocomplete = "off" id = 'stateNew' class = "form-control" type = "text" name = "state">
                            <div id = "states" class = "container states bg-info mt-2"></div>
                            
                        </div>

                        <div class = "form-group">
                            <label for = "city">City: </label>
                            <input disabled autocomplete = "off" id = 'cityNew' class = "form-control" type = "text" name = "city">
                            <div id = "cities" class = "container cities bg-info mt-2"></div>
                        
                        </div>

                        <div class = "form-group">
                            <div class = "container text-center" id = "wrapImage">
                                <img class = 'img-thumbnail' id = 'imageNew' name = 'image'></img>
                            </div>
                        </div>

                        <div class = "container text-center mb-4 d-flex justify-content-between">
                            <button id = 'rejectPersonalDataChange' class = "btn btn-outline-primary btn-md text-white">Reject</button>
                            <button id = 'acceptPersonalDataChange' class = "btn btn-outline-primary btn-md text-white">Accept</button>
                        </div>

                    </div>

                </div>

            </div>

       </div>

    </body>

</html>