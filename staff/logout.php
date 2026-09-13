<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

@include 'config.php';

session_start();
session_unset();
session_destroy();

header('location:login.php');

?>