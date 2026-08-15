<?php

$host="localhost";
$user="mybh164133_wingo";
$pass="8984@Raghu";
$dbname="mybh164133_wingo";

$conn = new mysqli(
    $host,
    $user,
    $pass,
    $dbname
);

if($conn->connect_error){
    die("Database Connection Failed");
}

?>