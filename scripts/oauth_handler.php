<?php
define("ALLOW_REQUIRED_SCRIPTS", true);
require_once "utils.php";
require_once "db.php";
ban_script_access();
if (
  isset($_GET["code"]) &&
  !empty($_GET["code"]) &&
  isset($_GET["scope"]) &&
  str_contains($_GET["scope"], "googleapis")
) {
  handle_google($_GET["code"]);
} elseif (
  // HACK:
  // rudimentary idiot check for github because github api does not return
  // anything specific in the "code" response. Just "code" and "state" strings
  // both random and unreliable. So I just make sure the code is there and
  // in general response differs from google's.
  // 1) If more oauth added then more vague conditions might be required...
  // 2) To be determined still if I can make the GH api return
  // strictly GH identifiable json... stupid microsoft :/
  isset($_GET["code"]) &&
  !empty($_GET["code"]) &&
  isset($_GET["state"]) &&
  !isset($_GET["scope"])
) {
  handle_github();
} else {
  redirect_with_query(
    "../index.php",
    ["error" => "autherror"],
    ["err_message" => "no_auth_code_received"]
  );
}

//  FN: _______________________________________________________________________
// handler for google oauth
// 1)get code/token/user-data .then check if registered by email query
// 2)if regged and via  oauth then start session,
// else inform user email already in use for a different login method
// 3)if all conditions checked and failed else register as new user

function handle_google($code)
{
  // reference used:
  // https://codeshack.io/implement-google-login-php/#process-curl-requests
  require_once "user_functionality.php";
  $base64_encoded_json = getenv("GOOGLE_CLIENT_JSON");
  $oauth_json_raw = base64_decode($base64_encoded_json);
  $oauth_obj = json_decode($oauth_json_raw, true);

  $query_params = [
    "code" => $code,
    "client_id" => $oauth_obj["web"]["client_id"],
    "client_secret" => $oauth_obj["web"]["client_secret"],
    "redirect_uri" => $oauth_obj["web"]["redirect_uris"][0],
    "grant_type" => "authorization_code",
  ];

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, "https://accounts.google.com/o/oauth2/token");
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query_params));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $response = curl_exec($ch);
  curl_close($ch);
  $response = json_decode($response, true);

  if (!isset($response["access_token"]) || empty($response["access_token"])) {
    redirect_with_query(
      "index.php",
      ["error" => "autherror"],
      ["err_message" => "google_no_access_token_received"]
    );
  }

  $ch = curl_init();
  curl_setopt(
    $ch,
    CURLOPT_URL,
    "https://www.googleapis.com/oauth2/v2/userinfo"
  );
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $response["access_token"],
  ]);
  $response = curl_exec($ch);
  curl_close($ch);
  $google_profile_data = json_decode($response, true);
  $response = json_encode($google_profile_data);

  //
  //WARN:
  // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  // ASSESSMENT: this is one place where task 4 is handled for google oauth2 case
  // it takes care of several routes. Email taken, but with different auth method,
  // email not taken at all and thus registration of new user, or email taken
  // but already authenticated with google, then log this user in successfully
  // details in comments for each section of code
  // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  //
  $email_to_check = $google_profile_data["email"];
  //if email present in db two options
  if (check_if_email_exists($email_to_check)) {
    // check if registered via google oauth
    $connection = get_mysqli();
    $query = "SELECT user_auth_method FROM users WHERE user_email = ?";
    $statement = $connection->prepare($query);
    if (!$statement) {
      mysqli_close($connection);
      redirect_with_query(
        "../index.php",
        ["error" => "internalerr"],
        ["err_message" => "oauth_google_fail_db_check_01"]
      );
    }
    mysqli_stmt_bind_param($statement, "s", $email_to_check);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);
    // fail if query fails guard clause check
    if (!$result) {
      db_tidy_up($statement, $connection);
      redirect_with_query(
        "../index.php",
        ["error" => "internalerr"],
        ["err_message" => "oauth_google_fail_db_check_02"]
      );
    }
    $user_auth_method = mysqli_fetch_assoc($result)["user_auth_method"];
    db_tidy_up($statement, $connection);
    // if user in db and registered via google oauth proceed
    if ($user_auth_method == 2) {
      session_start();
      $id = get_id_by_existing_email($email_to_check);
      $type = get_user_type_by_id($id);
      $_SESSION["user_id"] = $id;
      $_SESSION["user_type"] = $type;
      header("Location:../pages/profile.php");
    } else {
      // else (different method of registration) back to main page to display info
      redirect_with_query("../index.php", ["error" => "emailtaken"]);
    }
    //the only remaining route is registration
  } else {
    $new_user = new User();
    $user_img_url = $google_profile_data["picture"];
    try {
      $attempt_store_img = store_user_img($user_img_url);
      // if failed storing image from api reponse assign default image
      if ($attempt_store_img == false) {
        $new_user->user_img = "default.jpg";
      } else {
        // else filename string was returned thus assign to user_img
        $new_user->user_img = $attempt_store_img;
      }
    } catch (Exception) {
      // if any other fails just assign a default image
      $new_user->user_img = "default.jpg";
    }
    $new_user->user_id = -1;
    $new_user->user_email = $email_to_check;
    $new_user->user_registration = new DateTime(date("Y-m-d"));
    $new_user->user_firstname = $google_profile_data["given_name"];
    $new_user->user_lastname = $google_profile_data["family_name"];
    $new_user->user_type = "user";
    $new_user->user_auth_method = 2; // 2 for google

    // with oauth store user with null password
    add_user($new_user, "");
    $stored_user_id = get_id_by_existing_email($email_to_check);
    // after successfully adding grab next assigned ID (providing it worked else fail)
    if ($stored_user_id == false) {
      redirect_with_query(
        "../index.php",
        ["error" => "internalerr"],
        ["err_message" => "oauth_google_unable_id_fetch_user_not_registered"]
      );
    }
    //if adding image under temp name worked and it is not a default image
    // attempt to rename it with final user ID coming from the DB and their name
    // (id in sync with the DB )
    if ($new_user->user_img != "default.jpg") {
      try {
        $new_img_path = "../res/user_img/" . $new_user->user_img;
        $command = "convert $new_img_path -gravity center -crop 1:1^ -resize 96x96 $new_img_path";
        exec($command);
      } catch (Exception) {
        redirect_with_query(
          "../index.php",
          ["error" => "errorimg"],
          ["err_message" => "oauth_google_fail_img_resize"]
        );
      }
    }
    // after registration redirect to profile page
    session_start();
    $type = get_user_type_by_id($stored_user_id);
    $_SESSION["user_id"] = $stored_user_id;
    $_SESSION["user_type"] = $type;
    header("Location:../pages/profile.php");
  }
}

//
//WARN:
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// ASSESSMENT: this is the place where task 1 is done, handling it
// in similar way to google. curl until user data is back then handle
// the internal account based on that

//  FN: _______________________________________________________________________
// handler for github oauth
// 1)get code/token/user-data .then check if registered by email query
// 2)if regged and via  oauth then start session,
// else inform user email already in use for a different login method
// 3)if all conditions checked and failed else register as new user

function handle_github()
{
  require_once "user_functionality.php";
  require_once "../github/login_conf.php";
  $oauth_json_raw = base64_decode(getenv("GITHUB_CLIENT_JSON"));
  $oauth_obj = json_decode($oauth_json_raw, true);
  $code = $_GET["code"];
  $state = $_GET["state"];
  $_SESSION["oauth2state"] = $state;
  $query_params = [
    "client_id" => $oauth_obj["web"]["client_id"],
    "client_secret" => $oauth_obj["web"]["client_secret"],
    "redirect_uri" => $oauth_obj["web"]["redirect_uri"],
    "state" => $_SESSION["oauth2state"],
    "code" => $code,
  ];
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, "https://github.com/login/oauth/access_token");
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query_params));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json"]);
  $response = curl_exec($ch);
  curl_close($ch);
  $response_obj = json_decode($response, true);
  if (
    !isset($response_obj["access_token"]) ||
    empty($response_obj["access_token"])
  ) {
    redirect_with_query(
      "index.php",
      ["error" => "autherror"],
      ["err_message" => "github_no_access_token_received"]
    );
  }

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, "https://api.github.com/user");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $response_obj["access_token"],
    "Accept: application/vnd.github+json",
    "User-Agent: PhoneZone School Demo",
  ]);
  $response = curl_exec($ch);
  // var_dump($response);
  curl_close($ch);
  $github_profile_data = json_decode($response, true);
  $response = json_encode($github_profile_data);
  $email_to_check = $github_profile_data["email"];
  if (check_if_email_exists($email_to_check)) {
    $connection = get_mysqli();
    $query = "SELECT user_auth_method FROM users WHERE user_email = ?";
    $statement = $connection->prepare($query);
    if (!$statement) {
      mysqli_close($connection);
      redirect_with_query(
        "../index.php",
        ["error" => "internalerr"],
        ["err_message" => "oauth_google_fail_db_check_01"]
      );
    }
    mysqli_stmt_bind_param($statement, "s", $email_to_check);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);
    if (!$result) {
      db_tidy_up($statement, $connection);
      redirect_with_query(
        "../index.php",
        ["error" => "internalerr"],
        ["err_message" => "oauth_google_fail_db_check_02"]
      );
    }
    $user_auth_method = mysqli_fetch_assoc($result)["user_auth_method"];
    db_tidy_up($statement, $connection);
    if ($user_auth_method == 3) {
      session_start();
      $id = get_id_by_existing_email($email_to_check);
      $type = get_user_type_by_id($id);
      $_SESSION["user_id"] = $id;
      $_SESSION["user_type"] = $type;
      header("Location:../pages/profile.php");
    } else {
      redirect_with_query("../index.php", ["error" => "emailtaken"]);
    }
    //the only remaining route is registration
  } else {
    $new_user = new User();
    $name_to_process;
    //fallback if no "name"
    if (isset($github_profile_data["name"])) {
      $name_to_process = $github_profile_data["name"];
    } else {
      $name_to_process = $github_profile_data["login"] . " GitHubUser";
    }

    //fallback if name has one word only
    if (!str_contains($name_to_process, " ")) {
      $name_to_process = $name_to_process . " GitHubUser";
    }

    //had to explode stuff at least once when using PHP :D
    // reference:
    // https://i.pinimg.com/736x/85/ec/ff/85ecffae40d2ff81eb4ec63d2e61ab3d.jpg
    $name_to_process = explode(" ", $name_to_process);

    $user_img_url = $github_profile_data["avatar_url"];
    try {
      $attempt_store_img = store_user_img($user_img_url);
      // if failed storing image from api reponse assign default image
      if ($attempt_store_img == false) {
        $new_user->user_img = "default.jpg";
      } else {
        // else filename string was returned thus assign to user_img
        $new_user->user_img = $attempt_store_img;
      }
    } catch (Exception) {
      // if any other fails just assign a default image
      $new_user->user_img = "default.jpg";
    }
    $new_user->user_id = -1;
    $new_user->user_email = $email_to_check;
    $new_user->user_registration = new DateTime(date("Y-m-d"));
    $new_user->user_firstname = $name_to_process[0];
    $new_user->user_lastname = $name_to_process[count($name_to_process) - 1];
    $new_user->user_type = "user";
    $new_user->user_auth_method = 3; // 3 for github

    // with oauth store user with null password
    add_user($new_user, "");
    $stored_user_id = get_id_by_existing_email($email_to_check);
    // after successfully adding grab next assigned ID (providing it worked else fail)
    if ($stored_user_id == false) {
      redirect_with_query(
        "../index.php",
        ["error" => "internalerr"],
        ["err_message" => "oauth_github_unable_id_fetch_user_not_registered"]
      );
    }

    //if adding image under temp name worked and it is not a default image
    // attempt to rename it with final user ID coming from the DB and their name
    // (id in sync with the DB )
    if ($new_user->user_img != "default.jpg") {
      try {
        $new_img_path = "../res/user_img/" . $new_user->user_img;
        $command = "convert $new_img_path -gravity center -crop 1:1^ -resize 96x96 $new_img_path";
        exec($command);
      } catch (Exception) {
        redirect_with_query(
          "../index.php",
          ["error" => "errorimg"],
          ["err_message" => "oauth_github_fail_img_resize"]
        );
      }
    }
    // after registration redirect to profile page
    session_start();
    $type = get_user_type_by_id($stored_user_id);
    $_SESSION["user_id"] = $stored_user_id;
    $_SESSION["user_type"] = $type;
    header("Location:../pages/profile.php");
  }
}
