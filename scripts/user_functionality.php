<?php
require_once "utils.php";
require_once "db.php";
ban_script_access();

class User
{
  public int $user_id;
  public string $user_email;
  public DateTime $user_registration;
  public string $user_firstname;
  public string $user_lastname;
  public string $user_img;
  public string $user_type;
  public string $user_auth_method;
}
class UserPassword
{
  public string $user_id;
  private ?string $user_password;

  public function __get_password()
  {
    return $this->user_password;
  }
  public function __set_password(?string $new_password)
  {
    $this->user_password = $new_password;
  }
}
// handle client requests
$_POST = json_decode(file_get_contents("php://input"), true);
//  FN: _______________________________________________________________________
// return curretly logged in user's ID if client needs it
if (isset($_POST["client_request"]) && $_POST["client_request"] == "get_id") {
  session_start();
  if (isset($_SESSION["user_id"])) {
    echo $_SESSION["user_id"];
  } else {
    echo "no_id";
  }
}

//  FN: _______________________________________________________________________
// return curretly logged in user's role

if (isset($_POST["client_request"]) && $_POST["client_request"] == "get_role") {
  session_start();
  if (isset($_SESSION["user_type"])) {
    echo $_SESSION["user_type"];
  } else {
    echo "no_role";
  }
}

//  FN: _______________________________________________________________________
// function getting users as array

function get_users_array()
{
  $connection = get_mysqli();
  $query = "SELECT * FROM users";
  $statement = mysqli_prepare($connection, $query);
  if (!$statement) {
    mysqli_close($connection);
    $err_msg_params = ["error_msg" => "err_retrieving_user_list_01"];
    redirect_with_query(
      "../index.php",
      ["error" => "internalerr"],
      $err_msg_params
    );
  }
  $result = mysqli_stmt_execute($statement);
  if (!$result) {
    db_tidy_up($statement, $connection);
    $err_msg_params = ["error_msg" => "err_retrieving_user_list_02"];
    redirect_with_query(
      "../index.php",
      ["error" => "internalerr"],
      $err_msg_params
    );
  }
  $result = mysqli_stmt_get_result($statement);
  db_tidy_up($statement, $connection);
  $users = [];
  while ($row = mysqli_fetch_assoc($result)) {
    $user = new User();
    $user->user_id = $row["user_id"];
    $user->user_email = $row["user_email"];
    $user->user_registration = new DateTime($row["user_registration"]);
    $user->user_firstname = $row["user_firstname"];
    $user->user_lastname = $row["user_lastname"];
    //with null check
    $user->user_img = $row["user_img"] ?: "default.jpg";
    $user->user_type = $row["user_type"];
    $user->user_auth_method = $row["user_auth_method"];
    array_push($users, $user);
  }
  return $users;
}

//  FN: _______________________________________________________________________
// function getting user by passed ID

function get_user(int $user_id)
{
  // taking advantage of strong type implication in parameter but still wrote
  // extra validation util function because why not?
  if (validate_id($user_id) == false) {
    redirect_with_query("../index.php", [
      "error" => "fatalinvalidid",
      "error_msg" => "err_user_get_invalid_id",
    ]);
  }

  $connection = get_mysqli();
  $query = "SELECT * FROM users WHERE user_id = ?";
  $statement = mysqli_prepare($connection, $query);
  if (!$statement) {
    mysqli_close($connection);
    $err_msg_params = ["error_msg" => "err_retrieving_user_01"];
    redirect_with_query(
      "../index.php",
      ["error" => "internalerr"],
      $err_msg_params
    );
  }
  mysqli_stmt_bind_param($statement, "i", $user_id);
  $result = mysqli_stmt_execute($statement);
  if (!$result) {
    db_tidy_up($statement, $connection);
    $err_msg_params = ["error_msg" => "err_retrieving_user_02"];
    redirect_with_query(
      "../index.php",
      ["error" => "internalerr"],
      $err_msg_params
    );
  }
  $result = mysqli_stmt_get_result($statement);
  if (!$result) {
    db_tidy_up($statement, $connection);
    $err_msg_params = ["error_msg" => "err_retrieving_user_03"];
    redirect_with_query(
      "../index.php",
      ["error" => "internalerr"],
      $err_msg_params
    );
  }
  $user_data = mysqli_fetch_assoc($result);
  if (!$user_data) {
    db_tidy_up($statement, $connection);
    $err_msg_params = ["error_msg" => "err_retrieving_user_04"];
    redirect_with_query(
      "../index.php",
      ["error" => "internalerr"],
      $err_msg_params
    );
  }
  db_tidy_up($statement, $connection);

  $fetched_user = new User();
  $fetched_user->user_id = $user_id;
  $fetched_user->user_email = $user_data["user_email"];
  $fetched_user->user_registration = $user_data["user_registration"]
    ? new DateTime($user_data["user_registration"])
    : null;
  $fetched_user->user_firstname = $user_data["user_firstname"];
  $fetched_user->user_lastname = $user_data["user_lastname"];
  $fetched_user->user_img = $user_data["user_img"] ?: "default.jpg";
  $fetched_user->user_type = $user_data["user_type"];
  $fetched_user->user_auth_method = $user_data["user_auth_method"];

  return $fetched_user;
}

//_____________________________________________________________________________
//writing:
//  FN: _______________________________________________________________________
// function adding a user to a db

function add_user(User $user, ?string $user_password)
{
  $connection = get_mysqli();
  $pass_insert = null;
  $user_password == ""
    ? ($pass_insert = null)
    : ($pass_insert = md5($user_password));
  $formatted_date = $user->user_registration->format("Y-m-d");
  $query =
    "INSERT INTO users (user_email, user_registration, user_firstname, user_lastname, user_password, user_img, user_type, user_auth_method ) VALUES (?,?,?,?,?,?,?,?)";
  $statement = mysqli_prepare($connection, $query);
  if (!$statement) {
    mysqli_close($connection);
    $err_msg_params = ["error_msg" => "err_adding_user_01"];
    redirect_with_query(
      "../index.php",
      ["error" => "internalerr"],
      $err_msg_params
    );
  }
  mysqli_stmt_bind_param(
    $statement,
    "ssssssss",
    $user->user_email,
    $formatted_date,
    $user->user_firstname,
    $user->user_lastname,
    $pass_insert,
    $user->user_img,
    $user->user_type,
    $user->user_auth_method
  );
  try {
    mysqli_stmt_execute($statement);
  } catch (mysqli_sql_exception $e) {
    // Handle the exception here
    $error_message = $e->getMessage();
    if (strpos($error_message, "limit_reached") !== false) {
      // Handle the limit reached error
      $err_msg_params = ["err_msg" => "user_limit_reached"];
      redirect_with_query(
        "../index.php",
        ["error" => "users_constraint"],
        $err_msg_params
      );
    } else {
      // Handle other exceptions
      db_tidy_up($statement, $connection);
      $err_msg_params = ["error_msg" => "err_adding_user_02"];
      redirect_with_query(
        "../index.php",
        ["error" => "internalerr"],
        $err_msg_params
      );
    }
  }
  db_tidy_up($statement, $connection);
}

//  FN: _______________________________________________________________________
// function storing user avatar image

function store_user_img($user_img_url)
{
  $random_int = rand(1000000000, 9999999999);
  //using pretty large random number to avoid collision but better check if file exists
  //and generate a new rand if it does
  while (file_exists("../res/user_img/" . $random_int . ".jpg")) {
    $random_int = rand(1000000000, 9999999999);
  }

  $user_img_new_path = "../res/user_img/" . $random_int . ".jpg";
  $img_data = file_get_contents($user_img_url);
  //if script received no uploaded temp path return false to handle
  if (!$img_data) {
    return false;
  }
  // prep the image data to store
  $img_data = base64_encode($img_data);
  $img_data = str_replace("data:image/jpeg;base64,", "", $img_data);
  $img_data = str_replace(" ", "+", $img_data);
  try {
    // store the data using write permissions
    $new_file = fopen($user_img_new_path, "w+");
    fwrite($new_file, base64_decode($img_data));
    fclose($new_file);
  } catch (Exception) {
    // also return false if error on write operation
    return false;
  }
  // if all good return ther new random generated new filename
  return $random_int . ".jpg";
}

//_____________________________________________________________________________
//updating:

//  FN: _______________________________________________________________________
// function to update user password using obj with private prop, a setter
// and optionally allowing to write hashed or not hashed string to a db

function update_user_password(
  UserPassword $pass_obj,
  bool $to_hash_or_not_to_hash_hehe
) {
  $new_password = $to_hash_or_not_to_hash_hehe
    ? md5($pass_obj->__get_password())
    : $pass_obj->__get_password();
  $upd_result = true;
  $connection = get_mysqli();
  $query = "UPDATE users SET user_password = ? WHERE user_id = ?";
  $statement = mysqli_prepare($connection, $query);
  $upd_result = $statement ? $upd_result : false;
  mysqli_stmt_bind_param($statement, "si", $new_password, $pass_obj->user_id);
  $result = mysqli_stmt_execute($statement);
  db_tidy_up($statement, $connection);
  $upd_result = $result ? $upd_result : false;
  return $upd_result;
}

//  FN: _______________________________________________________________________
// function updating all user data other than password

function update_user(User $new_data_user)
{
  $connection = get_mysqli();
  $updated_user = get_user($new_data_user->user_id);
  $udp_result = true;
  $specific_results = [
    "email_r" => true,
    "firstname_r" => true,
    "lastname_r" => true,
    "type_r" => true,
    "img_r" => true,
  ];
  if (
    $updated_user->user_email != $new_data_user->user_email &&
    $new_data_user->user_email != "" &&
    $updated_user->user_email != null
  ) {
    $query = "UPDATE users SET user_email = ? WHERE user_id = ?";
    $statement = mysqli_prepare($connection, $query);
    $specific_results["email_r"] = $statement
      ? $specific_results["email_r"]
      : false;
    mysqli_stmt_bind_param(
      $statement,
      "si",
      $new_data_user->user_email,
      $new_data_user->user_id
    );
    $result = mysqli_stmt_execute($statement);
    $specific_results["email_r"] = $result
      ? $specific_results["email_r"]
      : false;
  }

  if (
    $updated_user->user_firstname != $new_data_user->user_firstname &&
    $new_data_user->user_firstname != "" &&
    $updated_user->user_firstname != null
  ) {
    $query = "UPDATE users SET user_firstname = ? WHERE user_id = ?";
    $statement = mysqli_prepare($connection, $query);
    $specific_results["firstname_r"] = $statement
      ? $specific_results["firstname_r"]
      : false;
    mysqli_stmt_bind_param(
      $statement,
      "si",
      $new_data_user->user_firstname,
      $new_data_user->user_id
    );
    $result = mysqli_stmt_execute($statement);
    $specific_results["firstname_r"] = $result
      ? $specific_results["firstname_r"]
      : false;
  }

  if (
    $updated_user->user_lastname != $new_data_user->user_lastname &&
    $new_data_user->user_lastname != "" &&
    $updated_user->user_lastname != null
  ) {
    $query = "UPDATE users SET user_lastname = ? WHERE user_id = ?";
    $statement = mysqli_prepare($connection, $query);
    $specific_results["lastname_r"] = $statement
      ? $specific_results["lastname_r"]
      : false;
    mysqli_stmt_bind_param(
      $statement,
      "si",
      $new_data_user->user_lastname,
      $new_data_user->user_id
    );
    $result = mysqli_stmt_execute($statement);
    $specific_results["lastname_r"] = $result
      ? $specific_results["lastname_r"]
      : false;
  }

  if (
    $updated_user->user_type != $new_data_user->user_type &&
    $new_data_user->user_type != "" &&
    $updated_user->user_type != null
  ) {
    $page_role_to_enum_format = strtolower($new_data_user->user_type);
    $query = "UPDATE users SET user_type = ? WHERE user_id = ?";
    $statement = mysqli_prepare($connection, $query);
    $specific_results["type_r"] = $statement
      ? $specific_results["type_r"]
      : false;
    mysqli_stmt_bind_param(
      $statement,
      "si",
      $page_role_to_enum_format,
      $new_data_user->user_id
    );
    $result = mysqli_stmt_execute($statement);
    $specific_results["type_r"] = $result ? $specific_results["type_r"] : false;
  }

  if (
    $updated_user->user_img != $new_data_user->user_img &&
    $new_data_user->user_img != "" &&
    $updated_user->user_img != null
  ) {
    $query = "UPDATE users SET user_img = ? WHERE user_id = ?";
    $statement = mysqli_prepare($connection, $query);
    $specific_results["img_r"] = $statement
      ? $specific_results["img_r"]
      : false;
    mysqli_stmt_bind_param(
      $statement,
      "si",
      $new_data_user->user_img,
      $new_data_user->user_id
    );
    $result = mysqli_stmt_execute($statement);
    $specific_results["img_r"] = $result ? $specific_results["img_r"] : false;
  }
  // check result bools then set the master outcome bool
  foreach ($specific_results as $value) {
    if ($value == false) {
      $udp_result = false;
      break;
    }
  }
  // If anything failed undo everythign by writing OG data to the database
  // to avoid partial updates
  if ($udp_result == false) {
    $query =
      "UPDATE users SET user_email = ?, user_firstname = ?, user_lastname = ?, user_type = ?, user_img = ? WHERE user_id = ?";
    $statement = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param(
      $statement,
      "sssssi",
      $updated_user->user_email,
      $updated_user->user_firstname,
      $updated_user->user_lastname,
      $updated_user->user_type,
      $updated_user->user_img,
      $updated_user->user_id
    );
    $result = mysqli_stmt_execute($statement);
    if (!$result) {
      db_tidy_up($statement, $connection);
      $err_msg_params = ["error_msg" => "err_user_upd_partial_upd_possible"];
      redirect_with_query(
        "../index.php",
        ["error" => "internalerr"],
        $err_msg_params
      );
    }
  }
  $statement ? mysqli_stmt_close($statement) : null;
  $connection ? mysqli_close($connection) : null;
  return $udp_result;
}

//_____________________________________________________________________________
//deleting:

//  FN: _______________________________________________________________________
// function deleting user froma db by ID

function delete_user(int $user_id, string $user_img)
{
  if (validate_id($user_id) == false) {
    redirect_with_query("../index.php", [
      "error" => "fatalinvalidid",
      "error_msg" => "err_user_del_invalid_id",
    ]);
  }

  $connection = get_mysqli();
  $query = "DELETE FROM users WHERE user_id = ?";
  $statement = mysqli_prepare($connection, $query);
  if (!$statement) {
    mysqli_close($connection);
    $err_msg_params = ["error_msg" => "err_user_del_01"];
    redirect_with_query(
      "../index.php",
      ["error" => "internalerr"],
      $err_msg_params
    );
  }
  mysqli_stmt_bind_param($statement, "i", $user_id);
  $result = mysqli_stmt_execute($statement);
  db_tidy_up($statement, $connection);
  if (!$result) {
    $err_msg_params = ["error_msg" => "err_user_del_02"];
    redirect_with_query(
      "../index.php",
      ["error" => "internalerr"],
      $err_msg_params
    );
  }
  if ($user_img != "default.jpg") {
    clearDeletedUserImage($user_img);
  }
}

function clearDeletedUserImage(string $user_img)
{
  $path = "../res/user_img/" . $user_img;
  try {
    unlink($path);
  } catch (Exception) {
    // if failed to delete old file log it and continue
    error_log(
      "Error deleting old image file.\nConsider manually deleting redundant file: " .
        $path,
      3
    );
  }
}
//_____________________________________________________________________________
//retrieval:

//  FN: _______________________________________________________________________
// function retrieving private prop password from userpassword object using
// a getter by a passed in ID

function get_user_password(int $user_id)
{
  if (validate_id($user_id) == false) {
    redirect_with_query("../index.php", [
      "error" => "fatalinvalidid",
      "error_msg" => "err_user_get_pass_invalid_id",
    ]);
  }

  $connection = get_mysqli();
  $query = "SELECT user_password FROM users WHERE user_id = '$user_id'";
  $statement = mysqli_prepare($connection, $query);
  if (!$statement) {
    $err_msg_params = ["error_msg" => "err_user_pass_get_01"];
    redirect_with_query(
      "../index.php",
      ["error" => "internalerr"],
      $err_msg_params
    );
  }
  $result = mysqli_stmt_execute($statement);
  if (!$result) {
    $err_msg_params = ["error_msg" => "err_user_pass_get_02"];
    redirect_with_query(
      "../index.php",
      ["error" => "internalerr"],
      $err_msg_params
    );
  }
  $result = mysqli_stmt_get_result($statement);
  db_tidy_up($statement, $connection);

  if (!$result) {
    $err_msg_params = ["error_msg" => "err_user_pass_get_03"];
    redirect_with_query(
      "../index.php",
      ["error" => "internalerr"],
      $err_msg_params
    );
  }
  $fetched_pass_obj = new UserPassword();
  $fetched_pass_obj->user_id = $user_id;
  $fetched_pass_obj->__set_password(
    mysqli_fetch_assoc($result)["user_password"]
  );
  return $fetched_pass_obj;
}

//  FN: _______________________________________________________________________
// function retrieving user ID by passed in email addr string

function get_id_by_existing_email(string $email)
{
  $connection = get_mysqli();
  $query = "SELECT user_id FROM users WHERE user_email = ?";
  $statement = mysqli_prepare($connection, $query);
  if (!$statement) {
    $err_msg_params = ["error_msg" => "err_user_get_id_by_email_01"];
    redirect_with_query(
      "../index.php",
      ["error" => "internalerr"],
      $err_msg_params
    );
  }
  mysqli_stmt_bind_param($statement, "s", $email);
  $result = mysqli_stmt_execute($statement);
  if (!$result) {
    $err_msg_params = ["error_msg" => "err_user_get_id_by_email_02"];
    redirect_with_query(
      "../index.php",
      ["error" => "internalerr"],
      $err_msg_params
    );
  }
  $result = mysqli_stmt_get_result($statement);
  db_tidy_up($statement, $connection);
  if ($result) {
    return mysqli_fetch_assoc($result)["user_id"] ?? false;
  }
  return false;
}

//  FN: _______________________________________________________________________
// function getting the user's type/role by ID

function get_user_type_by_id(int $user_id)
{
  if (validate_id($user_id) == false) {
    redirect_with_query("../index.php", [
      "error" => "fatalinvalidid",
      "error_msg" => "err_user_get_type_invalid_id",
    ]);
  }

  $connection = get_mysqli();
  $query = "SELECT user_type FROM users WHERE user_id = ?";
  $statement = mysqli_prepare($connection, $query);
  if (!$statement) {
    $err_msg_params = ["error_msg" => "err_user_get_type_by_id_01"];
    redirect_with_query(
      "../index.php",
      ["error" => "internalerr"],
      $err_msg_params
    );
  }
  mysqli_stmt_bind_param($statement, "i", $user_id);
  $result = mysqli_stmt_execute($statement);
  if (!$result) {
    $err_msg_params = ["error_msg" => "err_user_get_type_by_id_02"];
    redirect_with_query(
      "../index.php",
      ["error" => "internalerr"],
      $err_msg_params
    );
  }
  $result = mysqli_stmt_get_result($statement);
  db_tidy_up($statement, $connection);
  if ($result) {
    return mysqli_fetch_assoc($result)["user_type"] ?? false;
  }
  return false;
}

//  FN: _______________________________________________________________________
// function getting the user's avatar img by ID

function get_user_img_by_id(int $user_id)
{
  if (validate_id($user_id) == false) {
    redirect_with_query("../index.php", [
      "error" => "fatalinvalidid",
      "error_msg" => "err_user_get_type_invalid_id",
    ]);
  }

  $connection = get_mysqli();
  $query = "SELECT user_img FROM users WHERE user_id = ?";
  $statement = mysqli_prepare($connection, $query);
  if (!$statement) {
    $err_msg_params = ["error_msg" => "err_user_get_img_by_id_01"];
    redirect_with_query(
      "../index.php",
      ["error" => "internalerr"],
      $err_msg_params
    );
  }
  mysqli_stmt_bind_param($statement, "i", $user_id);
  $result = mysqli_stmt_execute($statement);
  if (!$result) {
    $err_msg_params = ["error_msg" => "err_user_get_img_by_id_02"];
    redirect_with_query(
      "../index.php",
      ["error" => "internalerr"],
      $err_msg_params
    );
  }
  $result = mysqli_stmt_get_result($statement);
  db_tidy_up($statement, $connection);
  if ($result) {
    return mysqli_fetch_assoc($result)["user_img"] ?? false;
  }
  return false;
}

//  FN: _______________________________________________________________________
// function getting the user's cart state by ID
function get_user_cart_state(int $user_id)
{
  if (validate_id($user_id) == false) {
    redirect_with_query("../index.php", [
      "error" => "fatalinvalidid",
      "error_msg" => "err_user_get_type_invalid_id",
    ]);
  }
  $connection = get_mysqli();
  $query = "SELECT order_contents FROM cart_state_store WHERE user_id = ?";
  $statement = mysqli_prepare($connection, $query);
  if (!$statement) {
    $err_msg_params = ["error_msg" => "err_user_get_cart_state_01"];
    redirect_with_query(
      "../index.php",
      ["error" => "internalerr"],
      $err_msg_params
    );
  }
  mysqli_stmt_bind_param($statement, "i", $user_id);
  $result = mysqli_stmt_execute($statement);
  if (!$result) {
    return "no_cart";
    exit();
  }
  mysqli_stmt_store_result($statement);
  if (mysqli_stmt_num_rows($statement) == 0) {
    return "no_cart";
    exit();
  }
  mysqli_stmt_bind_result($statement, $order_contents);
  mysqli_stmt_fetch($statement);
  db_tidy_up($statement, $connection);
  return $order_contents;
}

//_____________________________________________________________________________
//checks:

//  FN: _______________________________________________________________________
// function checking if email address exists in the db

function check_if_email_exists(string $email)
{
  $connection = get_mysqli();
  $query = "SELECT * FROM users WHERE user_email = ?";
  $statement = mysqli_prepare($connection, $query);
  if (!$statement) {
    $err_msg_params = ["error_msg" => "err_user_check_email_01"];
    redirect_with_query(
      "../index.php",
      ["error" => "internalerr"],
      $err_msg_params
    );
  }
  mysqli_stmt_bind_param($statement, "s", $email);
  $result = mysqli_stmt_execute($statement);
  if (!$result) {
    $err_msg_params = ["error_msg" => "err_user_check_email_02"];
    redirect_with_query(
      "../index.php",
      ["error" => "internalerr"],
      $err_msg_params
    );
  }
  $result = mysqli_stmt_get_result($statement);
  if (mysqli_num_rows($result) > 0) {
    db_tidy_up($statement, $connection);
    return true;
  }
  db_tidy_up($statement, $connection);
  return false;
}
