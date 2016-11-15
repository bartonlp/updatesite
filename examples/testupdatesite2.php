<?php
// You should put 'SetEnv SITELOAD=<path_to_site-class_includes>' in your .htaccess file
$_site = require_once(getenv("SITELOAD"). "/siteload.php");
$S = new $_site->className($_site);

$h->extra = <<<EOF
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.js"></script>
  <script type="text/javascript">
jQuery(document).ready(function() {
  var auto = 1;

  $("#updatesiteform #formtablesubmitth input")
  .after("<input type='button' id='render' style='display: none' value='Quick Preview'/>" +
        "<input type='button' id='autopreview' value='Stop Auto Preview' />");

  $("#updatesiteform").after("<div style='padding: 5px; border: 1px solid black' id='quickpreview'>");
  $("#quickpreview").html("<div style='border: 1px solid red'>TITLE: " + $("#formtitle").val() +
                            "</div>" + $("#formdesc").val());

  $("#autopreview").click(function() {
    if(auto) {
      $(this).val("Start Auto Preview");
      $("#render").show();
      auto = 0;
    } else {
      $(this).val("Stop Auto Preview");
      $("#render").hide();
      $("#render").click();
      auto = 1;
    }
  });

  $("#render").click(function() {
    $("#quickpreview").html("<div style='border: 1px solid red'>TITLE: " + $("#formtitle").val() +
                            "</div>" + $("#formdesc").val());
  });

  $("#formdesc, #formtitle").keyup(function() {
    if(!auto) return false;

    $("#quickpreview").html("<div style='border: 1px solid red'>TITLE: " + $("#formtitle").val() +
                            "</div>" + $("#formdesc").val());
  });
});
  </script>
EOF;
$h->title = "Update Site For Granby Rotary";
$h->title = "Update Site Admin for Granby Rotary";
$h->banner = "<h1>Update Site Admin For Granby Rotary</h1>";

$s->site = "heidi";

UpdateSite::secondHalf($S, $h, $s);

