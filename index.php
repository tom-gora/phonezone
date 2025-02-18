<!-- source for box shadow snippets https://manuarora.in/boxshadows -->
<!-- animations from https://www.tailwindcss-animated.com/ -->
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>PhoneZone</title>
  <link rel="icon" type="image/png" href="../phonezone/res/favicon.ico" />
  <link href="css/output/tailwind-styles.css" rel="stylesheet" />
  <link rel="stylesheet" href="css/globals.css">
</head>

<body class="flex flex-col gap-4 items-center h-screen md:pt-24">
  <?php
    define('ALLOW_REQUIRED_SCRIPTS', true);
    session_start();
    // if (!isset($_SESSION["teacher_access"])) {
    //  header("Location:./teacher-gate.php");
    // }

    require_once './scripts/products_functionality.php';
    require_once './google/login_conf.php';
    require_once './github/login_conf.php';

    // how many bestsellers I want to display
    $best_sellers_count_to_display = 4;

    // get my html components contents
    $cart_sidebar_html = file_get_contents('./html_components/cart_sidebar.html');
    $nav_html = file_get_contents('./html_components/navigation.html');
    $mobile_toggle = file_get_contents('./html_components/mobile_toggle.html');
    $cta_html = file_get_contents('./html_components/call_to_action.html');
    $flip_html = file_get_contents('./html_components/flip_card_tailwind.html');
    $phone_card_html = file_get_contents('./html_components/phone_card.html');
    $about_html = file_get_contents('./html_components/about.html');
    $footer_html = file_get_contents('./html_components/footer.html');
    $login_dialog_html = file_get_contents('./html_components/dialog_login.html');
    $reg_dialog_html = file_get_contents(
      './html_components/dialog_register.html'
    );

    // inject google api url and control what html to show for logged in and logged out users
    $google_login_button_target = get_google_login_url();
    $github_login_button_target = get_github_auth_url();
    $github_login_button_target =
      $github_login_button_target . '&scope=user:email';

    if (!isset($_SESSION['user_id'])) {
      $login_dialog_html = str_replace(
        ['GOOGLE_API_URL', 'GITHUB_API_URL'],
        [
          "window.location = '" . $google_login_button_target . "';",
          "window.location = '" . $github_login_button_target . "';",
        ],
        $login_dialog_html
      );

      $nav_html = str_replace(
        [
          'for-logged-out nav-link hidden',
          'for-logged-out mb-auto mt-4 hidden',
          'res/user_img/PROFILE_USER_IMG',
        ],
        ['nav-link', 'mb-auto mt-4', './res/pz_logo_1_dark.svg'],
        $nav_html
      );
    } else {
      require_once './scripts/user_functionality.php';
      $nav_html = str_replace(
        ['for-logged-in nav-link hidden', 'for-logged-in nav-link group hidden'],
        ['nav-link', 'nav-link group'],
        $nav_html
      );
      $logged_in_mf = get_user($_SESSION['user_id']);
      $nav_html = str_replace(
        ['PROFILE_USER_IMG'],
        [$logged_in_mf->user_img],
        $nav_html
      );
    }

    $login_dialog_html = str_replace(
      'action="../scripts/login.php"',
      'action="./scripts/login.php"',
      $login_dialog_html
    );
    $reg_dialog_html = str_replace(
      'action="../scripts/admin_functionality.php"',
      'action="./scripts/admin_functionality.php"',
      $reg_dialog_html
    );

    // since we are on index, adjust the top sellers component with appropriate title
    $top_products_html = str_replace(
      ['SECTION_TITLE', ' 2xl:grid-cols-5', ' lg:grid-cols-3'],
      ['Best sellers', '', ''],
      file_get_contents('./html_components/products_grid.html')
    );

    // and also make the promo sticker show up (removing the attribute giving it height of 0 and injecting html into it)
    $phone_card_html = str_replace(
      ['PROMO', 'promo-sticker h-0'],
      [
        '<div class="text-sm text-center absolute w-36 pt-[2px] h-6 dark:bg-brand-primary-600 bg-brand-primary-200 rotate-45 top-0 translate-y-7 translate-x-8 right-0">GREAT DEAL</div>',
        'promo-sticker',
      ],
      $phone_card_html
    );

    // get an array of random phones from db
    $random_products = get_random_products($best_sellers_count_to_display);

    // array to store html snippets per each card
    $top_sellers_grid_content_html = '';

    // adjust html per each phone grabbed from db and construct the html as string
    foreach ($random_products as $product) {
      $next_product_card_html = str_replace(
        ['PRODUCT_ID', 'PRODUCT_NAME', 'PRODUCT_PRICE', 'PRODUCT_IMG_PATH'],
        [
          $product->product_id,
          $product->product_name,
          '£' . $product->product_price,
          $product->product_img_path,
        ],
        $phone_card_html
      );
      $top_sellers_grid_content_html .= $next_product_card_html;
    }

    // insert the cards into grid component
    $top_products_html = str_replace(
      ['PRODUCTS_GRID'],
      [$top_sellers_grid_content_html],
      $top_products_html
    );

    // echo crap onto the page
    echo $cart_sidebar_html;
    echo $login_dialog_html;
    echo $reg_dialog_html;
    echo $nav_html;
    //   echo $cta_html;
    echo $flip_html;
    echo $top_products_html;
    echo $about_html;
    echo $footer_html;
    echo $mobile_toggle;
  ?>

  <script type="module" src="./js/navigation.js"></script>
  <script defer type="module" src="./js/cart.js"></script>
  <script defer src="./js/index.js" type="text/javascript"></script>
</body>

</html>
