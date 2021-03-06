<?php
    include './API/mysql.php';
    include './API/functions.php';

    session_start();

    if (isset($_SESSION['isLogged']) && $_SESSION['isLogged'] == true)
        die(header('location: /'));

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        if (!isset($_POST['message'])) {

            if (!isset($_POST['data']) || !isset($_POST['password']) || !isset($_POST['signInReplace']) || !$_POST['data'] || !$_POST['password']) // !isset($_POST['remember'])
                return Status(false, 'Missing credentials.');

            $usernameEmail = $_POST['data'];
            $password = $_POST['password'];
            $type = strpos($usernameEmail, '@') ? 'email' : 'username';

            $query = $conn->prepare('select hex(id) as id, username, email, sessionId, password from users where ' . $type . ' = ?');
            $query->bind_param('s', $usernameEmail);
            $query->execute();
            $query->bind_result($id, $username, $email, $sessionId, $dbPass);
            $query->fetch();
            $query->close();

            if (!$dbPass)
                return Status(false, 'There is no user with the given credentials.');

            if (!password_verify($password, $dbPass))
                return Status(false, 'Wrong password.');

            if ($sessionId != "0") {
                if ($_POST['signInReplace'] == "true") {
                    $oldSessId = sendQuery('select sessionId from users where ' . $type . ' = ?', $usernameEmail)[0];
                    session_id($oldSessId['sessionId']);
                    session_unset();
                    session_destroy();

                    session_start();
                }

                else
                    return Status(2, 'User is already connected.<br>If you wish to login from this device,<br>press the login button once more.');
            }

            // if ($_POST['remember']) {
            //     setcookie('active', time() + 3600 * 24);
            // }
            // else {
            //     setcookie('active', time() - 3600 * 24);
            // }

            $query = $conn->prepare('select 1 from toConfirm where id = unhex(?)');
            $query->bind_param('s', $id);
            $query->execute();
            $query->bind_result($notConfirmed);
            $query->fetch();
            $query->close();

            if ($notConfirmed) {
                $arg = "'" . $email . "'";
                return Status(false, 'Your account has not been confirmed. Check your e-mail at ' . $email . '.<br>If you need to re-send the confirmation e-mail, <b><a class="resendLink" href = "#" onclick="resendVerification(' . $arg . ')">click here</a></b>.');
            }

            $sessId = session_id();

            $query = $conn->prepare('update users set sessionId = ? where ' . $type . ' = ?');
            $query->bind_param('ss', $sessId, $usernameEmail);
            $query->execute();
            $query->close();

            $isAdmin = sendQuery('select count(*) as c from admins where id = unhex(?);', $id)[0]['c'];
            
            if ($isAdmin)
                $_SESSION['admin'] = true;
            
            $_SESSION['isLogged'] = true;
            $_SESSION['id'] = $id;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            
            return Status(true);
        }
    }

?>

<!DOCTYPE html>
<html lang = 'ro'>
    <head>
        <meta charset = 'UTF-8'>
        <meta name = 'viewport' content = 'width = device-width, initial-scale = 1.0'>

        <link rel = 'stylesheet' type = 'text/css' href = 'CSS/Login.css'>
        <link rel = 'stylesheet' type = 'text/css' href = 'CSS/Animations.css'>

        <link rel = 'stylesheet' type = 'text/css' href = 'https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css' integrity = 'sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2' crossorigin = 'anonymous'>
        <link rel = 'icon' href = 'Images/Icon.png'>
        

        <script src = 'JS/Login.js' defer></script>
        <script src = 'JS/Buttons.js' defer></script>

        <script src='https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js' type='text/javascript'></script>

        <script src = '//code.jquery.com/jquery-3.5.1.js'></script>
        <script src = '//code.jquery.com/ui/1.12.1/jquery-ui.js'></script>  
        
        <script src = 'https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js' integrity = 'sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx' crossorigin = 'anonymous'></script>


        <title>Login</title>
    </head>

    <body class = 'bg-img'>

        <nav id = 'navigation_bar' class = 'navbar navbar-expand-md navbar-dark bg-blue'>
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
                            <a href = '/Index.php' class = 'navbar-brand mx-2 d-none d-md-inline font-weight-bold font-italic border border-light p-1 rounded-pill'>myBank</a>
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


        <div class = 'container col-10 col-sm-6 col-xl-3 col-lg-5 border border-white rounded'>
            <div class = 'text-center text-white font-weight-bold pt-3'>
                Login
            </div>

            <div class = 'container text-white'>
                <form class = 'login formTab active' id = 'loginForm' action = '/Login.php' method = 'POST'>

                    <div id = 'message' class = 'alert alert-success text-center mb-4 mt-4'>
                        <?php
                            if (isset($_SESSION['confirmation'])) { 
                                echo 'Your account has been succesfully verified. You can now log in.'; 
                                unset($_SESSION['confirmation']); 
                            }
                            
                            elseif (isset($_POST['message'])) {
                                echo $_POST['message'];
                            }
                        ?>
                    </div>

                 
                    <div class = 'form-group'>
                        <label for = 'username_email'>Username / Email: </label>
                        <input autocomplete = 'off' id = 'usernameEmailLogin' class = 'form-control' type = 'text' name = 'data'>
                    </div>

                    <div class = 'form-group'>
                        <label for = 'password'>Password: </label>
                        <input id = 'passwordLogin' class = 'form-control' type = 'password' name = 'password'>
                    </div>

                    <!-- <div class = 'form-group text-center'>
                        <label for = 'remember'>Remember me: </label>
                        <input id = 'rememberMe' type = 'checkbox' name = 'remember'>
                    </div> -->

                    <div class = 'container text-center my-4'>
                        <button id = 'loginButton' disabled class = 'btn btn-outline-primary btn-lg text-white formbtn mainBtn' type = 'submit'>Login</button>
                    </div>

                </form>

            </div>

            <div class = 'container'>
                <form class = 'register text-white formTab' id = 'registerForm' action = '/Register.php' method = 'POST'>

                    <div class = 'form-group'>
                        <label for = 'username'>Username: </label>
                        <input id = 'username' class = 'form-control' autocomplete = 'off' type = 'text' id = 'username' name = 'username'>

                        <div id = 'usernamePattern' class = 'form-control-feedback text-danger font-weight-bold'>
                            Only 2 - 15 characters.
                        </div>

                        <div id = 'usernameExists' class = 'form-control-feedback text-danger font-weight-bold'>
                            The username is already taken.
                        </div>
                    </div>

                    <div class = 'form-group'>
                        <label for = 'email'>Email: </label>
                        <input id = 'email' autocomplete = 'off' class = 'form-control' type = 'text' name = 'email'>

                        <div id = 'emailPattern' class = 'form-control-feedback text-danger font-weight-bold'>
                            Invalid e-mail.
                        </div>

                        <div id = 'emailExists' class = 'form-control-feedback text-danger font-weight-bold'>
                            The email is already in use.
                        </div>
                    </div>

                    <div class = 'form-group'>
                        <label for = 'password'>Password: </label>
                        <input autocomplete = 'off' class = 'form-control' type = 'password' id = 'password' name = 'password'>

                        <div id = 'passwordPattern' class = 'container form-control-feedback text-danger font-weight-bold'>
                            Your password should contain at least:
                            <div class = 'container'>
                                <div class = 'container' id = 'passwordShouldContain'>
                                </div>
                            </div>
                        </div>
                    </div>
                    

                    <div class = 'form-group'>
                        <label for = 'password'>Confirm password: </label>
                        <input autocomplete = 'off' class = 'form-control' type = 'password' id = 'confirmPassword' name = 'confirmPassword'>

                        <div id = 'passwordMatch' class = 'form-control-feedback text-danger font-weight-bold'>
                            Passwords do not match.
                        </div>
                    </div>

                    <div class = 'container my-4 text-center'>
                        <button id = 'registerButton' disabled class = 'btn btn-outline-primary btn-lg text-white formbtn mainBtn' type = 'submit'>Register</button>
                    </div>

                </form>
            </div>

            <div class = 'container text-white'>
                <form class = 'recovery formTab' id = 'recoverForm' action = '/Recover.php' method = 'POST'>

                    <p id = 'messageRec' class = 'alert text-center alert-danger mt-4 mb-4'></p>
                    
                    <div class = 'form-group'>
                        <label for = 'username_email'>Username / Email: </label>
                        <input autocomplete = 'off' id = 'usernameEmail' class = 'form-control' type = 'text' name = 'data'>
                    </div>

                    <div class = 'container text-white text-center my-4'>
                        <button id = 'recoveryButton' class = 'btn btn-outline-primary btn-md text-white formbtn mainBtn' type = 'submit'>Reset password</button>
                    </div>
                </form>
            </div>

            <div class = 'container d-flex justify-content-between my-4'>
                    <button id = 'forgotPassword' class = 'btn btn-outline-primary btn-sm w-25 text-white formbtn'>Forgot password</button>
                    <button id = 'loginSwitchButton' class = 'btn btn-outline-primary btn-sm text-white formbtn'>Sign-up</button>
            </div>

        </div>

    </body>
</html>