<?php
require_once "utils.php";
// ban_script_access();
function get_mysqli()
{
  $pass = getenv("MYSQL_PASS_TOMANO");
  // $user = getenv("DB_USER");
  $connection = mysqli_connect(
    "localhost",
    "tomano",
    "homedb69",
    "phonezone",
    3306
  );
  if (!$connection) {
    die("Error connecting to database");
  }
  return $connection;
}
