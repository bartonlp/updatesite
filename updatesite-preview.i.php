<?php
// This is included by the program instantiating the UpdateSite class via updatesite.class.php

// This is the AJAX PART!!!

if($_GET['ajax']) {
  // The Ajax part does not need the UpdateSite class.
  // This part reads the 'page' file and replaces the '// START UpdateSite ...' marker with an array that fills the $item rather
  // than filling it via the $u->getItem() function.

  session_start(); // Start the session for the ajax part!

  // The function part of this file sets up these session variables.
  
  $title = $_SESSION['title'];
  $bodytext = $_SESSION['bodytext'];
  $itemname = $_SESSION['itemname'];
  $page = $_SESSION['page'];
  
  $title = preg_replace("/'/s", "&apos;", $title);
  $bodytext = preg_replace("/'/s", "&apos;", $bodytext);
  $date = date("Y-m-d H:i:s");

  $item = "array(title=>'$title', bodytext=>'$bodytext', date=>'$date');";

  $data = file_get_contents("$page");
  $data = preg_replace("|// START UpdateSite $itemname.*?// END UpdateSite.*?\n|s", '$item=' . $item, $data);

  // Remove the Admin Notice.
  // Note we have to escape the paren and the $
  $data = preg_replace('/if\(\$S->isAdmin.*?}/s', "", $data, 1);
  
  // This works using an eval!
  // I want the file to actually execute the PHP in it. That is execute it just like if
  // the file was loaded via the server. All of the PHP in the file is executed and the
  // results is output.

  ob_start();
  eval('?>' . $data);
  $data = ob_get_clean();
  
  $data = preg_replace("|<!-- Google Analitics -->.*<!-- END Google Analitics -->|s", "", $data);
  header("Content-type: text/html");
  echo $data;
  exit(); // All DONE EXIT
}

// This file is included in the site specific updatesite2.php (or whatever it is called -- the second part of the site specific
// pair of files).
// This function is then called from updatesite.class.php (UpdateSite class) 'previewpage' function. 'previewpage' check to see
// that the function exists. If it does not exist then a message is presented and the user is given the option to proceed to the
// post page without a preview option.
// If this function does exist it is passed the "$this" of the UpdateSite class, the id of the item, the page name, itemname, and
// the title and bodytext text.
// This function then starts a session and sets the session variables for the AJAX part above.
// It then presents an <iframe> that is filled via the AJAX logic and a form that has a 'create' and 'discard' button.
// The iframe has the full page of the 'page' file with the insert.
// The AJAX ifram is '<iframe id="frame" style="width: 100%; height: 100%;" src="$self?ajax=1"></iframe>'. The Javascript in the
// header handles the Ajax and the buttons.

function updatesite_preview($classthis, $id, $page, $itemname, $title, $bodytext) {
  session_start();
  
  $_SESSION['title'] = $title = stripslashes($title);
  $_SESSION['bodytext'] = $bodytext = stripslashes($bodytext);
  $_SESSION['itemname'] = $itemname;
  $_SESSION['page'] = $page;
  
  $self = $classthis->self; // this self is not this file but the one that called us!

//  $title = str_replace("\\", "", $title);
//  $bodytext = str_replace("\\", "", $bodytext);
  
  $u_title = urlencode($title);
  $u_bodytext = urlencode($bodytext);

  echo <<<EOF
<html>
<head>
  <title>UpdateSite Preview</title>
  <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
  <script type="text/javascript">
jQuery(document).ready(function($) {
  $("#reset").click(function() {
    $("#subButton").hide();
    $("#reset").hide();
    $("iframe").fadeOut(1000, function() {
      // The ajax needs time to do its thing so do a 1 second hide
      // before doing the back
      // window.history.back();
      $("form input[name=page]").val("reedit");
      $("form").submit();
    });
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
<iframe id="frame" style="width: 100%; height: 100%;" src="$self?ajax=1"></iframe>
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
