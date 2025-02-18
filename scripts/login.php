<?php
define("ALLOW_REQUIRED_SCRIPTS", true);
require_once "db.php";
require_once "utils.php";
ban_script_access();

$connection = get_mysqli();

if (isset($_POST["email"]) && isset($_POST["password"])) {
  $email = mysqli_real_escape_string($connection, $_POST["email"]);
  $password = mysqli_real_escape_string($connection, md5($_POST["password"]));

  $user_query = "SELECT * FROM users WHERE user_email = '$email' AND user_password = '$password'";

  $result = mysqli_query($connection, $user_query);
  if (mysqli_num_rows($result) == 0) {
    header("Location:../index.php?error=nouser");
    exit();
  }
  if (mysqli_num_rows($result) != 1) {
    header("Location: ../index.php?error=internalerr");
    exit();
  }
  //verification above with neat guar clauses
  // only init session after verification, much better approach than showed in class
  session_start();
  $row = mysqli_fetch_assoc($result);
  $_SESSION["user_id"] = $row["user_id"];
  $_SESSION["user_type"] = $row["user_type"];
  header("Location: ../pages/profile.php");
  mysqli_close($connection);
}
?>
