<?php
// This is included by the program instantiating the UpdateSite class via updatesite.class.php
// This is simple preview without any AJAX. It does not pull in the target page at all.

// This file is included in the site specific updatesite2.php (or whatever it is called -- the second part of the site specific
// pair of files).
// This function is then called from updatesite.class.php (UpdateSite class) 'previewpage' function. 'previewpage' check to see
// that the function exists. If it does not exist then a message is presented and the user is given the option to proceed to the
// post page without a preview option.
// If this function does exist it is passed the "$this" of the UpdateSite class, the id of the item, the page name, itemname, and
// the title and bodytext text.
// This simple version just displayes the title and the bodytext in HTML.

function updatesite_preview($classthis, $id, $page, $itemname, $title, $bodytext) {
  $title = str_replace("\\", "", $title);
  $bodytext = str_replace("\\", "", $bodytext);
  $u_title = urlencode($title);
  $u_bodytext = urlencode($bodytext);
  $self = $classthis->self;
  
  echo <<<EOF
$classthis->top
<h2>Title</h2>
<div style="border: 1px solid black; padding: 5px;">$title</div>
<h2>Body Text</h2>
<div style="border: 1px solid black; padding: 5px;">
$bodytext
</div>
<form action="$self" method="post">
<input type="hidden" name="title" value="$u_title"/>
<input type="hidden" name="bodytext" value="$u_bodytext"/>
<input type="hidden" name="id" value="{$id}" />
<input type="hidden" name="page" value="Post"/>
<input type="hidden" name="pagename" value="$page"/>
<input type="hidden" name="itemname" value="$itemname"/>
<button id="subButton">Create Article</button>
&nbsp;<button id="reset">Discard and return to editor panel</button>
</form>

<script type="text/javascript">
jQuery(document).ready(function($) {
  $("#reset").click(function() {
    //$("#subButton").hide();
    //$("#reset").hide();
    $("form input[name=page]").val("reedit");
    $("form").submit();
  });

  $("#subButton").click(function() {
    $("form").submit();
  });
});
</script>

<style type="text/css">
#subButton {
  font-size: 1.5em;
  background-color: green;
  color: white;
  padding: 20px;
}
#reset {
  font-size: 1.5em;
  background-color: red;
  color: white;
  padding: 20px;
}
</style>

$classthis->footer
EOF;
}
