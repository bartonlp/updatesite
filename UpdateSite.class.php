<?php
// This class maintains the site table which has the information for sections of pages for the
// sites.
// This table is be one per site and kept in the sites database!
/*
create table site (
  id int(11) auto_increment not null,
  page varchar(255) not null,     # the page like index.php
  itemname varchar(255) not null, # the name of the item
  title varchar(255),             # the title to apply
  bodytext text,                  # the message. Some html is allowed
  date datetime,                  # the date the record was created or updated
  status enum('active','inactive','delete') default 'active',
  lasttime timestamp,             # timestamp used to select most recent item. May be different form data if item was edited.
  creator varchar(255),           # the creator's id
  primary key(id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
*/
// IMPORTANT NOTE:
// The file "/home/bartonlp/includes/updatesite-preview.i.php" must be included in the file that
// instantiates UpdateSite!
// This file has the function "updatesite-preview.php" as well as the Ajax call back to self.
// If this file is NOT included then the preview option is not available -- see previewpage().

class UpdateSite {
  private $page;      // the page file like index.php etc
  private $itemname;  // item on a page
  private $siteclass; // SiteClass object ($S=new SiteClass(...)) usually $S
  public $top;       // the page top (<head> and banner which includes <body>)
  public $footer;    // the page footer which includes </body></html> at a minimum

  /**
   * Constructor
   *
   * @param object $s
   *   $s is an object: $s->siteclass, $s->page, $s->itemname
   *   these are the private properties.
   *   $s->top, $s->footer are public.
   *   Any additional properties of $s become class publics.
   */
  
  public function __construct($s=null) {
    // NOTE: This is where $top and $footer are filled in as well as all of the private properties.

    if($s) {
      foreach($s as $k=>$v) {
        $this->$k = $v; // any additional args become public
      }
    }
    $this->self = $_SERVER['PHP_SELF'];
  }

  /**
   * Update Switch
   * Switch for full program control usually called after UpdateSite::secondHalf() instantiates
   * the class.
   * This method is usually called from something like the site specific updatesite2.php
   * (or whatever you called it) via UpdateSite::secondHalf().
   * This could be called directly from a site specific page by doing the following:
   * The updatesite2.php should set the $s object argument to "new SiteUpdate($s)"
   * with the appropriate properties.
   * Then updateSwitch() is called. As $_POST or $_GET changes due to form POSTs or <a..>
   * GETs the various methods are called.
   * When POSTs are used the updatesite2.php will reset the $s object from the $_POST
   * but GET's must do the work HERE!
   * (you could of course have the updatesite2.php handle GET's Also I guess but I have not opted to do that!)
   *
   * @example
   * $s->siteclass = $S; // the site class object
   * $s->site = "the site domain name"; // usually explicitly set like "granbyrotary.org" for example.
   * $s->page = $_POST['pagename']; // via other methods or a startup page like updatesite.php
   * $s->itemname = $_POST['itemname']; // as above for page.
   * $u = new UpdateSite($s);
   * $u->updateSwitch(); // do start and then follow $_POST/$_GET
   * After the switch call you can have additional stuff at the bottom of the page,
   * even additional switches.
   */
  
  public function updateSwitch() {
    // NOTE: we get here via the site specific updatesite2.php which sets the
    // $s object with the siteclass, site, page, and
    // itemname from the $_POST, $S, and explicitly for site.
    // REQUEST_METHOD check wether a POST, GET etc.

    switch(strtoupper($_SERVER['REQUEST_METHOD'])) {
      case "POST":
        switch(strtolower($_POST['page'])) {
          case "adminpost":
            $this->adminpost();
            break;
          case "preview":
            $this->previewpage();
            break;
          case "post":
            $this->postpage();
            break;
          case "show":
            $this->showpage();
            break;
          case "list":
            $this->listitemspage();
            break;
          case "reedit":
            $title = urldecode($_POST['title']);
            $bodytext = urldecode($_POST['bodytext']);
            $values = array(title=>$title, bodytext=>$bodytext);
                
            $this->startpage(null, $values);
            break;
          default:
            // Main page
            $values = $this->getItem();
            $this->startpage(null, $values);
            break;
        }
        break;
      case "GET":
        switch(strtolower($_GET['page'])) {
          // NOTE: GETs do NOT update via the site specific updatesite2.php so the $s object
          // must be set explicitly HERE if needed.
          case "admin":
            $this->admin();
            break;
          case "admindelete":
            $this->admindelete();
            break;
          case "post":
            // These would normally be picked up in the site specific updatesite2.php but here
            // we are simulating a POST via a GET so we need to update page and itemname here!
            
            $this->page = $_GET['pagename'];
            $this->itemname = $_GET['itemname'];
            
            unset($_GET['post'], $_GET['pagename'], $_GET['itemname']); // Take these out of $_GET

            // move the rest of the $_GET to $_POST
            // This should be id, title, bodytext

            foreach($_GET as $k=>$v) {
              $_POST[$k] = $v;
            }

            $this->postpage();
            break;
          case "show":
            // showpages uses getItem(/*no arg*/) to access the site table.
            // Therefore a GET must provide:
            // pagename, and itemname (site is usually set by the file that instantiates UpdateSite.)
            // The GET parameters should be 'pagename' and 'itemname' as in
            // '<a href="xx?page=show&pagename=somepage&itemname=someitem" ...'

            $this->page = $_GET['pagename'];
            $this->itemname = $_GET['itemname'];
            
            $this->showpage();
            break;
          case "list":
            // like show above we need to have 'pagename' and 'itemname' in $_GET

            $this->page = $_GET['pagename'];
            $this->itemname = $_GET['itemname'];
            $this->listitemspage();
            break;
          case "status":
            // status GET need 'id' and 'status'
            
            $id = $_GET['id'];
            $status = $_GET['status'];
            $this->statuspage($id, $status);
            break;
          case "edit":
            $id = $_GET['id'];
            $values = $this->getItemById($id);
            $this->startpage($id, $values);
            break;
          case "start":
            // START need nothing. It checks to see if there is a getItem() but
            // if not then we get a blank form.
            
            $values = $this->getItem(); // get the current item if any.
            $this->startpage(null, $values);
            break;
          default:
            throw(new Exception("GET but no 'page'"));
        }
        break;
      case "HEAD":
      case "PUT":
        throw(new Exception("HEAD or PUT not allowed"));
        
      default:
        // NOT POST OR GET etc so this is the main page

        $values = $this->getItem(); // get the current item if any.
        $this->startpage(null, $values);
        break;
    }
  }

  // First and Second Half static functions.
  
  /**
   * firstHalf
   * Called by the site specific updatesite.php the first of two site specific pages.
   * firstHalf() creates a form with a form that has a select tag for the various pages that
   * have update sections and a select what has the itemnames of the update sections on the
   * pages. Once the form is submitted the next page, usually the site specific updatesite2.php,
   * is started. That page usually sets up things and calls secondHalf().
   * @static
   *
   * @param $S siteclass object
   * @param $h object passed to getPageTop() it already has title and banner filled in.
   * @param $nextfilename the name of the updatesite2.php file (if not /updatesite2.php).
   * @return string with the full select page
   */
  
  public static function firstHalf($S, $h, $nextfilename=null) {
    // Include this in your sites updatesite.php file (or whatever you call it) which is usually
    // only available to site administrators.
    // There are usually two files:
    // The first (updatesite.php or whatever you call it) gets the site information about which
    // files have insert areas marked:
    // '// START UpdateSite itemname "human readabel text optional"'
    // From that information we make a form with two select feilds for "page" and "itemname".
    // All of that is done here in firstHald().
    // $nextfilename is the name of the file in the '<form...' (defaults to "/updatesite2.php")!
    // updatesite2.php (or whatever you call it) is the 'action' part of the '<form...' and it uses
    // secondHalf() to create or edit the item information. SecondHalf() finally uses the preview
    // file (updatesite-preview.php) to show what the page will looklike with the updates. Then
    // the update is posted to the database.

    if(empty($nextfilename)) {
      $nextfilename = "/updatesite2.php";
    }

    // $options is an array of filenames and select options
    
    $options = UpdateSite::findInserts(); // Scan the site's directory for files with UpdateSite inserts.

    $pagenames = "";
    $cases = "";

    // Create the case statement and pagenames options
    // $pagenames are option tags for a html select
    // $cases are JavaScript statements with jQuery elements
    
    foreach($options as $k=>$v) {
      $pagenames .= "<option>$k</option>"; // filename
      $cases .= " case '$k': $('#itemnameselect select').html('$v'); break;";
    }

    // Get the first file in the list and its select itemname options.

    $x = array_values($options);
    $firstitem = $x[0];

    // The script stuff is the same for all. The $h->title and $h->banner are unique to each site and are filled in by the
    // site specific Select file.

    // the script changes the itemname select if the pagename select changes
    
    $h->extra .= <<<EOF
  <script type="text/javascript">
jQuery(document).ready(function($) {
  $("#pageselect select").change(function() {
    var page = $(this).val();
    switch(page) {
$cases
    }
  });
});
  </script>
EOF;
    if(!$h->head) {
      $top = $S->getPageHead($h);
    } else {
      $top = preg_replace("~</head>~", $h->extra . "</head>", $h->head);
    }
    
    if(!$h->nobanner) {
      $top .= $S->getBanner($h->banner, $h->nonav, $h->bodytag);
    } else {
      $top .= "<body>";
    }

    if(!$h->nofooter) {
      if($h->footer) {
        $footer = $S->getFooter($h->footer);
      } else {
        $footer = $S->getFooter("<hr/>");
      }
    }

    return <<<EOF
$top
<div id="firsthalf-wrapper">
<h2>Select Site Information</h2>
<form action="$nextfilename" method="post">
<table id="firsthalf-tbl>
<tr><th>Select Page</th><td id="pageselect">
<select name="pagename">
$pagenames
</select>
</td></tr>
<tr><th>Item Name</th><td id="itemnameselect">
<select name="itemname">
$firstitem
</select>
</td></tr>
</table>
<input type="submit" value="Continue" />
</div>
</form>
$footer
EOF;
  }

  // Function to scan directory for UpdateSite inserts in files.
  // Helper for above function firstHalf()
  // returns an array of filenames => <option item 
  
  private static function findInserts() {
    // look through all php files in Document Root for site
    
    $info = `grep '^// START UpdateSite ' *.php`; // actually run this command
    $info = explode("\n", $info); // an array of grep results

    $ar = array();

    foreach($info as $k=>$v) {
      // each grep line looks like <filename>:// START UpdateSite <itemname> <human readable string>
      // We want the filename and the itemname and the human readable string
      
      if(preg_match("~^(.*?):// START UpdateSite (\w+?)(?:$| (.*)$)~", $v, $m)) {
      
        if(empty($m[3])) {
          $m[3] = $m[2]; // if no string then use the itemname
        }
        $m[3] = trim($m[3], '"'); // get rid of quotes
        $m[3] = preg_replace("/'/s", "&apos;", $m[3]); // make ' into &apos;
        $ar[$m[1]] .= "<option value=\"$m[2]\">$m[3]</option>";
      }
    }

    return $ar;
  }

  /*
   * secondHalf
   * called from updatesite2.php
   * This static function instantiates the UpdateSite class and runs the updateSwitch() to do the
   * creation or editing of the UpdateSite area selected via firstHalf(), and then preview and post
   * the information to the database.
   * @static
   * This is STATIC so we call it UpdateSite::secondHalf(...)
   * We need to have this static because the secondHalf() actually instantiates the UpdateSite class
   * here!
   * @param $S siteclass object
   * @param $h object passed to getPageTop() it already has title and banner filled in.
   * @param $s object to pass to UpdateSite() class. The site property is already set
   * @param $startfilename the name of the updatesite.php file.
   * @return string the pages of the updateSwitch()
   */
   
  public static function secondHalf($S, $h, $s, $startfilename=null) {
    // Second half of SiteUpdate logic. There are two file: updatesite.php and updatesite2.php. 
    // There should be a site specific updatesite.php and updatesite2.php file
    // that sets up the site-class, the $h->title and $h->banner as well as
    // the $s items $s->siteclass, $s->site, $s->page, and $s->itemname.
    // $startfilename is the name of the first half of this pair (defaults to '/updatesite.php'.

    if(empty($startfilename)) {
      $startfilename = "/updatesite.php";
    }

    if(!$s->head) {
      $top = $S->getPageHead($h);
    } else {
      $top = $s->head;
    }
    
    if(!$h->nobanner) {
      $top .= "\n" . $S->getBanner($h->banner, $h->nonav, $h->bodytag);
    } else {
      $top .= "\n<body>";
    }

    if(!$h->nofooter) {
      if($h->footer) {
        $S->footer = $S->getFooter($h->footer);
      } else {
        $S->footer = $S->getFooter("<a href='$startfilename'>Back To Start</a><hr/>");
      }
    }
    
    // $s->siteclass is filled in by the caller!

    $s->siteclass = $S;

    // By adding $top and $S->footer to $s the constructor fills in $this->top and $this->footer
    
    $s->top = $top; // The rest of $s is filled in by site specific part.

    if(!$h->nofooter) {
      $s->footer = $S->footer;
    }
    
    switch(strtoupper($_SERVER['REQUEST_METHOD'])) {
      case "POST":
        $s->page = $_POST['pagename'];
        $s->itemname = $_POST['itemname'];
        break;
      case "GET":
        $s->page = $_GET['pagename'];
        $s->itemname = $_GET['itemname'];
        break;
      default:
        throw(new Exception(" secondHalf not GET or POST"));
    }

    // Instantiate the class.
    // Use the switching logic to control the flow which is now all internal to this class.
    
    $u = new UpdateSite($s);
    $u->updateSwitch(); // Switch based on $_GET and $_POST
  }
  
  /**
   * getForm
   * Get the Start page form
   * This could be private as as of 10/14/2012 it is  not used outside of the updateSwitch()
   * logic.
   * @param object $styles has the styles for the various parts of the form/table
   * @param object $values default null. If we want to edit an existing item $values have the title and bodytext
   * @return string form text
   */
  
  public function getForm($styles, $id=null, $values=null) {
    $ret = <<<EOF
<form id="updatesiteform" action="$this->self" method="post">
<p>Not all update sections display the title!</p>
<p>Some <i>HTML tags</i> are allowed.</p>
<table id="formtable" $styles->table>
<tr><th $styles->labels>Title</th>
<td $styles->body><input id="formtitle" $styles->input type="text" name="title" value="{$values['title']}" /></td>
</tr>
<tr><th $styles->labels>Body&nbsp;Text</th><td $styles->body>
<textarea id="formdesc" $styles->textarea name="bodytext" />{$values['bodytext']}</textarea>
</td></tr>
<tr><th id="formtablesubmitth" colspan="2"><input type="submit" name="page" value="Preview" /></th></tr>
</table>
<input type="hidden" name="id" value="$id" />
<input type="hidden" name="pagename" value="$this->page"/>
<input type="hidden" name="itemname" value="$this->itemname"/>
</form>
EOF;
    return $ret;
  }

  /**
   * getItems
   * retrieves all of the items
   * Currently not used outside of this class (10/12/2012) so could be private
   * @return array $rows all of the rows from the query
   */
  
  public function getItems($type=null) {
    if($type) {
      $itemname = "";
      if($this->itemname) {
        $itemname = " and itemname='$this->itemname'";
      }
    }

    $query = "select * from site where page='$this->page' $itemname";

    $n = $this->siteclass->query($query);
    if(!$n) return false;
    $rows = array();

    while($row = $this->siteclass->fetchrow()) {
      if(!$row) return false;
      
      array_push($rows, $row);
    }
    return $rows;
  }

  /**
   * setStatus
   * Currently only used in this class so could be private (10/14/2012)
   * @param int $id
   * @param string $status
   */
  
  public function setStatus($id, $status) {
    $query = "update site set status='$status' where id='$id'";
    $this->siteclass->query($query);
  }

  /**
   * getStatus
   * Currently not used anywhere (10/14/2012)
   * @param int $id
   * @return int status or false
   */
  
  public function getStatus($id) {
    $query = "select status from site where id='$id'";
    $n = $this->siteclass->query($query);
    if(!$n) return false;
    list($status) = $this->siteclass->fetchrow();
    if(!$status) return false;
    
    return $status;
  }

  /**
   * getItemById
   * This is used by the internal switch logic and could be private as currently it is not
   * called from outside this class (but I guess it could be)
   * @param int $id
   * @return array($title, $bodytext, $status, $date, title=>$title, bodytext->$bodytext, status=>$status, date=>$date
   *  or false
   */
  
  public function getItemById($id) {
    $query = "select * from site where id='$id'";

    $n = $this->siteclass->query($query);
    if(!$n) return false;

    $row = $this->siteclass->fetchrow();
    if(!$row) return false;
    
    extract($row);

    return array($title, $bodytext, $status, $date, title=>$title, bodytext=>$bodytext, status=>$status, date=>$date);
  }

  /**
   * getItem
   * Get the most recent item for the constructor params
   * This is REALLY public because it is used by site pages like index.php etc after instantiating this
   * class.
   *
   * @return array($title, $bodytext, $status, $date, $id, title=>$title, bodytext->$bodytext, status=>$status, date=>$date, id=>$id
   *  or false
   */
  
  public function getItem($s=null) {
    if($s) {
      $page = $s->page;
      $item = $s->itemname;
    } else {
      $page = $this->page;
      $item = $this->itemname;
    }
    
    $itemname = "";
    if($item) {
      $itemname = " and itemname='$item'";
    }

    $database = $this->siteclass->getDbName(); 

    if($this->siteclass->noTrack === true) {
      $ok = true;
    } else {
      $this->siteclass->query("select count(*) from information_schema.tables ".
                              "where (table_schema = '$database') and (table_name = 'site')");

      list($ok) = $this->siteclass->fetchrow('num');
    }
    
    if(!$ok) {
      error_log("$this->siteclass->siteName: Did not find 'site'");
      return false;
    }

    $query = "select * from site where page='$page' $itemname and ".
             "date=(select max(date) from site where status='active' and page='$page' $itemname)";

    try {
      $n = $this->siteclass->query($query);
      if(!$n) return false; // No item found!
    } catch(Exception $e) {
      if($e->getCode() == 1146) { // table does not exist
        // Create table
        $query =<<<EOF
create table site (
  id int(11) auto_increment not null,
  page varchar(255) not null,     # the page like index.php
  itemname varchar(255) not null, # the name of the item
  title varchar(255),             # the title to apply
  bodytext text,                  # the message. Some html is allowed
  date datetime,                  # the date the record was created or updated
  status enum('active','inactive','delete') default 'active',
  lasttime timestamp,             # timestamp used to select most recent item. May be different form data if item was edited.
  creator varchar(255),           # the creator's id
  primary key(id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8
EOF;
        
        // SqlException sends us an email with the error info!!!!
        return false; // No item found, because the site table does not exist!
      }
      throw($e);
    }
    
    $row = $this->siteclass->fetchrow();
    if(!$row) return false;
    
    extract($row);

    return array($title, $bodytext, $status, $date, $id, 'title'=>$title, 'bodytext'=>$bodytext, 'status'=>$status, 'date'=>$date, 'id'=>$id);
  }

  /**
   * makeTemplate
   * Given a template with two place holders <::1::> and <::2::> return the filled in template.
   * @param string $template
   * @param string $title default null
   * @param string $bodytext default null
   * @return string filled in template.
   * if the $title and $bodytext are null then the most recent item based on the constructor parameters is used.
   *
   * Currently (10/14/2012) I don't seem to use this anywhere?
   */
  
  public function makeTemplate($template, $title=null, $bodytext=null) {
    // template has two replace items as <::1::> and <::2::>
    if(empty($title) && empty($bodytext)) {
      list($title, $bodytext) = $this->getItem();
    } else {
      // Sanitize the form input. We allow most html tags but not script, iframe, object,
      // or embed.
      // Remove the slashes around quotes ("') and turn & and " into entities.
  
      $badtags = array("~<(/?script.*?)>~is", "~<(/?iframe.*?)>~is",
                       "~<(/?object.*?)>~is", "~<(/?embed.*?)>~is",
                       "~<(/?input.*?)>~is", "~<(/?textarea.*?)>~is",
                       "~<(/?link.*?)>~is", "~<(/?meta.*?)>~si");

      foreach(array(title=>$title, bodytext=>$bodytext) as $k=>$v) {
        $v = stripslashes($v);
        //$v = preg_replace(array("/&/", '/"/'), array("&amp;", "&quot;"), $v);
        $v = preg_replace($badtags, "&lt;$1&gt;", $v);
        $$k = $v; // make the variables like extract() does.
      }
    }
    
    $template = preg_replace(array("/<::1::>/", "/<::2::>/"), array($title, $bodytext), $template);
    return $template;
  }

  /**
   * postForm
   * Currently (10/14/2012) this is only used by this class so could be private. Used by postpage().
   * Posts the title and body text to the site table.
   * @param string $title
   * @param string $bodytext
   * On error throws and exception.
   */
  
  public function postForm($title, $bodytext, $id=null) {
    // Sanitize the form input. We allow most html tags but not script, iframe, object, or embed.
    // Remove the slashes around quotes ("') and turn & and " into entities.
  
    $badtags = array("~<(/?script.*?)>~is", "~<(/?iframe.*?)>~is",
                     "~<(/?object.*?)>~is", "~<(/?embed.*?)>~is",
                     "~<(/?input.*?)>~is", "~<(/?textarea.*?)>~is",
                     "~<(/?link.*?)>~is", "~<(/?meta.*?)>~si");

    foreach(array(title=>$title, bodytext=>$bodytext) as $k=>$v) {
      $v = urldecode($v);
      $v = stripslashes($v);
      //$v = preg_replace(array("/&/", '/"/'), array("&amp;", "&quot;"), $v);
      $v = preg_replace($badtags, "&lt;$1&gt;", $v);
      $v = $this->siteclass->escape($v);
      $$k = $v; // make the variables like extract() does.
    }

    if(method_exists($this->siteclass, 'getId')) {
      $memberid = $this->siteclass->getId(); // Get the id of the member who posted this.
    }
    
    $date = date("Y-m-d H:i:s");
    
    try {
      if($id) {
        $query = "update site set title='$title', bodytext='$bodytext', status='active', date=$date where id='$id'";
      } else {
        $query = "insert into site (page, itemname, title, bodytext, date, creator) " .
                 "values('$this->page', '$this->itemname', '$title', '$bodytext', '$date', '$memberid')";
      }

      $this->siteclass->query($query);
    } catch(Exception $e) {
      $err = $e->getCode();
          
      if($err == 1146) {
        // If table does not exist create it
        // CREATE MINIMUM 'site' table. 
        $query2 = <<<EOF
create table site (
  id int(11) auto_increment not null,
  page varchar(255) not null,     # the page like index.php
  itemname varchar(255) not null, # the name of the item
  title varchar(255),             # the title to apply
  bodytext text,                  # the message. Some html is allowed
  date datetime,                  # the date the record was created
  status enum('active', 'inactive', 'delete') default 'active',
  lasttime timestamp,
  creator varchar(255),           # the creator's id or the IP Address
  primary key(id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
EOF;
        $this->siteclass->query($query2);
        $this->siteclass->query($query);
      }
    }
    if(!$id) {
      $id = $this->siteclass->getLastInsertId();
    }

    // Now make all other items inactive
    $itemname = "";
    if($this->itemname) {
      $itemname = " and itemname='$this->itemname'";
    }

    $this->siteclass->query("update site set status='inactive' where " .
                 "page='$this->page' $itemname and id!='$id'");
  }

  // ********************************************************************************
  // Driven by POST and GET via the internal switch logic which is started in seconfHalf()
  // which is started by the updatesite2.php usually.
  // All of the following functions (methods) could probably be private as to date (10/12/2012)
  // they are only used by the updateSwitch() logic. One could probably craft special site pages
  // that might be able to make use of these methods?

  /**
   * startpage
   * Initial Page that gets the Form filled out.
   * This could also be called to edit an existing item. In such a case the form would have
   * the 'value's filled in.
   * @param int $id default null
   * @param object $values default null
   * @param object $s default null, style see $s in body for properties
   */

  public function startpage($id=null, $values=null, $s=null) {
    if(!$s) {
      $s->table = "style='width: 100%; border: 1px solid black;'";
      $s->input = "style='width: 98%; border: none;'";
      $s->textarea =  "style='width: 98%; height: 200px; border: none;'";
      $s->labels = "style='width: 1%; border: 1px solid black;'";
      $s->body = "style='border: 1px solid black;'";
    }
    $form = $this->getForm($s, $id, $values);

    echo <<<EOF
$this->top
<div id="startpage-wrapper">
$form
<br>
<a href="$this->self?page=list&type=edit&pagename={$this->page}&itemname={$this->itemname}">
  List All Items and Select Item to Edit</a>
</div>
$this->footer
EOF;
  }

  /**
   * previewpage
   * The second page. Displays a preview of the item.
   */

  public function previewpage() {
    $title = $_POST['title'];
    $bodytext = $_POST['bodytext'];
    $id = $_POST['id'];
    $self = $this->siteclass->self;
    $itemname = $this->itemname;
    $page = $this->page;
    
    if(function_exists(updatesite_preview)) {
      updatesite_preview($this, $id, $page, $itemname, $title, $bodytext);
    } else {
      $title = urlencode($title);
      $bodytext = urlencode($bodytext);
      echo <<<EOF
$this->top
<div id="previewpage-wrapper">
<h2>Preview Not Available</h2>
<p>Do you want to
<a href="$self?page=post&id=$id&pagename=$page&itemname=$itemname&title=$title&bodytext=$bodytext">Post</a>
anyway?</p>
</div>
$this->footer
EOF;
    }
  }
  
  /**
   * postpage
   * The third page. Post the item to the database.
   */
   
  public function postpage() {
    $this->postForm($_POST['title'], $_POST['bodytext'], $_POST['id']);
    $self = $this->siteclass->self;
    echo <<<EOF
$this->top
<div id="postpage-wrapper">
<h1>Posted</h1>
<a href="$self?page=show&pagename=$this->page&itemname=$this->itemname">Show Results</a>
</div>
$this->footer
EOF;
  }

  /**
   * showpage
   * The fourth page. Show the item from the site table.
   * The following items need to be set either by going through the site specific
   * updatesite2.php for a POST or via moving GET parameter to $this items site, page, itemname.
   * OR if showpage() is called directly from a program after instantiating UpdateSite!
   */

  public function showpage() {
    $page = $this->getItem();
    extract($page);
  
    if($status == "inactive") {
      $status = "<h1 style='color: red'>Inactive</h1>";
    } else {
      $status = "<h1 style='color: green'>Active</h1>";
    }
    echo <<<EOF
$this->top
<div id="showpage-wrapper">
$status
<table id="showpage-tbl" border="1">
<tr><th>Title</th><td style="padding: 5px;">$title</td></tr>
<tr><th>Body&nbsp;Text</th><td style="padding: 5px;">$bodytext</td></tr>
</table>
</div>
$this->footer
EOF;
  }

  /**
   * statuspage
   * Utility page, given the ID and Status toggles status from active to inactive and visa versa
   * @example:
   * <a href="{$_SERVER['PHP_SELF']}?page=status" ...
   * NOTE: this is safe to call directly, via GET or POST through the site specific updatesite2.php 
   */
  
  public function statuspage($id, $status) {
    $self = $this->siteclass->self;
    $status = $status == 'active' ? 'inactive' : 'active';
    $this->setStatus($id, $status);
    header("location: $self?page=start");  
  }

  /**
   * listitemspage
   * Utility page that show a table with all the items from getItems(). The ID field has a link to 'statuspage' above
   * @example:
   * <a href="{$_SERVER['PHP_SELF']}?page=list&type=edit|status&pagename=pagename&itemname=itemname" ...
   */
  
  public function listitemspage() {
    switch($_GET['type']) {
      case "edit":
        $type = "edit";
        $maintitle = "<h2>Edit An Existing Item</h2>";
        break;
      case "status":
        $type = "status";
        $maintitle = "<h2>Toggle Status</h2>";
        break;
      default:
        throw(new Exception(" listitemspage(): type not valid (type={$_GET['type']})"));
    }
    
    $rows = $this->getItems();
    $tbl = "<table id='listitemspage-tbl' border='1'>\n";
    $self = $this->siteclass->self;

    // NOTE: if type=status then in the <a below the page= becomes status.
    
    foreach($rows as $row) {
      extract($row);
      $tbl .= "<tr><td><a href='$self?page=$type&id=$id&status=$status&pagename=$this->page&itemname=$this->itemname'>$id</a></td><td>$title</td><td>$bodytext</td><td>$status</td></tr>\n";
    }
    $tbl .= "</table>\n";
    echo <<<EOF
$this->top
<div id="listitemspage-wrapper">
$maintitle
$tbl
</div>
$this->footer
EOF;
  }

  // Admin stuff. This is usually started via a site admin page like the granbyrotary.org
  // updatesiteadmin.php page which calls secondHalf().
  // The $_GET['page'] is set to 'admin' and we start here.
  
  /**
   * admin
   * Administer the site table. Set the status and delete
   */
  
  public function admin() {
    $n = $this->siteclass->query("select * from site");
    if(!$n) {
      echo "NO Records Fount";
      exit();
    }
    while($row = $this->siteclass->fetchrow('assoc')) {
      if(!$row) {
        echo "NO Records Fount";
        exit();
      }
        
      extract($row);

      $tbl .= <<<EOF
<tr><td>$status</td>
<td>
A<input type="checkbox" name="active[]" value="$id" />
I<input type="checkbox" name="inactive[]" value="$id"/>
D<input type="checkbox" name="delete[]" value="$id"/>
</td>
<td>$page</td><td>$itemname</td><td>$title</td><td>$date</td>
</tr>

EOF;
    }

    echo <<<EOF
$this->top
<div id="admin-wrapper">
<form action="$this->self" method="post">
<p>Action: make <b>A</b>tive, <b>I</b>nactive, <b>D</b>eleted.</p>
<table id="admin-tbl" border="1">
<thead>
<tr><th>Status</th><th>Action</th><th>Page</th><th>Section</th><th>Title</th><th>Date</th></tr>
</thead>
<tbody>
$tbl
</tbody>
</table>
<input type="hidden" name="page" value="adminpost"/>
<input type="submit" value="Submit"/>
</form>
<p>Items marked as <b>delete</b> have been marked but not yet removed.
To remove those items click <a href="$this->self?page=admindelete">Expunge Items Marked <i>delete</i></a>.</p>
<script type="text/javascript">
jQuery(document).ready(function($) {
  // I only want one checkbox active. So when I click on a checkbox I uncheck all of the checkboxes
  // in the td cell where the checkboxes live and then set the property of the checkbox I
  // just clicked on to true.
  $("input:checkbox").click(function() {
    $("input", $(this).parent()).prop("checked", false); // remove all of the checks for the parent
    $(this).prop("checked", true); // add this check back
  });
});
</script>
</div>
$this->footer
EOF;
  }

  /**
   * adminpost
   * Second half of admin where we post the results
   */
  
  public function adminpost() {
    extract($_POST);

    if($active) {
      foreach($active as $v) {
        $a .= "$v,";
      }
      $a = rtrim($a, ",");
      $this->siteclass->query("update site set status='active' where id in ($a)");
    }
    
    if($inactive) {
      foreach($inactive as $v) {
        $i .= "$v,";
      }
      $i = rtrim($i, ",");
      $this->siteclass->query("update site set status='inactive' where id in ($i)");
    }
    
    if($delete) {
      foreach($delete as $v) {
        $d .= "$v,";
      }
      $d = rtrim($d, ",");
      $this->siteclass->query("update site set status='delete' where id in ($d)");
    }

    $this->footer = "<a href='$this->self?page=admin'>Return to Admin</a>$this->footer";
    
    echo <<<EOF
$this->top
<div id="adminpost-wrapper">
<p>The selected items have been updated. Items marked as <b>delete</b> have been marked but not yet removed.
To remove those items click <a href="$this->self?page=admindelete">Expunge Items Marked <i>delete</i></a>.</p>
</div>
$this->footer
EOF;
  }

  /**
   * admindelete
   * Delete Marked items
   */

  public function admindelete() {
    $this->siteclass->query("delete from site where status='delete'");
    echo <<<EOF
$this->top
<div id="admindelete-wrapper">
<h2>Items Deleted</h2>
<p><a href="$this->self?page=admin">Return to Admin</a></p>
</div>
$this->footer
EOF;
  }
}
