<?php
require_once "db.php";
require_once "utils.php";
ban_script_access();

$json = file_get_contents("php://input");
$obj = json_decode($json);
$client_request = $obj->client_request;

if (!isset($client_request) || $client_request !== "get_hero_contents") {
  redirect_with_query(
    "../index.php",
    ["error" => "internalerr"],
    ["err_message" => "err_getting_pagination_01"]
  );
  exit();
}

require_once "products_functionality.php";
$connection = get_mysqli();

$query = "SELECT * FROM hero_contents ORDER BY hero_content_id ASC;";
$statement = mysqli_prepare($connection, $query);
if (!$statement) {
  redirect_with_query(
    "../index.php",
    ["error" => "internalerr"],
    ["err_message" => "err_getting_pagination_02"]
  );
}
mysqli_stmt_execute($statement);
$result = mysqli_stmt_get_result($statement);
if (!$result) {
  redirect_with_query(
    "../index.php",
    ["error" => "internalerr"],
    ["err_message" => "err_getting_pagination_03"]
  );
}
$contents = [];
$random_phones = get_random_products(3);
$counter = 0;
while ($row = mysqli_fetch_assoc($result)) {
  $rowHasInsertPhone = false;

  foreach ($row as &$value) {
    if (is_string($value)) {
      $value = mb_convert_encoding($value, "UTF-8", "UTF-8");
      $value = addslashes($value);
      $value = str_replace("INSERT_MONTH", date("F"), $value);
      $value = str_replace("\\", "", $value);
      if (strpos($value, "INSERT_PHONE") !== false) {
        $value = str_replace(
          "INSERT_PHONE",
          $random_phones[$counter]->product_name,
          $value
        );
        $rowHasInsertPhone = true;
      }
    }
  }
  unset($value);

  if ($rowHasInsertPhone) {
    $counter = ($counter + 1) % count($random_phones);
  }

  array_push($contents, $row);
}
echo json_encode($contents);
db_tidy_up($statement, $connection);
