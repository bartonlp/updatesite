<?php
//   $SQLDEBUG = true;
//   $ERRORDEBUG = true;

//require_once("./vendor/autoload.php");
//$_site = require_once(getenv("SITELOAD"). "/siteload.php");
//$S = new $_site->className($_site);

require_once("/var/www/UpdateSite-class/UpdateSite.class.php");

class db {
  public static $lastQuery;
  public static $lastNonSelectResult;
  
  public function __construct($host, $user, $password, $database) {
    if(preg_match("/^(.*?):/", $host, $m)) {
      $host = $m[1];
    }
    $this->host = $host;
    $this->user = $user;
    $this->password = $password;
    $this->database = $database;
    $this->opendb();

    // make warning show up as exceptions
    $driver = new mysqli_driver;
    $driver->report_mode = MYSQLI_REPORT_STRICT;
  }

  protected function opendb() {
    if($this->db) {
      return $this->db;
    }
    $db = new mysqli($this->host, $this->user, $this->password);
    
    if($db->connect_errno) {
      $this->errno = $db->connect_errno;
      $this->error = $db->connect_error;
      echo "Connect Error<br>";
      exit();
    }
    
    $this->db = $db; // set this right away so if we get an error below $this->db is valid

    if(!@$db->select_db($this->database)) {
      echo "Can't select database<br>";
      exit();
    }
    // BLP 2016-03-16 -- make sure we are la time. 
    $db->query("set time_zone = 'PST8PDT'");
    return $db;
  }

  public function getDbName() {
    return $this->database;
  }
  
  /**
   * query()
   * Query database table
   * @param string $query SQL statement.
   * @return mixed result-set for select etc, true/false for insert etc.
   * On error calls SqlError() and exits.
   */

  public function query($query) {
    $db = $this->opendb();

    self::$lastQuery = $query; // for debugging

    //echo "$query<br>";
    $result = $db->query($query);

    if($result === false) {
      echo "query error<br>";
      exit();
    }
    
    if($result === true) { // did not return a result object 
      $numrows = $db->affected_rows;
      self::$lastNonSelectResult = $result;
    } else {
      // NOTE: we don't change result for inserts etc. only for selects etc.
      $this->result = $result;
      $numrows = $result->num_rows;
    }

    return $numrows;
  }

    /**
   * fetchrow()
   * @param resource identifier returned from query.
   * @param string, type of fetch: assoc==associative array, num==numerical array, or both
   * @return array, either assoc or numeric, or both
   * NOTE: if $result is a string then it is the type and we use $this->result for result.
   */
  
  public function fetchrow($result=null, $type="both") {
    if(is_string($result)) {
      $type = $result;
      $result = $this->result;
    } elseif(!$result) {
      $result = $this->result;
    } 

    if(!$result) {
      echo "result is null<br>";
      exit();
    }

    switch($type) {
      case "assoc": // associative array
        $row = $result->fetch_assoc();
        break;
      case "num":  // numerical array
        $row = $result->fetch_row();
        break;
      case "both":
      default:
        $row = $result->fetch_array();
        break;
    }
    return $row;
  }
  
  /**
   * getLastInsertId()
   * See the comments below. The bottom line is we should NEVER do multiple inserts
   * with a single insert command! You just can't tell what the insert id is. If we need to do
   * and 'insert ... on duplicate key' we better not need the insert id. If we do we should do
   * an insert in a try block and an update in a catch. That way if the insert succeeds we can
   * do the getLastInsertId() after the insert. If the insert fails for a duplicate key we do the
   * update in the catch. And if we need the id we can do a select to get it (somehow).
   * Note if the insert fails because we did a 'insert ignore ...' then last_id is zero and we return
   * zero.
   * @return the last insert id if this is done in the right order! Otherwise who knows.
   */

  public function getLastInsertId() {
    $db = $this->opendb();
    // NOTE: if you have multiple items in an insert the insert_id is for the first one in the
    // group. For example: "insert into test (name) values('one'),('two'),('three')". The id field
    // is auto_increment. insert_id will be 1 if this is done right after the creation of the
    // table. But the last id is really 3. affected_rows is 3 so the last id is:
    // (insert_id + affected_rows -1)

    // $db->info shows:
    // Insert:  Records: 4  Duplicates: 0  Warnings: 0 // insert multipe records one statement
    // Update:  Rows matched: 2  Changed: 2  Warnings: 0 // update id in (100, 101) etc.
    // Insert/upsate: Records: 2 Duplicates: 1 Warnings: 0 // insert 2 records on duplicate key
    // update 1 record. So one straight insert in this case went info id 110, one insert that was a
    // duplicate so we did one (id 100 was already there) update. affected_rows is 3 here.
    // insert_id was 100 -- not sure why, I would have thought 110.
    // If the info says 'Rows matched:' then it is an update.
    // If the info says 'Records:' without any 'Duplicates:' then it is a insert/update
    // It also looks like $db->info is only filled in if $db->affected_rows is greater than one!
    
    // If the 'insert ignore ...' did in fact NOT do an insert then insert_id is zero and there
    // were no affected_rows so we need to test for that and return zero not -1.
    
    if($db->insert_id === 0) return 0;
    
    return ($db->insert_id + $db->affected_rows) -1;
  }
  public function __toString() {
    return __CLASS__;
  }
}

$S = new db('localhost', 'barton', '7098653', 'granbyrotarydotorg');

$h->title = "Update Site For Granby Rotary";
$h->banner = "<h1>Update Site For Granby Rotary</h1>";
$h->nofooter = true; // don't have UpdateSite.class display the footers as we will do it below.

$s->site = "granbyrotary.org";

UpdateSite::secondHalf($S, $h, $s);

