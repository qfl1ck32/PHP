<?php
    session_start();
    include './API/mysql.php';
    include './API/functions.php';

    if (!$_SESSION['resetPassword'])
        die(header('location: /404.php'));


    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        if (!isset($_POST['password']) || !isset($_POST['confirmPassword']) || !$_POST['password'])
            ext('Missing fields.');
        
        if ($_POST['password'] != $_POST['confirmPassword'])
            ext('Passwords do not match.');

        $email = $_SESSION['email'];

        $id = sendQuery('select hex(id) from users where email = ?;', $email)[0]['hex(id)'];

        $hashedPassword = password_hash($_POST['password'], PASSWORD_BCRYPT);

        sendQuery('update users set password = ? where id = unhex(?);', $hashedPassword, $id);
        unset($_SESSION['resetPassword']);

        die(json_encode(array(
            'success' => true,
            'message' => 'Done! You have succesfully changed your password.<br>You can now log in with the new credentials.'
        )));
    }
?>

<!DOCTYPE html>
<html lang = "ro">
    <head>
        <meta charset = "UTF-8">

        <meta name = "viewport" content = "width = device-width, initial-scale = 1.0">

        <link rel = "stylesheet" type = "text/css" href = "CSS/Reset.css">
        
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel = 'stylesheet' type = 'text/css' href = 'https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css' integrity = 'sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2' crossorigin = 'anonymous'>

        <link rel = "icon" href = "Images/Icon.png">
        
        <script src = "JS/Reset.js" defer></script>
        <script src = "JS/Buttons.js" defer></script>


        <script src='https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js' type='text/javascript'></script>

        <script src = '//code.jquery.com/jquery-3.5.1.js'></script>
        <script src = '//code.jquery.com/ui/1.12.1/jquery-ui.js'></script>  
        
        <script src = 'https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js' integrity = 'sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx' crossorigin = 'anonymous'></script>

        <title>Reset</title>
    </head>

    <body class = 'bg-img'>


    <nav id = 'navigation_bar' class = 'navbar navbar-expand-md navbar-dark bg-blue mb-2'>
            <a href = '/Index.php' class = 'navbar-brand d-md-none font-weight-bold font-italic border border-light p-1 rounded-pill' href = '#'>myBank</a>
            <button class = 'navbar-toggler ml-auto' type = 'button' data-toggle = 'collapse' data-target = '#navbarSupportedContent' aria-controls = 'navbarSupportedContent' aria-expanded = 'false' aria-label = 'Toggle navigation'>
                    <span class = 'navbar-toggler-icon'></span>
            </button>
            
            <div class = 'collapse navbar-collapse mx-auto w-auto justify-content-center' id = 'navbarSupportedContent'>
                <div class = 'navbar-nav mx-auto'>  
                    <ul class = 'navbar-nav mr-auto'>

                        <li class = 'nav-item'>
                            <a id = 'home' class = 'nav-link active' href = '/Index.php'>Home</a>
                        </li>
                        
                        <li class = 'nav-item'>
                                <a href = '/Index.php' class = 'navbar-brand mx-2 d-md-inline font-weight-bold font-italic border border-light p-1 rounded-pill'>myBank</a>
                            </li>

                        <li class = 'nav-item'>
                            <a id = 'about' class = 'nav-link' href = '#'>About</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class = 'container-fluid text-right'>
            <button id = 'login' class = 'btn btn-outline-primary btn-sm border rounded-pill active my-4'>Sign in</button>
        </div>

        <div class = "container col-10 col-sm-6 col-xl-3 col-lg-5 border border-white rounded">

            <div class = "text-center text-white font-weight-bold pt-3">
                Reset password
            </div>

            <p id = 'message' class = 'alert alert-danger'></p>

            <div class = 'container text-white'>
                <form class = "reset" id = "reset_form" action = "/Reset.php" method = "POST">


                    <div class = "form-group">
                        <label for = "password">Password: </label>
                        <input id = "password" class = "form-control" type = "password" name = "password">
                    </div>

                    <div class = "form-group">
                        <label for = "confirm_password">Confirm password: </label>
                        <input id = "confirm_password" class = "form-control" type = "password" name = "confirm_password">
                    </div>

                    <div id = "password_pattern" class = "container alert alert-danger">
                        Your password should contain at least:
                        <div class = "container">
                            <div class = "container" id = "password_should_contain">
                            </div>
                        </div>
                    </div>

                    <div id = "password_match" class = "alert alert-danger">
                        Passwords do not match.
                    </div>

                    <div id = "emptyFields" class = "alert alert-danger">
                        There are still empty fields.
                    </div>

                    <div class = "container text-center">
                        <button id = "reset_button" class = "btn btn-outline-primary btn-lg mb-4 text-white" type = "submit">Change password</button>
                    </div>


                </form>

            </div>

        </div>

    </body>
</html>