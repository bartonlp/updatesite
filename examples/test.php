<?php
// You should put 'SetEnv SITELOAD=<path_to_site-class_includes>' in your .htaccess file
$_site = require_once(getenv("SITELOAD"). "/siteload.php");
$S = new $_site->className($_site);

// START UpdateSite Message

$s->siteclass = $S;
$s->site = "heidi";
$s->page = "test.php";
$s->itemname ="Message";

$u = new UpdateSite($s);

$item = $u->getItem();

// If item is false then no item in table

if($item !== false) {
  $message = <<<EOF
<div>
<h2>{$item['title']}</h2>
<div>{$item['bodytext']}</div>
<p class="itemdate">Created: {$item['date']}</p>
</div>
<hr/>
EOF;
}

list($top, $footer) = $S->getPageTopBottom();

echo <<<EOF
$top
$message
$footer
EOF;

