<?php
    include 'mysql.php';
    include 'functions.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(404);
        include('404.php');
        die();
    }

    if (isset($_POST['getIds'])) {
        
        if (!isset($_POST['countryName']) || !isset($_POST['cityName']) || !isset($_POST['stateName']))
            return Status('error', 'Missing parameters.');

        $ans = sendQuery('select id countryId, (select id stateId from states where name = ?) stateId, (select id from cities c where name = ? and (select name from states where id = c.state_id) = ?) cityId from countries where name = ?;', $_POST['stateName'], $_POST['cityName'], $_POST['stateName'], $_POST['countryName']);
        
        return Status(true, $ans[0]);
    }

    if (isset($_POST['checkExists'])) {

        if (!isset($_POST['value']))
            return Status(false, "Missing parameter.");

        switch($_POST['where']) {
            case 'countries':
                $data = sendQuery('select count(*) as c from countries where name = ?;', $_POST['value']);
                break;
            
            case 'states':
                $data = sendQuery('select count(*) as c from states where name = ?;', $_POST['value']);
                break;
            
            case 'cities':
                $data = sendQuery('select count(*) as c from cities where name = ?;', $_POST['value']);
                break;
        }
        
        die(json_encode(array('status' => $data[0]['c'])));
    }

    if (!isset($_POST['where']) || !isset($_POST['substr']))
        return Status(false, "Missing parameter.");

    switch($_POST['where']) {
        case 'countries':
            $data = sendQuery('select * from countries where name like concat(?, "%");', $_POST['substr']);
            break;

        case 'states':
            if (!isset($_POST['countryId']))
                return Status(false, "Missing parameter.");
            $data = sendQuery('select * from states where country_id = ? and name like concat(?, "%");', $_POST['countryId'], $_POST['substr']);
            break;

        case 'cities':
            if (!isset($_POST['stateId']))
                return Status(false, "Missing parameter.");
            $data = sendQuery('select * from cities where state_id = ? and name like concat(?, "%");', $_POST['stateId'], $_POST['substr']);
            break;
    }


    echo(json_encode($data));
?>