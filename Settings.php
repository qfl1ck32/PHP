<?php
    session_start();
    if (!isset($_SESSION['isLogged']) || !$_SESSION['isLogged'])
        die(header('location: /404.php'));

    $sessId = sendQuery('select sessionId from users where id = unhex(?);', $_SESSION['id']);

    if (session_id() != $sessId) {
        session_destroy();
        die(header('location: /404.php'));
    }

    include './API/functions.php';
    include './API/mysql.php';

    $pendingData = sendQuery('select * from pendingPersonalData where id = unhex(?);', $_SESSION['id']);
    $actualData = sendQuery('select * from personalData where id = unhex(?);', $_SESSION['id']);

    if (isset($pendingData[0])) {
        $data = $pendingData[0];
        $pending = true;
    }
    else {
        if (isset($actualData[0])) {
            $data = $actualData[0];
            $pending = false;
        }
    }
?>

<!DOCTYPE html>
<html lang = "ro">
    <head>
        <meta charset = "UTF-8">
        <meta name = "viewport" content = "width = device-width, initial-scale = 1.0">

        <link rel = "stylesheet" type = "text/css" href = "CSS/Settings.css">


        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        
        <link rel = 'stylesheet' type = 'text/css' href = 'https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css' integrity = 'sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2' crossorigin = 'anonymous'>

        <link rel = "icon" href = "Images/Icon.png">

        <script src = "JS/Settings.js" defer></script>
        <script src = "JS/Buttons.js" defer></script>

        <script src='https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js' type='text/javascript'></script>

        <script src = '//code.jquery.com/jquery-3.5.1.js'></script>
        <script src = '//code.jquery.com/ui/1.12.1/jquery-ui.js'></script>  

        <script src = 'https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js' integrity = 'sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx' crossorigin = 'anonymous'></script>

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js" type="text/javascript"></script>

        <title>Settings</title>
    </head>

    <body class = 'bg-img'>


    <nav id = "navigation_bar" class = "navbar navbar-expand-md navbar-dark bg-blue">
            <a href = '/Index.php' class = 'navbar-brand d-md-none font-weight-bold font-italic border border-light p-1 rounded-pill' href = '#'>myBank</a>
            <button class = 'navbar-toggler ml-auto' type = 'button' data-toggle = 'collapse' data-target = '#navbarSupportedContent' aria-controls = 'navbarSupportedContent' aria-expanded = 'false' aria-label = 'Toggle navigation'>
                    <span class = 'navbar-toggler-icon'></span>
            </button>
            
            <div class = 'collapse navbar-collapse mx-auto w-auto justify-content-center' id = 'navbarSupportedContent'>
                <div class = 'navbar-nav mx-auto'>  
                    <ul class = 'navbar-nav mr-auto'>

                        <li class = 'nav-item'>
                            <a id = "home" class = "nav-link" href = '/Index.php'>Home</a>
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
                                            <a id = 'settings' href = '#' class = 'nav-link active'>Settings</a>
                                        </li>";
                        ?>

                        <li class = 'nav-item'>
                            <a id = "about" class = "nav-link" href = '#'>About</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class = 'container-fluid text-right my-4'>
            <?php
                if (isset($_SESSION['admin']) && $_SESSION['admin'])
                    echo '<button id = "admin" class = "btn btn-outline-primary btn-sm border rounded-pill text-white mr-2">Administrate accounts</button>';

                echo "<button id = 'logout' class = 'btn btn-outline-primary btn-sm border rounded-pill text-white'>Logout</button>";

            ?>
        </div>

    
        <div class = "container col-10 col-sm-6 col-xl-3 col-lg-5 border border-white rounded text-white mb-4">

            <div id = "personalInfoData" class = "container">

                <?php if (isset($pending) && $pending) { ?>
                    <div class = 'container text-white text-center mt-4'>
                        <p id = 'pendingApproval' class = 'alert alert-info'>Your last change is pending approval.</p>
                    </div>

                <?php } ?>

                <div class = "container text-white text-center mt-4" id = 'messageWrap'>
                    <p id = "message" class = "alert alert-danger"></p>
                </div>

                <div class = "form-group">
                        <label for = "firstName">First name: </label>
                        <input <?php trySet('firstName'); ?> autocomplete = "off" id = 'firstName' class = "form-control" type = "text" name = "firstName">
                </div>
                    
                <div class = "form-group">
                        <label for = "lasttName">Last name: </label>
                        <input <?php trySet('lastName'); ?> autocomplete = "off" id = 'lastName' class = "form-control" type = "text" name = "lastName">
                </div>

                <div class = "form-group">
                        <label for = "dateOfBirth">Date of birth: </label>
                        <input <?php trySet('dateOfBirth'); ?> autocomplete = "off" id = 'dateOfBirth' class = "form-control" type = "date" name = "dateOfBirth">
                </div>

                <div class = "form-group">
                        <label for = "gender">Gender: </label>
                        <select class = "form-control" name = "gender" id = "gender">
                            <?php if (!isset($data['gender'])) echo '<option value = "" selected disabled hidden>Choose here</option>'; ?>
                            <option <?php trySetGender('M'); ?> value = "M">M</option>
                            <option <?php trySetGender('F'); ?> value = "F">F</option>
                        </select>
                </div>

                <div class = "form-group">
                        <label for = "address">Address: </label>
                        <input <?php trySet('address'); ?> autocomplete = "off" id = 'address' class = "form-control" type = "text" name = "address">
                </div>

                <div class = "form-group">
                        <label for = "country">Country: </label>
                        <input <?php trySet('country'); ?> autocomplete = "off" id = 'country' class = "form-control" type = "text" name = "country">
                        <div id = "countries" class = "container countries bg-info mt-2"></div>
                        <div id = "badCountries" class = "container alert alert-danger mt-4">
                            You have selected a non-existing country.
                        </div>
                </div>

                <div class = "form-group">
                        <label for = "state">State: </label>
                        <input <?php trySet('state'); ?> autocomplete = "off" id = 'state' class = "form-control" type = "text" name = "state">
                        <div id = "states" class = "container states bg-info mt-2"></div>
                        <div id = "badStates" class = "container alert alert-danger mt-4">
                            You have selected a state which does not exist or is not in the selected country.
                        </div>
                </div>

                <div class = "form-group">
                        <label for = "city">City: </label>
                        <input <?php trySet('city'); ?> autocomplete = "off" id = 'city' class = "form-control" type = "text" name = "city">
                        <div id = "cities" class = "container cities bg-info mt-2"></div>
                        <div id = "badCities" class = "container alert alert-danger mt-4">
                            You have selected a city which does not exist or is not in the selected country.
                        </div>
                </div>

                <div class = "form-group">
                    <div class = "container text-center" id = "wrapImage">
                        <img <?php trySet('image'); ?> class = 'img-thumbnail' id = 'image' name = 'image'></img>
                    </div>
                    <div class = 'container text-center'>
                        <input class = 'form-control-file mt-4' type = "file" accept = 'image/png,image/jpeg' id = "file" name = "file">
                        <small class = 'form-text text-left'>Max. 5MB</small>
                    </div>
                </div>

                <div id = "emptyFields" class = "alert alert-danger">
                    There are still empty fields.
                </div>

                <div class = "container text-center mb-4">
                    <button id = 'update' class = "btn btn-outline-primary btn-md text-white">Update</button>
                </div>

            </div>
        </div>
    </body>
</html>