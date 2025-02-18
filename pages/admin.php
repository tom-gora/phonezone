<!-- source for box shadow snippets https://manuarora.in/boxshadows -->
<!-- animations from https://www.tailwindcss-animated.com/ -->
<?php
// this is logged in content exclusively so first grab the user data
// plus make sure no regular user is trying to access this page
define("ALLOW_REQUIRED_SCRIPTS", true);
session_start();
$user_id = isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : null;
$user_role = isset($_SESSION["user_type"]) ? $_SESSION["user_type"] : null;
if (!$user_id || $user_role == "user") {
  header("location:../index.php?error=noadmin");
  exit();
}
?>

<!-- begin markup -->
<!doctype html>
<html lang="en" data-theme="light">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>PhoneZone - Admin Panel</title>
    <link rel="icon" type="image/png" href="../res/favicon.ico" />
    <link href="../css/output/tailwind-styles.css" rel="stylesheet" />
    <link rel="stylesheet" href="../css/globals.css" />
  </head>

  <body class="h-screen items-center flex flex-col">
    <?php
    //only establish connection after confirming session status
    require_once "../scripts/db.php";
    require_once "../scripts/user_functionality.php";
    require_once "../google/login_conf.php";

    // get the markup
    $cart_sidebar_html = file_get_contents(
      "../html_components/cart_sidebar.html"
    );
    $nav_html = file_get_contents("../html_components/navigation.html");
    $mobile_toggle = file_get_contents("../html_components/mobile_toggle.html");
    $users_grid_html = file_get_contents(
      "../html_components/admin_panel_user_grid.html"
    );
    $user_card_html = file_get_contents(
      "../html_components/admin_user_card.html"
    );
    $pagination_html = file_get_contents("../html_components/pagination.html");
    $footer_html = file_get_contents("../html_components/footer.html");
    $del_dialog_html = file_get_contents(
      "../html_components/dialog_delete.html"
    );
    $edit_dialog_html = file_get_contents(
      "../html_components/dialog_edit.html"
    );
    $breadcrumbs_html = file_get_contents(
      "../html_components/breadcrumbs.html"
    );

    //get the logged in user avatar for the navigation miniature
    $connection = get_mysqli();
    $query = "SELECT user_img FROM users WHERE user_id = '$user_id'";
    $result = mysqli_query($connection, $query);
    $row = mysqli_fetch_assoc($result);

    //fix nav as usual - links, details etc specific per page
    $nav_html = str_replace(
      [
        "res/user_img/PROFILE_USER_IMG",
        "for-logged-in nav-link hidden",
        "for-logged-in nav-link group hidden",
        "res/logo-h.png",
        'href="index.php"',
        "pages/products.php",
        "pages/profile.php",
        "scripts/logout.php",
      ],
      [
        "../res/user_img/" . $row["user_img"],
        "nav-link",
        "nav-link group",
        "../res/logo-h.png",
        'href="../index.php"',
        "../pages/products.php",
        "../pages/profile.php",
        "../scripts/logout.php",
      ],
      $nav_html
    );

    $query = "SELECT COUNT(user_id) AS total_users FROM users;";
    $statement = mysqli_prepare($connection, $query);
    if (!$statement) {
      mysqli_close($connection);
      $err_msg_params = ["error_msg" => "err_retrieving_user_count_01"];
      redirect_with_query("../index.php", $err_msg_params);
    }
    $result = mysqli_stmt_execute($statement);
    if (!$result) {
      db_tidy_up($statement, $connection);
      $err_msg_params = ["error_msg" => "err_retrieving_user_count_02"];
      redirect_with_query("../index.php", $err_msg_params);
    }
    $result = mysqli_stmt_get_result($statement);
    db_tidy_up($statement, $connection);
    $row = mysqli_fetch_assoc($result);
    $total_users = $row["total_users"];

    $pagination_html = str_replace(
      ['id="pagination"', "scale-90"],
      ['id="pagination" data-total-users=' . $total_users, "scale-90 mb-12"],
      $pagination_html
    );

    $breadcrumbs_html = str_replace(
      [
        "my-4",
        "CURRENT_PAGE",
        'href="./products.php">
      Shop',
      ],
      [
        "mt-28 mb-4",
        "Admin Panel",
        'href="./profile.php">
      Your Profile',
      ],
      $breadcrumbs_html
    );

    // add content onto the page before shipping to client
    echo $del_dialog_html;
    echo $edit_dialog_html;
    echo $cart_sidebar_html;
    echo $nav_html;
    echo $breadcrumbs_html;
    echo $users_grid_html;
    echo $pagination_html;
    echo $footer_html;
    echo $mobile_toggle;
    ?>

<script type="module" src="../js/navigation.js"></script>
<script defer type="module" src="../js/cart.js"></script>
<script type="module" src="../js/admin.js"></script>
  </body>
</html>
