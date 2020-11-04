<!DOCTYPE html>

<html lang = "ro">
    <head>
        <meta charset = "UTF-8">
        <meta name = "viewport" content = "width = device-width, initial-scale = 1.0">
        
        <title>Verification</title>
    </head>

    <link rel = 'stylesheet' type = 'text/css' href = 'https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css' integrity = 'sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2' crossorigin = 'anonymous'>

    <style>

      html, body {
        width: 100% !important;
        height: 100% !important;
        overflow-x: hidden;
        overflow-y: hidden;
        margin: 0em;
        padding: 0em;
        background-image: url('https://i.ibb.co/kSqTHxZ/Background.png');
        background-attachment: fixed;
        background-position: center;
        background-size: cover;
        background-repeat: no-repeat;
        overflow-x: hidden;
      }
  
    </style>
  
    <body class = 'bg'>
  
    <div class = 'container row h-100 w-50 mx-auto'>

      <div class = 'col text-center jumbotron bg-primary my-auto'>

        <div class = 'container text-white'>
          <h3>Hi there, <?php echo $_POST['username'];?>!</h3>
        </div>

        <hr>

        <div class = 'container text-white'>
          <h5 class = 'font-italic'>We are happy to inform you that we've received your account registration.</h5>
        </div>

        <div class = 'container text-white'>
          <h5 class = 'font-italic'>Below is the link that you should click in order to confirm your account.</h5>
        </div>

        <div class = 'container text-white'>
          <h5 class = 'font-italic'>Have lots of fun!</h5>
        </div>

        <hr>

        <div class = 'container'>
          <h5 class = 'font-italic'><a class = 'text-white' href = <?php echo $_POST['url']; ?>>Confirmation link</a></h5>
        </div>
      </div>

    </div>

    </body>

</html>