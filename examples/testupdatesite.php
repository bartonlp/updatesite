<?php
// You should put 'SetEnv SITELOAD=<path_to_site-class_includes>' in your .htaccess file
$_site = require_once(getenv("SITELOAD"). "/siteload.php");
ErrorClass::setNoEmailErrs(true);
ErrorClass::setDevelopment(true);
$S = new $_site->className($_site);

// START UpdateSite PresidentMsg "President's Message"
// START UpdateSite International
// START UpdateSite Leadership

$s->siteclass = $S;
$s->page = "testupdatesite.php"; // the name of this page
$s->itemname ="International"; // the item we want to get first

$u = new UpdateSite($s); // Should do this outside of the '// START UpdateSite ...' comments

$item = $u->getItem($s);

if($item !== false) {
  $international = <<<EOF
<div>
<h2>{$item['title']}</h2>
<div>{$item['bodytext']}</div>
<p class="itemdate">Created: {$item['date']}</p>
</div>
<hr/>
EOF;
}

$s->itemname = "PresidentMsg";

$item = $u->getItem($s);

if($item !== false) {
  $presidentmsg = <<<EOF
<div>
<h2>{$item['title']}</h2>
<div>{$item['bodytext']}</div>
<p class="itemdate">Created: {$item['date']}</p>
</div>
<hr/>
EOF;
}

// To get subsequent sections just set the itemname and call getItem with the $s with the new
// itemname set.

$s->itemname ="Leadership";

$item = $u->getItem($s);

if($item !== false) {
  $leadership = <<<EOF
<div>
<h2>{$item['title']}</h2>
<div>{$item['bodytext']}</div>
<p class="itemdate">Created: {$item['date']}</p>
</div>
<hr/>
EOF;
}

$s->itemname ="Otherstuff";

$item = $u->getItem($s);

if($item !== false) {
  $otherstuff = <<<EOF
<div>
<h2>{$item['title']}</h2>
<div>{$item['bodytext']}</div>
<p class="itemdate">Created: {$item['date']}</p>
</div>
<hr/>
EOF;
}

echo <<<EOF
<h1>Test</h1>
$presidentmsg
$international
$leadership
$otherstuff
EOF;
