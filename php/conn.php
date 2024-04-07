<?php

$db_host = 'localhost';
$db_user = 'sysAdmin';
$db_pass = 'xOUFd6yjUmZyDCiD';
$db_database = 'crud';

$db = new mysqli($db_host, $db_user, $db_pass, $db_database);
mysqli_query($db, "SET NAMES 'utf8'");

if($db->connect_errno > 0) {
    die('Impossible to stablish connection to the database. ['. $db->connect_error . ']');
}