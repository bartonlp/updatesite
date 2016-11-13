<?php
// This is included by the program instantiating the UpdateSite class via updatesite.class.php
// This file is included in the site specific updatesite2.php (or whatever it is called -- the second part of the site specific
// pair of files).
// This function is then called from updatesite.class.php (UpdateSite class) 'previewpage' function. 'previewpage' check to see
// that the function exists. If it does not exist then a message is presented and the user is given the option to proceed to the
// post page without a preview option.
// If this function does exist it is passed the "$this" of the UpdateSite class, the id of the item, the page name, itemname, and
// the title and bodytext text.
// It presents an <iframe> that is filled via blob-datauri logic and a form that has a 'create' and 'discard' button.
// The form's action is $classthis->self so the form goes back to updatesite.class.php with
// page=Post so updatesite.class.php performs the Post.
// The iframe has the full page of the 'page' file with the insert (that is $item has our new text).
// The iframe is '<iframe id="frame" style="width: 100%; height: 100%;"
// src="a-data-uri"></iframe>'.

function updatesite_preview($classthis, $id, $page, $itemname, $title, $bodytext) {
  $title = stripslashes($title);
  $bodytext = stripslashes($bodytext);
  $self = $classthis->self; // this self is not this file but the one that called us!

  $u_title = urlencode($title);
  $u_bodytext = urlencode($bodytext);

  $title = preg_replace("/'/s", "&apos;", $title);
  $bodytext = preg_replace("/'/s", "&apos;", $bodytext);
  $date = date("Y-m-d H:i:s");
  $item = "array(title=>'$title', bodytext=>'$bodytext', date=>'$date');";
  $data = file_get_contents("$page");

  // Every UpdateSite section in a webpage is delimited with a START/END marker.
  // We remove the START/END section that would normally load the info via $item = $u->getItem($s);
  // which makes $item an array.
  // with a straight load of $item with item array which has title, bodytext and date.
  
  $data = preg_replace("|// START UpdateSite $itemname.*?// END UpdateSite.*?\n|s", '$item=' . $item, $data);

  // Remove the Admin Notice.
  // Note we have to escape the paren and the $
  $data = preg_replace('/if\(\$S->isAdmin.*?}/s', "", $data, 1);

  // This works using an eval!
  // I want the file to actually execute the PHP in it. That is execute it just like if
  // the file was loaded via the server. All of the PHP in the file is executed and the
  // results is output.

  ob_start();
  eval('?>' . $data . '<?');
  $data = ob_get_clean();

  $data = preg_replace("|<!-- Google Analitics -->.*<!-- END Google Analitics -->|s", "", $data);
  $data = "data:text/html;base64," . base64_encode($data);
  
  echo <<<EOF
<html>
<head>
  <meta charset='utf8'/>
  <title>UpdateSite Preview</title>
  <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
  <script type="text/javascript">
jQuery(document).ready(function($) {
  // Now attach the click to the buttons

  $("#reset").click(function() {
//    $("#subButton").hide();
//    $("#reset").hide();
/*
    $("iframe").fadeOut(1000, function() {
      // The ajax needs time to do its thing so do a 1 second hide
      // before doing the back
      // window.history.back();
      $("form input[name=page]").val("reedit");
      $("form").submit();
    });
*/
    $("form input[name=page]").val("reedit");
    $("form").submit();
    return false;
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
</head>

<body>
<h1>This is a test</h1>

<iframe id="frame" style="width: 100%; height: 100%;" src="$data"></iframe>
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
</body>
</html>
EOF;
}
