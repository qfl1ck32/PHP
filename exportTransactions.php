<?php

    require 'vendor/autoload.php';
    include 'API/mysql.php';
    include 'API/functions.php';

    session_start();
    
    if (!isset($_SESSION['isLogged']) || !$_SESSION['isLogged'])
        die(header('location: /404.php'));

    $sessId = sendQuery('select sessionId as sid from users where id = unhex(?);', $_SESSION['id'])[0]['sid'];

    if (session_id() != $sessId) {
        session_destroy();
        die(header('location: /404.php'));
    }

    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

    if (isset($_POST['IBAN'])) {

        $IBAN = $_POST['IBAN'];
        $fromDate = $_POST['fromDate'];
        $toDate = $_POST['toDate'];

        $data = sendQuery('select ID from creditcards where id = unhex(?) and iban = ?', $_SESSION['id'], $IBAN);

        if (!isset($data[0]))
            return Status(false, "The given IBAN either does not exist or it does not belong to any of your credit cards.");

        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet -> getActiveSheet();

        if ($_POST['fromDate'] != '' && $toDate != '')
            $tableData = sendQuery('select type, date, description, amount, balance from transactions where IBAN = ? and date >= ? and date <= ?;', $IBAN, $fromDate, date('Y-m-d', strtotime($toDate . ' +1 day')));
        else
            $tableData = sendQuery('select type, date, description, amount, balance from transactions where IBAN = ?;', $IBAN);

        if (!isset($tableData[0])) {
            return Status(false, 'There are no transactions for the given date.');
        }

        $sheet -> setCellValue('A1', 'Hello, World!');


        $column = 'A';

        foreach ($tableData[0] as $key => $value) {
            $sheet -> setCellValue($column . '1', $key);
            ++$column;

            $sheet -> getColumnDimension($column) -> setAutoSize(true);
        }

        for ($i = 1; $i <= sizeof($tableData); ++$i) {
            $column = 'A';

            foreach ($tableData[$i - 1] as $key => $value) {
                $sheet -> setCellValue($column . ($i + 1), $value);

                ++$column;
            }
        }

        $writer = new Xlsx($spreadsheet);

        $excelFile = tempnam(sys_get_temp_dir(), 'transactionsXls');

        $writer->save($excelFile);

        $attachmentName = $IBAN . '.xls';

        $data = array(
            'email' => $_SESSION['email'],
            'subject' => 'Transactions for ' . $IBAN . ($fromDate == '' && $toDate == '' ? '' : ' | ' . $fromDate . ' - ' . $toDate),
            'html' => 'A file containing the requested transactions has been attached.
                        Have a beautiful day!',
            'attachment' => file_get_contents($excelFile),
            'attachmentName' => $attachmentName
        );

        sendVerificationEmail($data);

        unlink($excelFile);

        die(Status(true, 'Successfully exported.'));
    }
?>