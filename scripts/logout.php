<?php
define("ALLOW_REQUIRED_SCRIPTS", true);
session_start();
require_once "utils.php";
ban_script_access();
if (isset($_SESSION["user_id"])) {
  unset($_SESSION["user_id"]);
  session_destroy();
  redirect_with_query("../index.php", ["status" => "loggedout"]);
}
