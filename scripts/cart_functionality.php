<?php
require_once "db.php";
require_once "utils.php";
ban_script_access();

$connection = get_mysqli();
$json = file_get_contents("php://input");
$obj = json_decode($json);
$client_request = $obj->client_request;
switch ($client_request) {
  case "get_product_for_cart":
    $product_id = $obj->product_id;

    $query = "SELECT * FROM products WHERE product_id = ?";
    $statement = mysqli_prepare($connection, $query);

    if (!$statement) {
      redirect_with_query(
        "../index.php",
        ["error" => "internalerr"],
        ["err_message" => "err_getting_product_for_cart_01"]
      );
    }
    mysqli_stmt_bind_param($statement, "i", $product_id);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);
    if (!$result) {
      redirect_with_query(
        "../index.php",
        ["error" => "internalerr"],
        ["err_message" => "err_getting_product_for_cart_02"]
      );
    }
    $row = mysqli_fetch_assoc($result);

    echo json_encode($row);
    break;
  case "init_cart":
    $user_id = $obj->user_id;
    $query = "SELECT * FROM cart_state_store WHERE user_id =?";
    $statement = mysqli_prepare($connection, $query);
    if (!$statement) {
      redirect_with_query(
        "../index.php",
        ["error" => "internalerr"],
        ["err_message" => "err_getting_cart_state_01"]
      );
    }
    mysqli_stmt_bind_param($statement, "i", $user_id);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);
    if (!$result) {
      redirect_with_query(
        "../index.php",
        ["error" => "internalerr"],
        ["err_message" => "err_getting_cart_state_02"]
      );
    }
    $row = mysqli_fetch_assoc($result);
    echo json_encode($row ?: ["order_contents" => "no_cart"]);
    break;
  case "save_cart_state":
    if ($obj->user_id == "no_id") {
      echo json_encode(["success" => "false", "err_message" => "no_user_id"]);
      break;
    }
    $user_id = $obj->user_id;
    $cart_state = json_encode($obj->cart_state);
    $query =
      "INSERT INTO cart_state_store (user_id, order_contents, state_save_timestamp) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE order_contents = VALUES(order_contents), state_save_timestamp = NOW();";
    $statement = mysqli_prepare($connection, $query);
    if (!$statement) {
      redirect_with_query(
        "../index.php",
        ["error" => "internalerr"],
        ["err_message" => "err_saving_cart_state_01"]
      );
    }
    mysqli_stmt_bind_param($statement, "is", $user_id, $cart_state);
    $result = mysqli_stmt_execute($statement);

    if (!$result) {
      echo json_encode([
        "success" => "false",
        "error" => "execution_error",
        "message" => mysqli_stmt_error($statement),
      ]);
      break;
    }
    if (mysqli_stmt_affected_rows($statement) == 0) {
      echo json_encode(["success" => "false", "error" => "no_changes_written"]);
      break;
    }
    echo json_encode(["success" => "true"]);
    break;
  case "delete_cart_state":
    $user_id = $obj->user_id;
    $query = "DELETE FROM cart_state_store WHERE user_id =?";
    $statement = mysqli_prepare($connection, $query);
    if (!$statement) {
      redirect_with_query(
        "../index.php",
        ["error" => "internalerr"],
        ["err_message" => "err_deleting_cart_state_01"]
      );
    }
    mysqli_stmt_bind_param($statement, "i", $user_id);
    $result = mysqli_stmt_execute($statement);
    if (!$result) {
      echo json_encode([
        "success" => "false",
        "error" => "execution_error",
        "message" => mysqli_stmt_error($statement),
      ]);
      break;
    }
    if (mysqli_stmt_affected_rows($statement) == 0) {
      echo json_encode(["success" => "false", "error" => "no_changes_written"]);
      break;
    }
    echo json_encode(["success" => "true"]);
    break;

  case "get_product_prices_only":
    $product_ids_arr = $obj->product_ids;
    $temp_arr = [];

    foreach ($product_ids_arr as $product_id) {
      $query =
        "SELECT product_id, product_price FROM products WHERE product_id = ?";
      $statement = mysqli_prepare($connection, $query);
      if (!$statement) {
        redirect_with_query(
          "../index.php",
          ["error" => "internalerr"],
          ["err_message" => "err_getting_product_price_01"]
        );
      }

      mysqli_stmt_bind_param($statement, "i", $product_id);
      mysqli_stmt_execute($statement);
      $result = mysqli_stmt_get_result($statement);
      if (!$result) {
        redirect_with_query(
          "../index.php",
          ["error" => "internalerr"],
          ["err_message" => "err_getting_product_price_02"]
        );
      }

      $row = mysqli_fetch_assoc($result);
      if ($row) {
        $temp_arr[] = $row;
      }

      mysqli_stmt_close($statement);
    }

    echo json_encode($temp_arr);
    break;
  case "store_order":
    $user_id = $obj->user_id;
    $order_contents = json_encode($obj->order_contents);
    $query =
      "INSERT INTO orders (order_contents, user_id, order_timestamp) VALUES (?, ?, NOW())";
    $statement = mysqli_prepare($connection, $query);
    if (!$statement) {
      redirect_with_query(
        "../index.php",
        ["error" => "internalerr"],
        ["err_message" => "err_saving_order_01"]
      );
    }
    mysqli_stmt_bind_param($statement, "si", $order_contents, $user_id);
    $result = mysqli_stmt_execute($statement);
    if (!$result) {
      echo json_encode([
        "success" => "false",
        "error" => "execution_error",
        "message" => mysqli_stmt_error($statement),
      ]);
      break;
    }
    if (mysqli_stmt_affected_rows($statement) == 0) {
      echo json_encode(["success" => "false", "error" => "no_changes_written"]);
      break;
    }
    echo json_encode(["success" => "true"]);
    break;
  case "get_orders":
    $user_id = $obj->user_id;
    $query = "SELECT * FROM orders WHERE user_id =?";
    $statement = mysqli_prepare($connection, $query);
    if (!$statement) {
      redirect_with_query(
        "../pages/profile.php",
        ["error" => "internalerr"],
        ["err_message" => "err_getting_orders_01"]
      );
    }
    mysqli_stmt_bind_param($statement, "i", $user_id);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);
    if (!$result) {
      redirect_with_query(
        "../pages/profile.php",
        ["error" => "internalerr"],
        ["err_message" => "err_getting_orders_02"]
      );
    }
    $orders_arr = [];
    while ($row = mysqli_fetch_assoc($result)) {
      $order_data = [
        "order_contents" => $row["order_contents"],
        "order_hash_number" => $row["order_hash_number"],
      ];
      $orders_arr[] = $order_data;
    }
    if (empty($orders_arr)) {
      echo "no_orders";
      break;
    }

    $query = "SELECT * FROM orders WHERE user_id =?";
    $statement = mysqli_prepare($connection, $query);
    if (!$statement) {
      redirect_with_query(
        "../pages/profile.php",
        ["error" => "internalerr"],
        ["err_message" => "err_getting_orders_01"]
      );
    }
    mysqli_stmt_bind_param($statement, "i", $user_id);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);
    if (!$result) {
      redirect_with_query(
        "../pages/profile.php",
        ["error" => "internalerr"],
        ["err_message" => "err_getting_orders_02"]
      );
    }

    $products_ids_arr = [];
    foreach ($orders_arr as $order_data) {
      $decoded_content = json_decode($order_data["order_contents"], true);
      if (isset($decoded_content["products"])) {
        foreach ($decoded_content["products"] as $product) {
          $products_ids_arr[] = $product["product_id"];
        }
      }
    }

    $products_details_arr = [];
    foreach ($products_ids_arr as $product_id) {
      $product_query =
        "SELECT product_id, product_name, product_price FROM products WHERE product_id =?";
      $statement = mysqli_prepare($connection, $product_query);
      if (!$statement) {
        redirect_with_query(
          "../pages/profile.php",
          ["error" => "internalerr"],
          ["err_message" => "err_getting_product_01"]
        );
      }
      mysqli_stmt_bind_param($statement, "i", $product_id);
      mysqli_stmt_execute($statement);
      $result = mysqli_stmt_get_result($statement);
      if (!$result) {
        redirect_with_query(
          "../pages/profile.php",
          ["error" => "internalerr"],
          ["err_message" => "err_getting_product_02"]
        );
      }
      $row = mysqli_fetch_assoc($result);
      $products_details_arr[] = $row;
    }
    $combined_arr = [];

    foreach ($orders_arr as $order_data) {
      $decoded_content = json_decode($order_data["order_contents"], true);
      $combined_products = [];

      if (isset($decoded_content["products"])) {
        foreach ($decoded_content["products"] as $product) {
          $product_id = $product["product_id"];
          $combined_product = $product;

          foreach ($products_details_arr as $product_details) {
            if ($product_details["product_id"] == $product_id) {
              $combined_product["product_name"] =
                $product_details["product_name"];
              $combined_product["product_price"] =
                $product_details["product_price"];
              break;
            }
          }
          $combined_products[] = $combined_product;
        }
      }

      $decoded_content["products"] = $combined_products;
      $decoded_content["order_hash_number"] = $order_data["order_hash_number"];
      $combined_arr[] = $decoded_content;
    }
    echo json_encode($combined_arr);
    break;
}
