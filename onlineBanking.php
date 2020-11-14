<?php
    session_start();

    include './API/mysql.php';

    if (!isset($_SESSION['isLogged']) || !$_SESSION['isLogged'])
        die(header('location: /404.php'));

        $sessId = sendQuery('select sessionId as sid from users where id = unhex(?);', $_SESSION['id'])[0]['sid'];

    if (session_id() != $sessId) {
        session_destroy();
        die(header('location: /404.php'));
    }

    include './API/functions.php';
    include './API/mysql.php';

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        $data = sendQuery('select type, currency, balance from creditcards where id = unhex(?) and iban = ?', $_SESSION['id'], $_POST['IBAN']);

        if (!isset($data[0]))
            return Status(false, "The given IBAN either does not exist or it does not belong to any of your credit cards.");

        $data = $data[0];

        die(json_encode(array(
            'status' => true,
            'type' => $data['type'],
            'currency' => $data['currency'],
            'balance' => $data['balance'] . ' ' . $data['currency']
        )));
    }

    $hasSettings = sendQuery('select count(*) as c from personaldata where id = unhex(?)', $_SESSION['id'])[0]['c'];

    $currentCreditCards = sendQuery('select iban, type, currency, balance from creditcards where id = unhex(?);', $_SESSION['id']);

    $currencies = sendQuery('select name from currencies;');

    $currencyWithImg = array();

    

    foreach ($currencies as $currentCurrency) {
        $name = $currentCurrency['name'];
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
                                                <a id = 'settings' href = '#' class = 'nav-link'>Settings</a>
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

      
       <div class = 'container'>

            <div class = 'container d-flex justify-content-center text-light'>
                <h1 class = 'display-5 font-italic'>Online Banking</h1>
            </div>

            <hr class = 'mt-0'>

           <div class = 'row py-4'>
               <?php if ($hasSettings) { ?>
                    <div style = 'overflow: hidden;' class = 'col-12 col-lg-3 offset-lg-0 text-white'>
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
                                                <small class = 'text-muted'><?php echo $currentCreditCards[$i]['currency']; ?> [ <?php echo $currentCreditCards[$i]['balance']; ?> ]</small>   
                                            </div>
                                        </div>
                                        
                                        <div class = 'col text-right'>
                                            <small><img style = 'width: 10px; height: 10px;' class = 'rounded' src = <?php echo "./Images/countryFlags/" . substr($currentCreditCards[$i]['currency'], 0, 2) . ".png"; ?> ></small>
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
                        </div>
                    </div>
                <?php } ?>


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
                           
                                <div id = 'messageCreateCreditCard'></div>

                               <form>
                                   <div class = 'form-group'>
                                        <label for = 'currency'>Currency</label>
                                        <select data-live-search = 'true' data-live-search-style = 'startsWith' class = 'form-control selectpicker show-tick' id = 'createCardWithCurrency' name = 'currency'>
                                           <?php
                                                foreach ($currencyWithImg as $curr) {
                                                    echo "<option value = '" . $curr['name'] . "'>" . $curr['name'] . "</option>";
                                                }
                                           ?>
                                       </select>
                                   </div>
                               </form>
                           </div>

                           <div class = 'modal-footer'>
                               <button type = 'button' class = 'btn btn-secondary' data-dismiss = 'modal'>Close</button>
                               <button id = 'createCreditCard' type = 'button' class = 'btn btn-primary'>Create</button>
                           </div>

                       </div>
                   </div>
               </div>

               <div class = 'col-lg border rounded offset-lg-0 mt-4 mt-lg-0 p-4 mx-4'>


                    <?php if ($hasSettings) { ?>
                        <?php if (isset($currentCreditCards[0])) { ?>

                            <div class = 'd-flex justify-content-around mb-4'>
                                <button class = 'btn btn-outline-primary btn-md border rounded-pill text-white active' id = 'details'>Details</button>
                                <button class = 'btn btn-outline-primary btn-md border rounded-pill text-white' id = 'transactions'>Transactions</button>
                            </div>
                        
                            <hr>

                            <div class = 'container' id = 'creditCardMainData'>
                                    <div class = 'text-white'>
                                        <h5 class = 'font-weight-bold'>Account type</h5>
                                        <h6 class = 'ml-2 font-italic' id = 'accountType'><?php echo $currentCreditCards[0]['type']; ?></h6>
                                    </div>

                                    <div class = 'text-white mt-4'>
                                        <h5 class = 'font-weight-bold'>IBAN</h5>
                                        <h6 class = 'ml-2 font-italic' id = 'IBAN'><?php echo $currentCreditCards[0]['iban']; ?></h6>
                                    </div>

                                    <div class = 'text-white mt-4'>
                                        <h5 class = 'font-weight-bold'>Currency</h5>
                                        <h6 class = 'ml-2 font-italic' id = 'currency'><?php echo $currentCreditCards[0]['currency']; ?></h6>
                                    </div>

                                    <hr>

                                    <div class = 'text-white mt-4'>
                                        <h5 class = 'font-weight-bold'>Available balance</h5>
                                        <h6 class = 'ml-2 font-italic' id = 'balance'><?php echo $currentCreditCards[0]['balance'] . ' ' . $currentCreditCards[0]['currency']; ?></h6>
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