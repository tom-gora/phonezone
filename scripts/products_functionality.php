<?php
require_once "db.php";
require_once "utils.php";
ban_script_access();
class Product
{
  public int $product_id;
  public string $product_name;
  public int $product_price;
  public string $product_img_path;
}

//  FN: _______________________________________________________________________
// function getting all products as array

function get_products()
{
  $products = [];
  $connection = get_mysqli();
  $query = "SELECT * FROM products";
  $statement = mysqli_prepare($connection, $query);
  if (!$statement) {
    redirect_with_query(
      "../index.php",
      ["error" => "internalerr"],
      ["err_message" => "err_getting_products_01"]
    );
  }
  mysqli_stmt_execute($statement);
  $result = mysqli_stmt_get_result($statement);
  if (!$result) {
    redirect_with_query(
      "../index.php",
      ["error" => "internalerr"],
      ["err_message" => "err_getting_products_02"]
    );
  }
  while ($row = mysqli_fetch_assoc($result)) {
    $product = new Product();
    $product->product_id = $row["product_id"];
    $product->product_name = $row["product_name"];
    $product->product_price = $row["product_price"];
    $product->product_img_path = $row["product_img_path"];
    array_push($products, $product);
  }
  return $products;
}

//  FN: _______________________________________________________________________
// function getting a product by id

function get_product(int $product_id)
{
  $connection = get_mysqli();
  $query = "SELECT * FROM products WHERE product_id = ?";
  $statement = mysqli_prepare($connection, $query);
  if (!$statement) {
    redirect_with_query(
      "../index.php",
      ["error" => "internalerr"],
      ["err_message" => "err_getting_product_01"]
    );
  }
  mysqli_stmt_bind_param($statement, "i", $product_id);
  mysqli_stmt_execute($statement);
  $result = mysqli_stmt_get_result($statement);
  if (!$result) {
    redirect_with_query(
      "../index.php",
      ["error" => "internalerr"],
      ["err_message" => "err_getting_product_02"]
    );
  }
  $row = mysqli_fetch_assoc($result);

  $product = new Product();
  $product->product_name = $row["product_name"];
  $product->product_price = $row["product_price"];
  $product->product_img_path = $row["product_img_path"];

  return $product;
}

//  FN: _______________________________________________________________________
// function getting array of required lenght of randomly shuffled products

function get_random_products(int $num_products)
{
  $products = get_products();
  shuffle($products);
  return array_slice($products, 0, $num_products);
}
?>
