<?php
// HACK:
// a little bit of code duplication because I just cannot be arsed anymore
// with how unreliable handling the mess that relative paths are where multiple
// scripts require multiple things in multiple places. if something is is not
// directly in the same directory it is MADDENING!
function oauth_ban_github()
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
oauth_ban_github();

// using https://github.com/thephpleague/oauth2-github
//abstracted to a function to not expose details
require "vendor/autoload.php";

function get_github_auth_url()
{
  $oauth_json_raw = base64_decode(getenv("GITHUB_CLIENT_JSON"));
  $oauth_obj = json_decode($oauth_json_raw, true);
  $provider = new League\OAuth2\Client\Provider\Github([
    "clientId" => $oauth_obj["web"]["client_id"],
    "clientSecret" => $oauth_obj["web"]["client_secret"],
    "redirectUri" => "http://localhost/phonezone/scripts/oauth_handler.php",
  ]);
  // If we don't have an authorization code then get one
  return $provider->getAuthorizationUrl();
}
