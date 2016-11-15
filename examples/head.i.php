<?php
return <<<EOF
<head>
  <title>{$arg['title']}</title>
  <!-- METAs -->
  <meta charset='utf-8'/>
  <meta name="copyright" content="$this->copyright">
  <meta name="Author"
     content="Barton L. Phillips, mailto:barton@bartonphillips.org"/>
  <meta name="description"
     content="{$arg['desc']}"/>
  <meta name=viewport content="width=device-width, initial-scale=1">
  {$arg['link']}
  <!-- jQuery -->
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
  <!-- Custom Scripts -->
{$arg['extra']}
{$arg['script']}
{$arg['css']}
</head>
EOF;
