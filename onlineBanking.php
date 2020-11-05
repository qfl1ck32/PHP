<?php
    session_start();
?>

<!DOCTYPE HTML>

<html>

    <head>
    
        <meta charset = 'UTF-8'>
        <meta name = 'viewport' content = 'width = device-width, initial-scale = 1.0'>

        <link rel = 'stylesheet' type = 'text/css' href = 'CSS/onlineBanking.css'>

        <link rel = 'stylesheet' type = 'text/css' href = 'https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css' integrity = 'sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2' crossorigin = 'anonymous'>
        <link rel = 'icon' href = 'Images/Icon.png'>

        <script src = 'https://code.jquery.com/jquery-3.5.1.js'></script>
        
        <script src = 'https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js' integrity = 'sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx' crossorigin = 'anonymous'></script>
        
        <script src = 'JS/onlineBanking.js' defer></script>
        <script src = 'JS/Buttons.js' defer></script> 

        <title>Online Banking</title>

    </head>

    <body class = 'bg-img'>
    
        <nav class = 'navbar navbar-expand-md navbar-dark bg-blue'>
            <a href = '/index.php' class = 'navbar-brand d-md-none font-weight-bold font-italic border border-light p-1 rounded-pill' href = '#'>myBank</a>
            <button class = 'navbar-toggler ml-auto' type = 'button' data-toggle = 'collapse' data-target = '#navbarSupportedContent' aria-controls = 'navbarSupportedContent' aria-expanded = 'false' aria-label = 'Toggle navigation'>
                    <span class = 'navbar-toggler-icon'></span>
            </button>

            <div class = 'd-md-flex d-block w-100'>

                <div class = 'collapse navbar-collapse mx-auto w-auto justify-content-center' id = 'navbarSupportedContent'>
                    
                    <div class = 'navbar-nav mx-auto'>    
                        <ul class = 'navbar-nav mr-auto'>
                            <li class = 'nav-item'>
                                <a id = 'home' class = 'nav-link' href = '/index.php'>Home</a>
                            </li>

                            <?php
                                if (isset($_SESSION['isLogged']) && $_SESSION['isLogged'])
                                    echo " <li class = 'nav-item'>
                                                <a id = 'onlineBanking' href = '#' class = 'nav-link active'>Banking</a>
                                            </li>";
                            ?>
                            
                            <li class = 'nav-item'>
                                <a href = '/index.php' class = 'navbar-brand mx-2 d-none d-md-inline font-weight-bold font-italic border border-light p-1 rounded-pill'>myBank</a>
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
            <button id = 'logout' class = 'btn btn-outline-primary btn-sm border rounded-pill text-white'>Logout</button>
        </div>

      
       <div class = 'container bg-info'>
           Hi!
       </div>

    </body>

</html>