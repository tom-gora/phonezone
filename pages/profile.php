<!-- source for box shadow snippets https://manuarora.in/boxshadows -->
<!-- animations from https://www.tailwindcss-animated.com/ -->
<?php
// this is logged in content exclusively so first grab the user data
define("ALLOW_REQUIRED_SCRIPTS", true);
session_start();
$user_id = isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : null;

//disallow not logged in users
if (!$user_id) {
  header("location:../index.php?error=nologin");
  exit();
}
//only start work and involve database when confirmed session status
require_once "../scripts/db.php";
require_once "../scripts/user_functionality.php";
require_once "../google/login_conf.php";
require_once "../github/login_conf.php";
$connection = get_mysqli();
$query = "SELECT * FROM users WHERE user_id = '$user_id'";
$result = mysqli_query($connection, $query);
$row = mysqli_fetch_assoc($result);

//construct current user object
$logged_in_mf = new User();
$logged_in_mf->user_id = $user_id;
$logged_in_mf->user_email = $row["user_email"];
$logged_in_mf->user_registration = new DateTime($row["user_registration"]);
$logged_in_mf->user_firstname = $row["user_firstname"];
$logged_in_mf->user_lastname = $row["user_lastname"];
$logged_in_mf->user_img = $row["user_img"] ?: "default.jpg";
$logged_in_mf->user_type = $row["user_type"];
$logged_in_mf->user_auth_method = $row["user_auth_method"];
$breadcrumbs_html = file_get_contents("../html_components/breadcrumbs.html");

//preformat the date for the page
$formatted_date = $logged_in_mf->user_registration->format("d M y");
?>

<!-- begin markup -->
<!doctype html>
<html lang="en" data-theme="light">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="icon" type="image/png" href="../res/favicon.ico" />
    <title>PhoneZone
      <?php echo " - Your profile: " .
        $logged_in_mf->user_firstname .
        " " .
        $logged_in_mf->user_lastname; ?>

    </title>
  <link href="../css/output/tailwind-styles.css" rel="stylesheet" />
  <link rel="stylesheet" href="../css/globals.css">
</head>


<body class="min-h-screen flex flex-col items-center gap-4 md:pt-24">
<?php
// bring in the base markup for my html components
$cart_sidebar_html = file_get_contents("../html_components/cart_sidebar.html");
$nav_html = file_get_contents("../html_components/navigation.html");
$mobile_toggle = file_get_contents("../html_components/mobile_toggle.html");
$profile_header_html = file_get_contents(
  "../html_components/profile_header.html"
);
$edit_dialog_html = file_get_contents("../html_components/dialog_edit.html");
$del_dialog_html = file_get_contents("../html_components/dialog_delete.html");

$edit_dialog_html = str_replace(
  [
    "Edit details</h2>",
    'name="edit-user-id" value=""',
    'name="edit-user-email"',
    'name="edit-user-first-name"',
    'name="edit-user-last-name"',
    'name="edit-user-password"',
    'id="label-bg"',
  ],
  [
    "Edit your details</h2>",
    'name="edit-user-id" value="' . $logged_in_mf->user_id . '"',
    'name="edit-user-email" value="' . $logged_in_mf->user_email . '"',
    'name="edit-user-first-name" value="' . $logged_in_mf->user_firstname . '"',
    'name="edit-user-last-name" value="' . $logged_in_mf->user_lastname . '"',
    'name="edit-user-password" value=""',
    'id="label-bg" style="background-image: url(../res/user_img/' .
    $logged_in_mf->user_img .
    '); background-size: cover; background-position: center;"',
  ],
  $edit_dialog_html
);
// WOW I will always remeber how I fucking came up with this solution
// just by reading docs for select element and flipping the AI off HARD
// I fucking KNEW this could be done on the server without bloody JS!!!!!!!

// WARN: ASSESSMENT:
// This is a fancy addition. Not only I preselect role in the form on the server
// for the user, but also modify options, so that user cannot do shit and sees
// shit, admin can demote themselves, but not ascend to ownershit and the owner
// can play fucking 11-D chess made of strings and branes if he wants XD
switch ($logged_in_mf->user_type) {
  case "user":
    $edit_dialog_html = str_replace(
      [
        '<option value="owner">Owner</option>',
        '<option value="admin">Admin</option',
        '<option value="user">User</option>',
        'id="role-input" class="',
      ],
      [
        "",
        "",
        '<option value="user" selected>User</option>',
        'id="role-input" class="hidden ',
      ],
      $edit_dialog_html
    );
    break;
  case "admin":
    $edit_dialog_html = str_replace(
      [
        '<option value="owner">Owner</option>',
        '<option value="admin">Admin</option',
      ],
      ["", '<option value="admin" selected>Admin</option>'],
      $edit_dialog_html
    );
    break;
  case "owner":
    $edit_dialog_html = str_replace(
      ['<option value="owner">Owner</option>'],
      ['<option value="owner" selected>Owner</option>'],
      $edit_dialog_html
    );
    break;
}
$del_dialog_html = str_replace(
  'name="del-user-id" value="delete_user',
  'name="del-user-id" value="' . $user_id,
  $del_dialog_html
);
$profile_cart_html = file_get_contents("../html_components/profile_cart.html");
$profile_cart_card_html = file_get_contents(
  "../html_components/profile_cart_card.html"
);
$profile_order_history = file_get_contents(
  "../html_components/profile_history.html"
);
$footer_html = file_get_contents("../html_components/footer.html");

$nav_html = str_replace(
  ["res/logo-h.png", 'href="index.php"'],
  ["../res/logo-h.png", 'href="../index.php"'],
  $nav_html
);

//this page cannot be accessed by non-logged in users so no conditional
//markup text swapping is needed. only changes appying to logged in users

//conditional changes based on user type (for admin/owner stuff)
if (isset($_SESSION["user_type"]) && $_SESSION["user_type"] != "user") {
  $profile_header_html = str_replace(
    ["role hidden", "PROFILE_USER_ROLE", "btn-primary hidden"],
    ["", ucfirst($_SESSION["user_type"]), "btn-primary"],
    $profile_header_html
  );
}
// adjust navbar strings
$nav_html = str_replace(
  [
    "for-logged-in nav-link hidden",
    "for-logged-in nav-link group hidden",
    "pages/profile.php",
    "res/user_img/PROFILE_USER_IMG",
    "pages/products.php",
    "scripts/logout.php",
  ],
  [
    "nav-link",
    "nav-link group",
    "../pages/profile.php",
    "../res/user_img/" . $logged_in_mf->user_img,
    "../pages/products.php",
    "../scripts/logout.php",
  ],
  $nav_html
);

// adjust profile header strings
$profile_header_html = str_replace(
  [
    "PROFILE_USER_NAME",
    "PROFILE_USER_EMAIL",
    "PROFILE_USER_REGISTRATION_DATE",
    "PROFILE_USER_IMG",
  ],
  [
    $logged_in_mf->user_firstname . " " . $logged_in_mf->user_lastname,
    $logged_in_mf->user_email,
    $formatted_date,
    $logged_in_mf->user_img,
  ],
  $profile_header_html
);

$cart_products_json = get_user_cart_state($logged_in_mf->user_id);
if ($cart_products_json == "[]" || $cart_products_json == "no_cart") {
  $profile_cart_html = str_replace(
    "CART_CONTETS",
    '<p class="py-4 text-bg-info">You haven\'t got anything in your cart at the moment.</p>',
    $profile_cart_html
  );
} else {
  $cart_data = json_decode($cart_products_json);

  $product_ids_to_retrieve = [];
  foreach ($cart_data->products as $product) {
    $product_ids_to_retrieve[] = $product->product_id;
  }
  $cards_string_builder = "";
  foreach ($product_ids_to_retrieve as $product_id) {
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
    while ($row = mysqli_fetch_assoc($result)) {
      $product_img = $row["product_img_path"];
      $product_name = $row["product_name"];
      $product_price = $row["product_price"];
      $product_id = $row["product_id"];
      $product_amount = null;
      foreach ($cart_data->products as $product) {
        if ($product->product_id == $product_id) {
          $product_amount = $product->product_amount;
          break;
        }
      }
      $card_html = $profile_cart_card_html;
      $card_html = str_replace(
        [
          "PRODUCT_ID",
          "PRODUCT_IMG",
          "PRODUCT_NAME",
          "PRODUCT_PRICE",
          "PRODUCT_AMOUNT",
        ],
        [
          $product_id,
          $product_img,
          $product_name,
          $product_price,
          $product_amount,
        ],
        $card_html
      );
      $cards_string_builder .= $card_html;
    }
  }
  $profile_cart_html = str_replace(
    "CART_CONTETS",
    $cards_string_builder,
    $profile_cart_html
  );
}
$footer_html = str_replace(
  ["bg-bg-light", "bg-bg-darker"],
  ["bg-bg-lighter", "bg-bg-dark"],
  $footer_html
);

$breadcrumbs_html = str_replace(
  [
    "my-4",
    '  <li class="inline-flex items-center">
    <a
      class="flex items-center text-sm transition duration-300 hover:text-bg-info focus:bg-bg-info focus:outline-none"
      href="./products.php">
      Shop
      <svg
        class="mx-2 size-4 flex-shrink-0 overflow-visible text-bg-info"
        xmlns="http://www.w3.org/2000/svg"
        width="24"
        height="24"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        style="--darkreader-inline-stroke: currentColor"
        data-darkreader-inline-stroke="">
        <path d="m9 18 6-6-6-6"></path>
      </svg>
    </a>
  </li>
',
    "CURRENT_PAGE",
  ],
  ["mt-4", "", "Your Profile"],
  $breadcrumbs_html
);

// echo content to the page
echo $cart_sidebar_html;
echo $edit_dialog_html;
echo $del_dialog_html;
echo $nav_html;
echo $breadcrumbs_html;
echo $profile_header_html;
echo "<div id='profile-purchases-details' class='w-full px-[8.33%] bg-bg-light dark:bg-bg-darker pt-12 h-max grow md:min-h-80 items-center flex flex-col gap-4 justify-start'>";
echo $profile_cart_html;
echo $profile_order_history;
echo "</div>";
echo $footer_html;
echo $mobile_toggle;
?>
<script type="module" src="../js/navigation.js"></script>
<script defer type="module" src="../js/cart.js"></script>
<script type="module" src="../js/profile.js"></script>
  </body>
</html>
