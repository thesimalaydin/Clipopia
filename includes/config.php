<?php
ob_start();
session_start();

date_default_timezone_set("Europe/Istanbul");

try{
    $con = new PDO("mysql:dbname=clipopia;host=localhost", "root", "");
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
}
catch(PDOException $e){
    echo "Connection failed: ", $e->getMessage();
}
?>