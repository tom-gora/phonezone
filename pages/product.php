<!doctype html>
<html lang="en" data-theme="light">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>PhoneZone Product</title>
  <link rel="icon" type="image/png" href="../res/favicon.ico" />
  <link href="../css/output/tailwind-styles.css" rel="stylesheet" />
  <link rel="stylesheet" href="../css/globals.css">
</head>


<body class="min-h-screen h-full flex flex-col items-center w-screen gap-4 md:pt-24">
<?php
define("ALLOW_REQUIRED_SCRIPTS", true);
session_start();

// bring in the php scripts
require_once "../scripts/db.php";
require_once "../scripts/products_functionality.php";
require_once "../google/login_conf.php";
require_once "../github/login_conf.php";
// grab markup for my html components
$cart_sidebar_html = file_get_contents("../html_components/cart_sidebar.html");
$nav_html = file_get_contents("../html_components/navigation.html");
$mobile_toggle = file_get_contents("../html_components/mobile_toggle.html");
$footer_html = file_get_contents("../html_components/footer.html");
$pagination_html = file_get_contents("../html_components/pagination.html");
$login_dialog_html = file_get_contents("../html_components/dialog_login.html");
$reg_dialog_html = file_get_contents("../html_components/dialog_register.html");

$breadcrumbs_html = file_get_contents("../html_components/breadcrumbs.html");
$product_page_html = file_get_contents("../html_components/product_view.html");

$google_login_button_target = get_google_login_url();
$github_login_button_target = get_github_auth_url();
$github_login_button_target = $github_login_button_target . "&scope=user:email";

if (!isset($_SESSION["user_id"])) {
  // changes when user is logged out
  $login_dialog_html = str_replace(
    ["GOOGLE_API_URL", "GITHUB_API_URL"],
    [
      "window.location = '" . $google_login_button_target . "';",
      "window.location = '" . $github_login_button_target . "';",
    ],
    $login_dialog_html
  );

  $nav_html = str_replace(
    [
      "for-logged-out nav-link hidden",
      "for-logged-out mb-auto mt-4 hidden",
      "scripts/login.php",
    ],
    ["nav-link", "mb-auto mt-24", "../scripts/login.php"],
    $nav_html
  );
} else {
  // changes when user is logged in
  require_once "../scripts/user_functionality.php";
  $logged_in_mf = get_user($_SESSION["user_id"]);
  $nav_html = str_replace(
    [
      "for-logged-in nav-link hidden",
      "for-logged-in nav-link group hidden",
      "pages/products.php",
      "pages/profile.php",
      "scripts/logout.php",
      "res/user_img/PROFILE_USER_IMG",
    ],
    [
      "nav-link",
      "nav-link group",
      "../pages/products.php",
      "../pages/profile.php",
      "../scripts/logout.php",
      "../res/user_img/" . $logged_in_mf->user_img,
    ],
    $nav_html
  );
}

$id_to_get = $_GET["id"];

$product = get_product($id_to_get);

$product_page_html = str_replace(
  ["PRODUCT_ID", "PRODUCT_NAME", "PRODUCT_IMG", "PRODUCT_PRICE"],
  [
    $id_to_get,
    $product->product_name,
    $product->product_img_path,
    $product->product_price,
  ],
  $product_page_html
);
$breadcrumbs_html = str_replace(
  "CURRENT_PAGE",
  $product->product_name,
  $breadcrumbs_html
);

$footer_html = str_replace(
  ["bg-bg-light", "bg-bg-darker"],
  ["bg-bg-lighter", "bg-bg-dark"],
  $footer_html
);

echo $cart_sidebar_html;
echo $login_dialog_html;
echo $reg_dialog_html;
echo $nav_html;
echo $breadcrumbs_html;
echo $product_page_html;
echo $footer_html;
echo $mobile_toggle;
?>
<script type="module" src="../js/navigation.js"></script>
<script defer type="module" src="../js/cart.js"></script>

</body>
