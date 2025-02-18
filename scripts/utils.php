<?php
//  FN: _______________________________________________________________________
// quickly ban access to script if accessed in any way that is not a client POST
// request but crucially still allowing the script to work when included

function ban_script_access()
{
  $markup_message = "<h3>Access to this resource is not allowed.</h2><br><br>";
  $markup_button =
    "<button type='button' onclick=\"window.location.href='../index.php';\">Return to Homepage</button>";
  if (!defined("ALLOW_REQUIRED_SCRIPTS")) {
    $_SERVER["REQUEST_METHOD"] == "POST"
      ? null
      : exit($markup_message . $markup_button);
  }
}

// --------------------------------------------------------
// functions storing internal app abstractions

//  FN: _______________________________________________________________________
// make sure user ID processed is within range of stored users

function validate_id(int $user_id)
{
  require_once "db.php";
  $connection = get_mysqli();
  $query = "select max(user_id) as max_id from users";
  $statement = mysqli_prepare($connection, $query);
  if (!$statement) {
    mysqli_close($connection);
    return false;
  }
  mysqli_stmt_execute($statement);
  $result = mysqli_stmt_get_result($statement);
  if (!$result) {
    db_tidy_up($statement, $connection);
    return false;
  }
  $max_id = mysqli_fetch_assoc($result)["max_id"];
  db_tidy_up($statement, $connection);
  return $user_id >= 0 && $user_id <= $max_id;
}

//  FN: _______________________________________________________________________
// encapsulate closing of DB connection and statement into one line call

function db_tidy_up($statement, $connection)
{
  mysqli_stmt_close($statement);
  mysqli_close($connection);
}

//  FN: _______________________________________________________________________
// provide flexible redirects with option to pass param to use in ui error handling
// and optional error messages to use in error reporting down the line

function redirect_with_query(
  string $url,
  array $query,
  ?array $error_msg = null
) {
  if (isset($error_msg) && count($error_msg) > 0 && $error_msg != null) {
    $query = array_merge($query, $error_msg);
  }
  $query_string = http_build_query($query);
  header("Location: $url?" . $query_string);
  exit();
}
