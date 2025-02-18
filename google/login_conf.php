<?php
// HACK:
// a little bit of code duplication because I just cannot be arsed anymore
// with how unreliable handling the mess that relative paths are where multiple
// scripts require multiple things in multiple places. if something is is not
// directly in the same directory it is MADDENING!
function oauth_ban_google()
{
  $markup_message = "<h3>Access to this resource is not allowed.</h2><br><br>";
  $markup_button =
    "<button onclick=\"window.location.href='../index.php';\">Return to Homepage</button>";
  if (!defined("ALLOW_REQUIRED_SCRIPTS")) {
    $_SERVER["REQUEST_METHOD"] == "POST"
      ? null
      : exit($markup_message . $markup_button);
  }
}
oauth_ban_google();

// using https://github.com/thephpleague/oauth2-github
//abstracted to a function to not expose details
require "vendor/autoload.php";

function get_google_login_url()
{
  $oauth_json_raw = base64_decode(getenv("GOOGLE_CLIENT_JSON"));
  $oauth_obj = json_decode($oauth_json_raw, true);
  $provider = new League\OAuth2\Client\Provider\Google([
    "clientId" => $oauth_obj["web"]["client_id"],
    "clientSecret" => $oauth_obj["web"]["client_secret"],
    "redirectUri" => $oauth_obj["web"]["redirect_uris"][0],
    "accessType" => "online",
    "scopes" => ["email", "profile"],
  ]);
  $url = $provider->getAuthorizationUrl();
  $url = preg_replace("/([&?])state=[^&]*(&|$)/", "&state&", $url);
  $url = str_replace("openid%20", "", $url);
  $url = $url . "&approval_prompt=auto";

  return $url;
}
