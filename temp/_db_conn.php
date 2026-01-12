<?php

function connectToDatabase() {
    $serverName = "ocm-oiles\\sqlexpress";
    $connectionOptions = array(
        "Database" => "Selio",
        "Uid" => "Selio",
        "PWD" => "S3l10p4ssw0rd",
		"CharacterSet" => "UTF-8"
    );

    $conn = sqlsrv_connect($serverName, $connectionOptions);

    if ($conn === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    return $conn;
}

?>