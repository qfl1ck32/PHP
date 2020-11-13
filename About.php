<?php
    session_start();

    if (isset($_SESSION['isLogged']) && $_SESSION['isLogged'] == true) {
        $sessId = sendQuery('select sessionId from users where id = unhex(?);', $_SESSION['id']);

        if (session_id() != $sessId) {
            session_destroy();
            die(header('location: /404.php'));
        }
    }
?>

<!DOCTYPE HTML>

<html>

    <head>
    
        <meta charset = 'UTF-8'>
        <meta name = 'viewport' content = 'width = device-width, initial-scale = 1.0'>

        <link rel = 'stylesheet' type = 'text/css' href = 'CSS/About.css'>
        <link rel = 'stylesheet' type = 'text/css' href = 'https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css' integrity = 'sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2' crossorigin = 'anonymous'>
        <link rel = 'icon' href = 'Images/Icon.png'>

        <script src = 'https://code.jquery.com/jquery-3.5.1.js'></script>
        
        <script src = 'https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js' integrity = 'sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx' crossorigin = 'anonymous'></script>
        <script src = 'JS/About.js' defer></script>
        <script src = 'JS/Buttons.js' defer></script> 

        <title>About</title>

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
                                                <a id = 'settings' href = '#' class = 'nav-link'>Settings</a>
                                            </li>";
                            ?>

                            <li class = 'nav-item'>
                                <a id = 'about' class = 'nav-link active' href = '#'>About</a>
                            </li>
                        </ul>
                    </div>

                </div>

            </div>

        </nav>

        <div class = "container-fluid text-right my-4">
            <?php

                if (isset($_SESSION['admin']) && $_SESSION['admin'])
                    echo '<button id = "admin" class = "btn btn-outline-primary btn-sm border rounded-pill text-white mr-2">Administrate accounts</button>';

                if (isset($_SESSION['isLogged']) && $_SESSION['isLogged'])
                    echo '<button id = "logout" class = "btn btn-outline-primary btn-sm border rounded-pill text-white">Logout</button>';
                else
                    echo '<button id = "login" class = "btn btn-outline-primary btn-sm border rounded-pill text-white">Sign in</button>';
            ?>
        </div>

      
        <div id = 'whatDoWeDo' class = 'container'>
            <div class = 'container d-flex justify-content-center text-light'>
                <h1 class = 'display-5 font-italic'>What do we do?</h1>
            </div>

            <hr class = 'mt-0'>

            <div class = 'container text-light mt-4'>

                <div class = 'row'>
                    <div class = 'col-8 offset-2 col-sm-4 col-lg-3 offset-sm-4 offset-lg-0'>
                        <div class = 'list-group text-dark text-center border rounded'>
                            <a id = 'loginSystemButton' href = '#' class = 'list-group-item list-group-item-action active infobtn'>Login system</a>   
                            <a id = 'settingsButton' href = '#' class = 'list-group-item list-group-item-action infobtn'>Settings</a>   
                            <a id = 'onlineBankingButton' href = '#' class = 'list-group-item list-group-item-action infobtn'>Online banking</a>
                        </div>
                    </div>

                    <div class = 'col-lg offset-lg-0 text-center mt-4 mt-lg-0 pb-4'>
                        <div class = 'bg-info py-2 border rounded'>
                            <div id = 'loginSystemInfo' class = 'container currentInfo'>
                                <h4 class = 'font-italic text-left'>Login system</h4>
                                <hr>
                                <h6 class = 'font-italic text-left mx-2'>At the "Sign-In" page you will find three main components:</h6>
                                <h6 class = 'font-italic text-left mx-4'>Sign-up, Sign-in and Reset Password.</h6>
                                
                                <h4 class = 'font-italic text-left mt-5'>Sign-up</h4>
                                <hr>
                                <h6 class = 'font-italic text-left mx-2'>All you need to do is - pass in a nickname, an e-mail and a password. Can it get more friendly?</h6>

                                <h4 class = 'font-italic text-left mt-5'>Sign-in</h4>
                                <hr>
                                <h6 class = 'font-italic text-left mx-2'>After signing up, you will receive a confirmation e-mail.</h6>

                                <div class = 'container text-left'>
                                    <small class = 'small-text text-white'>Note!</small>
                                </div>

                                <h6 class = 'font-italic text-left mx-2 alert alert-info'>If the e-mail doesn't get to you in 10 minutes, sign-in in order to be able to resend the e-mail.</h6>
                                
                                <div class = 'container text-left'>
                                    <small class = 'small-text text-white'>Another note!</small>
                                </div>
                                
                                <h6 class = 'font-italic text-left mx-2 alert alert-info'>After settings up your account for the first time, you should set up all the details about you in the Settings tab.</h6>

                                <h4 class = 'font-italic text-left mt-5'>Reset password</h4>
                                <hr>
                                <h6 class = 'font-italic text-left mx-2'>If, for whatever reason, you want to change your password - either cause you forgot it, or just for giggles - you can at any time enter the
                                    Reset Password tab, input either your username or e-mail and check your inbox for a link that shall help you in this direction.</h6>
                            </div>

                            <div id = 'settingsInfo' class = 'container'>
                                <h4 class = 'font-italic text-left'>Settings</h4>
                                <hr>

                                <h6 class = 'font-italic text-left mx-2'>Here is where every piece of personal detail about you lays.</h6>
                                <h6 class = 'font-italic text-left mx-2'>Mainly, before creating any credit card, you must fill this form out.</h6>
                                
                                <h6 class = 'font-italic text-left mx-2'>After filling up all the needed data, you have to upload a picture of an identification card.

                                <h6 class = 'font-italic text-left mx-2'>Also, keep in mind that every time you add any slight modification to it, an administrator will have to look into it and approve it before
                                    you'll be able to use any functionality again.</h6>

                                <div class = 'container text-left'>
                                    <small class = 'small-text text-white'>Note!</small>
                                </div>

                                <h6 class = 'font-italic text-left mx-2 alert alert-info'>After completing the form for the first time, try not to play in the tab unless needed. Obvious stuff, right?</h6>
                            </div>

                            <div id = 'onlineBankingInfo' class = 'container'>

                                <h4 class = 'font-italic text-left'>Online Banking</h4>
                                <hr>

                                <h6 class = 'font-italic text-left mx-2'>Once you are logged in, you'll get access to the main functionality of this application - Online Banking. Let's get to analyze this.</h6>
                                
                                <div class = 'container text-left'>
                                    <small class = 'small-text text-white'>Note!</small>
                                </div>

                                <h6 class = 'font-italic text-left mx-2 alert alert-info'>In order to be able to use anything from here on, you must have your personal details filled in (check the Settings tab).

                                <h6 class = 'font-italic text-left mx-2'>Here is where you'll spend most of your time in this application. There are a few features I want to tell you about, such as:</h6>
                                <h6 class = 'font-italic text-left mx-4'>Generating new credit cards, sending money, and the almighty spreadsheet containing all information about any transaction that have happened
                                    on any of your accounts.</h6>

                                <h4 class = 'font-italic text-left mt-5'>Generating new credit cards</h4>
                                <hr>

                                <h6 class = 'font-italic text-left mx-2'>At any point, you can attach a new credit card to your account, by telling us a minimalistic amount of information about it, like currency and type.</h6>
                                
                                <div class = 'container text-left'>
                                    <small class = 'small-text text-white'>Note!</small>
                                </div>

                                <h6 class = 'font-italic text-left mx-2 alert alert-info'>You can have only one credit card for a specific type of currency.</h6>

                                <h4 class = 'font-italic text-left mt-5'>Sending money</h4>
                                <hr>

                                <h6 class = 'font-italic text-left mx-2'>One important aspect of online banking is the ability to send money without waiting at any physical bank.</h6>
                                <h6 class = 'font-italic text-left mx-2'>For this specific reason, we've built a friendly interface for you to use with ease when in need of transferring some money.</h6>
                                <h6 class = 'font-italic text-left mx-2'>The only things you need to fill in are the following:</h6>
                                
                                <h6 class = 'font-itaic text-left mx-4'>The IBAN of the person you want to send money to, his/her name, an amount of a specific currency and a message.</h6>

                                <div class = 'container text-left'>
                                    <small class = 'small-text text-white'>Note!</small>
                                </div>

                                <h6 class = 'font-italic text-left mx-2 alert alert-info'>If the currency you're trying to send is not the same as the receiver's credit card's, you can choose to
                                    automatically exchange the money. Otherwise, the transfer can not be done, and you'll get an alert about this.
                                </h6>

                                <h4 class = 'font-italic text-left mt-5'>Spreadsheet</h4>
                                <hr>

                                <h6 class = 'font-italic text-left mx-2'>Another important feature of online banking is being able to see all the details about any of your credit cards in real time.</h6>
                                <h6 class = 'font-italic text-left mx-2'>In order to make this experience as friendly as possible, we have created the simplest yet most complete page where you can
                                    find out anything about your credit cards.
                                </h6>

                            </div>
                        </div>
                    </div>
                </div>

            </div>
    
        </div>

    </body>

</html>