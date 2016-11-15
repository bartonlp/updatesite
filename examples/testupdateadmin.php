<?php
// You should put 'SetEnv SITELOAD=<path_to_site-class_includes>' in your .htaccess file
$_site = require_once(getenv("SITELOAD"). "/siteload.php");
ErrorClass::setNoEmailErrs(true);
ErrorClass::setDevelopment(true);
$S = new $_site->className($_site);

$h->title = "UpdateSite Admin";
$h->banner = "<h1>UpdateSite Admin</h1>";
$s->head = <<<EOF
<!DOCTYPE html>
<html>
<head>
<!-- TEST -->
</head>
EOF;

if(!$_GET && !$_POST) {
  $_GET['page'] = "admin"; // Force us to the admin page if not get or post
}

$updatepage = UpdateSite::secondHalf($S, $h);

echo <<<EOF
$updatepage
EOF;
