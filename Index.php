<?php
    session_start();

    include './API/mysql.php';

    if (isset($_SESSION['isLogged']) && $_SESSION['isLogged'] == true) {
        $sessId = sendQuery('select sessionId as sid from users where id = unhex(?);', $_SESSION['id'])[0]['sid'];

        if (session_id() != $sessId) {
            session_destroy();
            die(header('location: /404.php'));
        }
    }

?>

<!DOCTYPE html>
<html lang = "ro">
    <head>
        <meta charset = "UTF-8">
        <meta name = "viewport" content = "width = device-width, initial-scale = 1.0">

        <link rel = "stylesheet" type = "text/css" href = "CSS/Index.css">

        <link rel = 'stylesheet' type = 'text/css' href = 'https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css' integrity = 'sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2' crossorigin = 'anonymous'>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        
        <link rel = "icon" href = "Images/Icon.png">

        <script src = "JS/Index.js" defer></script>
        <script src = "JS/Buttons.js" defer></script>

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js" type="text/javascript"></script>
        <script src = 'https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js' integrity = 'sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx' crossorigin = 'anonymous'></script>

        <title>Home</title>
    </head>

    <body class = 'bg-img'>

        <nav id = "navigation_bar" class = "navbar navbar-expand-md navbar-dark bg-blue">
            <a href = '/' class = 'navbar-brand d-md-none font-weight-bold font-italic border border-light p-1 rounded-pill' href = '#'>myBank</a>
            <button class = 'navbar-toggler ml-auto' type = 'button' data-toggle = 'collapse' data-target = '#navbarSupportedContent' aria-controls = 'navbarSupportedContent' aria-expanded = 'false' aria-label = 'Toggle navigation'>
                    <span class = 'navbar-toggler-icon'></span>
            </button>
            
            <div class = 'collapse navbar-collapse mx-auto w-auto justify-content-center' id = 'navbarSupportedContent'>
                <div class = 'navbar-nav mx-auto'>  
                    <ul class = 'navbar-nav mr-auto'>

                        <li class = 'nav-item'>
                            <a id = "home" class = "nav-link active" href = '#'>Home</a>
                        </li>

                        <?php
                            if (isset($_SESSION['isLogged']) && $_SESSION['isLogged'])
                                echo " <li class = 'nav-item'>
                                            <a id = 'onlineBanking' href = '#' class = 'nav-link'>Banking</a>
                                        </li>";
                        ?>
                        
                        <li class = 'nav-item'>
                                <a href = '/' class = 'navbar-brand mx-2 d-none d-md-inline font-weight-bold font-italic border border-light p-1 rounded-pill'>myBank</a>
                        </li>

                        <?php
                            if (isset($_SESSION['isLogged']) && $_SESSION['isLogged'])
                                echo " <li class = 'nav-item'>
                                            <a id = 'settings' href = '#' class = 'nav-link'>Settings</a>
                                        </li>";
                        ?>

                        <li class = 'nav-item'>
                            <a id = "about" class = "nav-link" href = '#'>About</a>
                        </li>
                    </ul>
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

        <div class = 'container bg-info'>
            Hi there!
        </div>

    </body>
</html>