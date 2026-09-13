<?php
/* =========================
   LOGOUT PROCESS
========================= */

/*
   Load configuration file.
   This keeps structure consistent across pages,
   even if this file does not directly use the database.
*/
require_once __DIR__ . '/../config.php';

/*
   Start the session.
   Required before we can clear or destroy session data.
*/
session_start();

/*
   Remove all session variables.
   This clears stored login information and other session data.
*/
session_unset();

/*
   Destroy the session completely.
   This ends the user's session on the server.
*/
session_destroy();

/*
   Redirect user back to login page
   after logout is complete.
*/
header('location:login.php');

?>
