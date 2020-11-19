<?php
    session_start();

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

            $creditCards = sendQuery('select iban, type, (select name from currencies where id = currencyId) currency, balance from creditCards where id = unhex(?);', $_SESSION['id']);

            $cc = array();

            foreach ($creditCards as $creditCard) {
                $cc[] = array('IBAN' => $creditCard['iban'], 'type' => $creditCard['type'], 'currency' => $creditCard['currency'], 'balance' => $creditCard['balance']);
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

            $creditCard = sendQuery('select balance, (select name from currencies where id = currencyId) currency from creditcards where id = unhex(?) and iban = ?;', $_SESSION['id'], $IBAN);

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

            $checkIsHisIban = sendQuery('select balance from creditcards where IBAN = ? and id = unhex(?);', $_POST['fromIBAN'], $_SESSION['id']);

            if (!isset($checkIsHisIban[0])) {
                return Status(false, "Invalid sender IBAN.");
            }

            if ($_POST['amount'] > $checkIsHisIban[0]['balance'])
                return Status(false, "You are trying to send more money than your current balance.");

            $checkIbanExists = sendQuery('select hex(id) as id from creditcards where IBAN = ?;', $_POST['toIBAN']);

            if (!isset($checkIbanExists))
                return Status(false, "The given IBAN does not belong to anyone.");

            if ($checkIbanExists[0]['id'] === $_SESSION['id'])
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
                
                    $checkHaveReceiverCurrency = sendQuery('select IBAN from creditcards where id = unhex(?) and substr(iban, 9, 3) = ?;', $_SESSION['id'], $receiverCurrency);

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

            $senderName = sendQuery("select concat(firstName, ' ', lastName) name from personalData where id = unhex(?);", $_SESSION['id'])[0]['name'];
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

            if (isset($checkIbanIsSelf[0]) && $checkIbanIsSelf[0]['id'] == $_SESSION['id'])
                return Status(false, "You can not send money to yourself.");

            $ibanExists = sendQuery('select concat(firstName, " ", lastName) as name from personalData where id = (select id from creditcards where IBAN = ?);', $_POST['IBAN']);

            if (!sizeof($ibanExists))
                return Status(false, "There is no credit card account with the given IBAN.");

            return Status(true, $ibanExists[0]['name']);
        }

        $data = sendQuery('select type, (select name from currencies where id = currencyId) currency, balance from creditcards where id = unhex(?) and iban = ?', $_SESSION['id'], $_POST['IBAN']);

        if (!isset($data[0]))
            return Status(false, "The given IBAN either does not exist or it does not belong to any of your credit cards.");

        $transactions = sendQuery('select date, type, description, amount, balance, hex(reference) as reference from transactions where iban = ? order by date desc;', $_POST['IBAN']);

        $data = $data[0];

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

    $hasSettings = sendQuery('select count(*) as c from personaldata where id = unhex(?)', $_SESSION['id'])[0]['c'];

    $currentCreditCards = sendQuery('select iban, type, (select name from currencies where id = currencyId) currency, balance from creditcards where id = unhex(?);', $_SESSION['id']);

    if (sizeof($currentCreditCards))
        $currentTransactions = sendQuery('select type from transactions where iban = ?', $currentCreditCards[0]['iban']);

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

        <link rel = 'stylesheet' type = 'text/css' href = 'CSS/onlineBanking.css'>
        <link rel = 'stylesheet' type = 'text/css' href = 'CSS/Animations.css'>

        <link rel = 'stylesheet' type = 'text/css' href = 'https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css' integrity = 'sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2' crossorigin = 'anonymous'>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
        
        <link rel = 'icon' href = 'Images/Icon.png'>

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js" type="text/javascript"></script>
        <script src = 'https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js' integrity = 'sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx' crossorigin = 'anonymous'></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>

        <script src="https://kit.fontawesome.com/7218cc0d0e.js" crossorigin="anonymous"></script>

        <script src = 'JS/onlineBanking.js' defer></script>
        <script src = 'JS/Buttons.js' defer></script> 

        <title>Online Banking</title>

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
                                                <a id = 'onlineBanking' href = '#' class = 'nav-link active'>Banking</a>
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
                    echo '<button id = "admin" class = "btn btn-outline-primary btn-sm border rounded-pill text-white mr-2">Administrate accounts</button>';

                echo "<button id = 'logout' class = 'btn btn-outline-primary btn-sm border rounded-pill text-white'>Logout</button>";

            ?>

        </div>
      
       <div id = 'mainDiv' class = 'container'>

            <div class = 'container d-flex justify-content-center text-light'>
                <h1 class = 'display-5 font-italic'>Online Banking</h1>
            </div>

            <hr class = 'mt-0'>

           <div class = 'row py-4'>
               <?php if ($hasSettings) { ?>
                    <div style = 'overflow: hidden;' class = 'col-12 col-lg-3 offset-lg-0'>
                        <ul id = 'creditCardsList' style = 'max-height: 288px; overflow-y: scroll;' class = 'list-group flex-lg-column flex-row pb-2 pr-2'>


                        </ul>

                        <div class = 'container pt-4 text-center'>
                            <button id = 'createCard' class = 'btn btn-outline-primary btn-sm border rounded-pill text-white' data-toggle = 'modal' data-target = '#modalCenter'>Create a new credit card</button>
                            
                            <button id = 'simulateTransaction' class = 'btn btn-outline-primary btn-sm border rounded-pill text-white mt-0 mt-lg-2 mt-sm-0' data-toggle = 'modal' data-target = '#modalCenter3'>Simulate a transaction</button>
                            
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
                                                    <button class = 'btn btn-outline-primary btn-md border rounded-pill active' id = 'sendMoney'>Send money</button>
                                                    <button class = 'btn btn-outline-primary btn-md border rounded-pill' id = 'buyItem'>Buy item</button>
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
                            
                        <?php } ?>
                    </div>
                </div>

               <div class = 'col-lg border rounded offset-lg-0 mt-4 mt-lg-0 p-4 mx-4'>

                    <?php if ($hasSettings) { ?>
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

                            <div class = 'd-flex justify-content-around mb-4'>
                                <button class = 'btn btn-outline-primary btn-md border rounded-pill text-white active' id = 'details'>Details</button>
                                <button class = 'btn btn-outline-primary btn-md border rounded-pill text-white' id = 'transactions'>Transactions</button>
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
                                    <div id = 'spinner' class = 'spinner-grow    text-primary' role = 'status'></div>
                                </div>
                            </div>

                            
                            <div class = 'container' id = 'creditCardTransactionsData'>
                                <div id = 'missingTransactions' class = 'text-white text-center'>
                                </div>

                                <div class = 'text-white'>
                                    <ul id = 'transactionsList' class = 'list-group' style = 'max-height: 288px; overflow-y: scroll;'></ul>
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

                        </div>

                            <div id = 'missingCreditCards' class = 'container text-center text-white'>

                                <h4>Uh-oh!</h4>

                                <hr>

                                    <h6>It seems like you got no credit cards.</h6>

                                    <h6>You can create a new one using the <i><b>Create a new credit card</b></i> button.</h6>
                                
                            </div>

                    <?php } else { ?>

                        <div class = 'container text-center text-white'>
                            <h4>Uh-oh!</h4>

                            <h6>It seems like you haven't put any information in the "Personal data" tab.</h6>
                            <h6>Please fill in the needed data and wait for an administrator to approve the changes.</h6>

                        </div>

                    <?php } ?>

               </div>

           </div>
       </div>

    </body>

</html>