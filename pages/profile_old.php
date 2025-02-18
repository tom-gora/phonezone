<?php

session_start();
$user_id = isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : null;

if (!$user_id) {
  header("location:../index.php?error=nologin");
  exit();
}

//only establish connection after confirming session status
require_once "../scripts/db.php";
$connection = get_mysqli();

if (isset($_POST["confirm_delete"])) {
  // Delete the user account
  $delete_query = mysqli_query(
    $connection,
    "DELETE FROM users WHERE user_id = '$user_id'"
  );
  if ($delete_query) {
    // Account deleted successfully
    unset($_SESSION["user_id"]);
    session_destroy();
    header("location:login.php");
    exit();
  } else {
    // Error deleting account
    $message = "Error deleting account: " . mysqli_error($connection);
  }
}

if (isset($_POST["delete_selected"])) {
  if (isset($_POST["user_ids"]) && is_array($_POST["user_ids"])) {
    foreach ($_POST["user_ids"] as $user_id_to_delete) {
      // Delete the selected user accounts
      $delete_query = mysqli_query(
        $connection,
        "DELETE FROM users WHERE user_id = '$user_id_to_delete'"
      );
    }
    // Redirect to prevent resubmission
    header("Location: profile.php");
    exit();
  }
}

function displayUsers($connection, $fetch)
{
  $sql = "SELECT * FROM users";
  $result = $connection->query($sql);

  if ($result->num_rows > 0) {
    echo "<h3> Table of users </h3> ";
    echo "<form method='post'>";
    echo "<div class='table-responsive'>";
    echo "<table class='table table-bordered'>";

    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Type of User</th></tr>";

    while ($row = $result->fetch_assoc()) {
      echo "<tr>";
      echo "<td>" . $row["user_id"] . "</td>";
      echo "<td>" . $row["user_name"] . "</td>";
      echo "<td>" . $row["user_email"] . "</td>";
      echo "<td>" . $row["user_type"] . "</td>";
      echo "<td><input type='checkbox' name='user_ids[]' value='" .
        $row["user_id"] .
        "'></td>";
      echo "</tr>";
    }

    echo "</table>";
    echo "</div>";
    echo "<input type='submit' name='delete_selected' value='Delete Selected Users' class='btn btn-danger' onclick=\"return confirm('are you sure you want to delete the selected users?')\"  >";

    if ($fetch["user_type"] == "owner") {
      echo "<a href='add_admin.php' class='btn btn-danger'>Add Admin User</a>";
    }

    echo "</form>";
  } else {
    echo "0 results";
  }
}

// function displayproduct($connection, $fetch)
// {
//   // Fetch shopping cart items from the database
//   $user_id = $fetch["user_id"];
//   $query = "SELECT * FROM cart WHERE user_id = '$user_id'";
//   $result = mysqli_query($connection, $query);
//   if (mysqli_num_rows($result) > 0) {
//     $_SESSION["shopping_cart"] = mysqli_fetch_all($result, MYSQLI_ASSOC);
//   }
//
//   // Your display product function content here
//   if (isset($_POST["add"])) {
//     if (isset($_SESSION["shopping_cart"])) {
//       $item_array_id = array_column($_SESSION["shopping_cart"], "order_id");
//       if (!in_array($_GET["id"], $item_array_id)) {
//         $count = count($_SESSION["shopping_cart"]);
//         $item_array = [
//           "product_id" => $_GET["id"],
//           "product_name" => $_POST["hidden_name"],
//           "product_price" => $_POST["hidden_price"],
//           "product_quantity" => $_POST["quantity"],
//         ];
//         $_SESSION["shopping_cart"][$count] = $item_array;
//         // Save the shopping cart items to the database
//         saveShoppingCart($connection, $user_id, $_SESSION["shopping_cart"]);
//         echo '<script>window.location="profile.php"</script>';
//       } else {
//         echo '<script>alert("Product is already in  the cart")</script>';
//         echo '<script>window.location="profile.php"</script>';
//       }
//     } else {
//       $item_array = [
//         "product_id" => $_GET["id"],
//         "product_name" => $_POST["hidden_name"],
//         "product_price" => $_POST["hidden_price"],
//         "product_quantity" => $_POST["quantity"],
//       ];
//       $_SESSION["shopping_cart"][0] = $item_array;
//       // Save the shopping cart items to the database
//       saveShoppingCart($connection, $user_id, $_SESSION["shopping_cart"]);
//     }
//   }
//
//   if (isset($_GET["action"])) {
//     if ($_GET["action"] == "delete") {
//       foreach ($_SESSION["shopping_cart"] as $keys => $value) {
//         if ($value["product_id"] == $_GET["id"]) {
//           unset($_SESSION["shopping_cart"][$keys]);
//           // Update the shopping cart in the database after removing an item
//           saveShoppingCart($connection, $user_id, $_SESSION["shopping_cart"]);
//           echo '<script>alert("Product has been removed")</script>';
//           echo '<script>window.location="profile.php"</script>';
//         }
//       }
//     }
//   }
// }
//
// // Function to save shopping cart items to the database
// function saveShoppingCart($connection, $user_id, $shopping_cart)
// {
//   // Delete existing entries for the user
//   mysqli_query(
//     $connection,
//     "DELETE FROM shopping_cart WHERE user_id = '$user_id'"
//   );
//
//   // Insert new entries
//   foreach ($shopping_cart as $item) {
//     $product_id = $item["product_id"];
//     $product_name = $item["product_name"];
//     $product_price = $item["product_price"];
//     $product_quantity = $item["product_quantity"];
//     mysqli_query(
//       $connection,
//       "INSERT INTO shopping_cart (user_id, product_id, product_name, product_price, product_quantity) VALUES ('$user_id', '$product_id', '$product_name', '$product_price', '$product_quantity')"
//     );
//   }
// }
//
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="style.css">
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
</head>

<body>

  <div class="container">

    <div class="profile">
      <?php
      ($select = mysqli_query(
        $connection,
        "SELECT * FROM `users` WHERE user_id = '$user_id'"
      )) or die("query failed");
      if (mysqli_num_rows($select) > 0) {
        $fetch = mysqli_fetch_assoc($select);
      }

//if ($fetch["user_img"] == "") {
//  echo '<img src="images/default-avatar.png">';
//} else {
//  echo '<img src="uploaded_img/' . $fetch["image"] . '">';
//}
?>
      <h3><?php echo $fetch["user_name"]; ?></h3>
      <a href="update_profile.php" class="btn btn-primary">Update Profile</a>

      <?php if (
        $fetch["user_type"] == "user" ||
        $fetch["user_type"] == "admin"
      ) {
        echo '<form method="post" action="">';
        echo '<input type="submit" name="confirm_delete" value="Delete Your Account" class="btn btn-danger" onclick="return confirm(\'Are you sure you want to delete your account?\')">';
        echo "</form>";
      } ?>

      <a href="profile.php?logout=<?php echo $user_id; ?>" class="btn btn-danger">Logout</a>

      <p>New <a href="login.php">Login</a> or <a href="register.php">Register</a></p>
    </div>


    <div>


      <?php if (
        $fetch["user_type"] == "owner" ||
        $fetch["user_type"] == "admin"
      ) {
        displayUsers($connection, $fetch);
      }
//if ($fetch["user_type"] == "user") {<?php
//displayproduct($connection, $fetch);
//
?>

          <!-- Your shopping cart content here -->
          <!-- <h2>Shopping Cart</h2> -->
         <!-- <?php
//$query = "select * from product order by id asc";
//$result = mysqli_query($connection, $query);
//if (mysqli_num_rows($result) > 0) {
//  while ($row = mysqli_fetch_array($result)) {
?>
         //    <div class="col-md-3" style="float: left;">

           <!-- FUCK IT WHAT AN UNMAINTAINABLE IMPOSSIBLE TO LOOK AT LET ALONE -->
           <!-- REFACTOR MESS HOW PEOPLE WROTE PAGES LIKE THAT BLOODY 15 YEARS AGO?? -->
    <!-- I am deleting all of that witing my own cart module -->

         
  </div>
</body>

</html>
