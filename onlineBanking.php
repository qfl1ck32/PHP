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
                        . "<br>Otherwise, press the 'Simulate' button again to send from current chosen credit card.", $checkHaveReceiverCurrency, 0);
                    }

                    $receiverId = sendQuery('select hex(id) as id from creditcards where IBAN = ?', $_POST['toIBAN'])[0]['id'];
                    $checkHasSenderCurrency = sendQuery('select IBAN from creditcards where id = unhex(?) and substr(iban, 9, 3) = ?;', $receiverId, $senderCurrency);

                    if (isset($checkHasSenderCurrency[0]))
                        return Status(-1, "The receiver has other credt card(s) with the same currency.<br>If you wish to send money to one of those, feel free to do so by clicking one of them."
                        . "<br>Otherwise, press the 'Simulate' button again to send from current chosen credit card.", $checkHasSenderCurrency, 1);

                    return Status(false, "The other user has no " . $senderCurrency . " credit card, and neither do you have a " . $receiverCurrency . " one.<br>If you wish to automatically convert the currencies <br>and send "
                                        . round($convertedCurrency * $_POST['amount'], 2) . ' ' . $receiverCurrency . ', please hit the "Simulate" button again.<br>'
                                        . "1 " . $senderCurrency . ' = ' . $convertedCurrency . ' ' . $receiverCurrency . ' (' . $date . ')');

                }
            }

            if ($amountToSend == 0)
                $amountToSend = $_POST['amount'];

            $transactionReference = strtoupper(bin2hex(random_bytes(16)));

            sendQuery('update creditcards set balance = balance + ? where iban = ?;', $amountToSend, $_POST['toIBAN']);
            sendQuery('update creditCards set balance = balance - ? where iban = ?;', $_POST['amount'], $_POST['fromIBAN']);

            $sendBalance = sendQuery('select balance from creditcards where iban = ?;', $_POST['fromIBAN'])[0]['balance'];
            $receiveBalance = sendQuery('select balance from creditcards where iban = ?;', $_POST['toIBAN'])[0]['balance'];

            $type1 = "Sent money";
            $description1 = $_POST['description'];

            $type2 = "Received money";
            $description2 = $description1;

            sendQuery('insert into transactions values (?, ?, ?, curdate(), ?, ?, ?), (?, ?, ?, curdate(), ?, ?, ?);', 
                        $transactionReference, $_POST['fromIBAN'], $type1, $description1, $_POST['amount'], $sendBalance,
                        $transactionReference, $_POST['toIBAN'], $type2, $description1, $_POST['amount'], $receiveBalance);

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

        $data = sendQuery('select type, currency, balance from creditcards where id = unhex(?) and iban = ?', $_SESSION['id'], $_POST['IBAN']);

        if (!isset($data[0]))
            return Status(false, "The given IBAN either does not exist or it does not belong to any of your credit cards.");

        $transactions = sendQuery('select date, type, description, amount, balance, hex(reference) as reference from transactions where iban = ?', $_POST['IBAN']);

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

    $currentCreditCards = sendQuery('select iban, type, currency, balance from creditcards where id = unhex(?);', $_SESSION['id']);

    if (sizeof($currentCreditCards))
        $currentTransactions = sendQuery('select type from transactions where iban = ?', $currentCreditCards[0]['iban']);

    $currencies = sendQuery('select currency from currencies;');

    $currencyWithImg = array();

    

    foreach ($currencies as $currentCurrency) {
        $name = $currentCurrency['currency'];
        $img = glob('Images/countryFlags/' . substr($name, 0, 2) . '.png');

        $currencyWithImg[] = array('name' => $name, 'src' => $img[0]);
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

                            <?php
                                for ($i = 0; $i < sizeof($currentCreditCards); ++$i) { 
                            ?>

                                <a href = '#' class = 'creditCard list-group-item list-group-item-action list-group-item-info border rounded text-center mr-lg-0 mr-2 <?php echo ($i == 0) ? 'active' : 'mt-lg-2 mt-sm-0'; ?>'>

                                    <div class = 'row text-left'>
                                        <div class = 'col-9'>
                                            <div class = 'row'>
                                                <small class = 'pre'><?php echo $currentCreditCards[$i]['type']; ?></small>
                                            </div>
                                            <div class = 'row'>
                                                <small class = 'creditCardListBalance' class = 'text-muted'><?php echo $currentCreditCards[$i]['currency']; ?> [ <?php echo $currentCreditCards[$i]['balance']; ?> ]</small>   
                                            </div>
                                        </div>
                                        
                                        <div class = 'col text-right'>
                                            <small><img style = 'width: 15px; height: 15px;' class = 'rounded' src = <?php echo "./Images/countryFlags/" . substr($currentCreditCards[$i]['currency'], 0, 2) . ".png"; ?> ></small>
                                        </div>
                                    </div>

                                    <div class = 'row justify-content-start mt-2 text-break-lg'>
                                        <small class = 'IBAN' ><?php echo $currentCreditCards[$i]['iban']; ?></small>
                                    </div>
                                
                                </a>

                            <?php } ?>

                        </ul>

                        <div class = 'container pt-4 text-center'>
                            <button id = 'createCard' class = 'btn btn-outline-primary btn-sm border rounded-pill text-white' data-toggle = 'modal' data-target = '#modalCenter'>Create a new credit card</button>
                            <?php if (sizeof($currentCreditCards)) { ?>
                                <button id = 'simulateTransaction' class = 'btn btn-outline-primary btn-sm border rounded-pill text-white mt-2 mt-lg-2 mt-sm-0' data-toggle = 'modal' data-target = '#modalCenter3'>Simulate a transaction</button>
                                
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
                                                        <input class = 'form-control'>
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
                <?php } ?>

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
                                                                echo "<option value = '" . $curr['name'] . "'>" . $curr['name'] . "</option>\n";
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

                        <?php if (isset($currentCreditCards[0])) { ?>

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


                            <?php } else { ?>

                                <div class = 'container text-center text-white'>

                                    <h4>Uh-oh!</h4>

                                    <hr>

                                        <h6>It seems like you got no credit cards.</h6>

                                        <h6>You can create a new one using the <i><b>Create a new credit card</b></i> button.</h6>
                                    
                                </div>

                            <?php } ?>

                    <?php } else { ?>

                        <div class = 'container text-center text-white'>
                            <h4>Uh-oh!</h4>

                            <h6>It seems like you haven't put any information in the settings tab.</h6>
                            <h6>Please fill in the needed data and wait for an administrator to approve the changes.</h6>

                        </div>

                    <?php } ?>

               </div>

           </div>
       </div>

    </body>

</html>