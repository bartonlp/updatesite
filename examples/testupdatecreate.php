<?php
// You should put 'SetEnv SITELOAD=<path_to_site-class_includes>' in your .htaccess file
$_site = require_once(getenv("SITELOAD"). "/siteload.php");
$S = new $_site->className($_site);

// Get site info

$h->title = "Update Site For Granby Rotary";
$h->banner = "<h1>Update Site For Granby Rotary</h1>";

// UpdateSite::firstHalf() is a static member.
// UpdateSite::firstHalf($S, $h, [$nextfilename]);
// The third parameter is optional.
// $nextfilename can be set if we want a file other than the default which is "/updatesite2.php".

$page = UpdateSite::firstHalf($S, $h, 'testupdatesite2.php');

echo <<<EOF
$page
<br>
<a href="testupdateadmin.php">Administer Update Site Table</a><br/>
$footer
EOF;
