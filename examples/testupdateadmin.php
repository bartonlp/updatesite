<?php
// You should put 'SetEnv SITELOAD=<path_to_site-class_includes>' in your .htaccess file
require_once("/var/www/UpdateSite-class/UpdateSite.class.php");
$_site = require_once(getenv("SITELOAD"). "/siteload.php");
$S = new $_site->className($_site);

$h->title = "Update Site Admin for Granby Rotary";
$h->banner = "<h1>Update Site Admin For Granby Rotary</h1>";
$s->head = <<<EOF
<!DOCTYPE html>
<html>
<head>
<!-- TEST -->
</head>
EOF;

$h->nobanner = true;
$h->nofooter = true;

$s->site = "granbyrotary.org";

if(!$_GET && !$_POST) {
  $_GET['page'] = "admin"; // Force us to the admin page if not get or post
}

$updatepage = UpdateSite::secondHalf($S, $h, $s);

echo <<<EOF
$updatepage
EOF;
