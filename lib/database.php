<?php
require_once(__DIR__.'/../corelib/dataaccess.php');

function initializeDataBase_() {
  $createTable =
    array("
CREATE TABLE user(
   id INTEGER PRIMARY KEY AUTO_INCREMENT,
   name VARCHAR(30),
   lastname VARCHAR(30),
   phonenumber VARCHAR(20),
   username VARCHAR(20),
   email VARCHAR(50),
   profile_picture VARCHAR(50),
   school VARCHAR(50),
   esteem INTEGER,
   engagement INTEGER,
   lastaccess DATE,
   joindate DATE,
   last_visit DATETIME,
   isadmin INTEGER,
   FULLTEXT(name, lastname));",
	  "
CREATE TABLE user_settings(
   id INTEGER PRIMARY KEY AUTO_INCREMENT,
   user_id INTEGER,
   school_posts INTEGER,
   tts_activity INTEGER,
   followers_posts INTEGER,
   awards INTEGER);",
	  "
CREATE TABLE admin(
   id INTEGER PRIMARY KEY AUTO_INCREMENT,
   role VARCHAR(20));",
	  "
CREATE TABLE admin_settings(
   id INTEGER PRIMARY KEY AUTO_INCREMENT,
   esteem_like INTEGER,
   esteem_comment INTEGER,
   esteem_share INTEGER,
   esteem_view INTEGER,
   esteem_follow INTEGER,
   engagement_like INTEGER,
   engagement_comment INTEGER,
   engagement_share INTEGER,
   engagement_view INTEGER,
   engagement_follow INTEGER,
   log_actions INTEGER);",
	  "
CREATE TABLE award(
   id INTEGER PRIMARY KEY AUTO_INCREMENT,
   name VARCHAR(40),
   url VARCHAR(50),
   category VARCHAR(20),
   type VARCHAR(20),
   rank INTEGER,
   about VARCHAR(128));",
	  "
CREATE TABLE user_earns_award(
   id INTEGER PRIMARY KEY AUTO_INCREMENT,
   user_id INTEGER,
   award_id INTEGER,
   time DATETIME,
   promoted INTEGER);",
	  "
CREATE TABLE teachingtip(
   id INTEGER PRIMARY KEY AUTO_INCREMENT,
   author_id INTEGER,
   title VARCHAR(128),
   whencreated timestamp default current_timestamp,
   time DATETIME,
   rationale TEXT,
   description TEXT,
   practice TEXT,
   worksbetter TEXT,
   doesntworkunless TEXT,
   essence TEXT,
   school text,
   status enum('draft', 'active', 'deleted') not null default 'active',
   FULLTEXT(title,rationale,description,practice,worksbetter,doesntworkunless,essence));",
	  "
CREATE TABLE ttcomment(
   id INTEGER PRIMARY KEY AUTO_INCREMENT,
   time DATETIME,
   comment TEXT,
   archived INTEGER);",
	  "
CREATE TABLE ttkeyword(
   id INTEGER PRIMARY KEY AUTO_INCREMENT,
   ttid_id INTEGER,
   keyword VARCHAR(30),
   archived INTEGER,
   FULLTEXT(keyword));",
	  "
CREATE TABLE ttfilter(
   id INTEGER PRIMARY KEY AUTO_INCREMENT,
   teachingtip_id INTEGER,
   category VARCHAR(30),
   opt VARCHAR(30));",
	  "
CREATE TABLE ttview(
   id INTEGER PRIMARY KEY AUTO_INCREMENT,
   teachingtip_id INTEGER,
   user_id INTEGER,
   time DATETIME);",
	  "
CREATE TABLE contributors(
   id INTEGER PRIMARY KEY AUTO_INCREMENT,
   user_id INTEGER,
   teachingtip_id INTEGER,
   email VARCHAR(50),
   seen INTEGER);",
	  "
CREATE TABLE user_comments_tt(
   id INTEGER PRIMARY KEY AUTO_INCREMENT,
   user_id INTEGER,
   teachingtip_id INTEGER,
   comment_id INTEGER,
   time DATETIME,
   archived INTEGER);",
	  "
CREATE TABLE user_likes_tt(
   id INTEGER PRIMARY KEY AUTO_INCREMENT,
   user_id INTEGER,
   teachingtip_id INTEGER,
   time DATETIME,
   archived INTEGER);",
	  "
CREATE TABLE user_shares_tt(
   id INTEGER PRIMARY KEY AUTO_INCREMENT,
   sender VARCHAR(50),
   recipient VARCHAR(50),
   teachingtip_id INTEGER,
   time DATETIME,
   message TEXT,
   archived INTEGER);",
	  "
CREATE TABLE user_follows_user(
   id INTEGER PRIMARY KEY AUTO_INCREMENT,
   follower_id INTEGER,
   user_id INTEGER,
   time DATETIME);",
	  "
CREATE TABLE notification(
   id INTEGER PRIMARY KEY AUTO_INCREMENT,
   user_id INTEGER,
   activity_id INTEGER,
   activity_type VARCHAR(10),
   category VARCHAR(20),
   time DATETIME,
   seen INTEGER);");

  foreach ($createTable as $query)
    dataConnection::runQuery($query . "  ENGINE=MyISAM");
}

class ChangeTracker {
  var $_original;
  var $_changes;
    
  function __construct($original) {
    $this->_changes = array();
    $this->_original = $original;
  }
  
  function hasChanges() { return !empty($this->_changes); }
  
  function changes() { return $this->_changes; }
  
  function setIfChanged($current, $field, $type = 'string') {
    if ($current != $this->_original[$field]) {
      switch ($type) {
      case 'date': $value = dataConnection::date2db($current); break;
      case 'time': $value = dataConnection::time2db($current); break;
      case 'boolean': $value = $current ? 1 : 0; break;
      default: $value = dataConnection::safe($current); break;
      }
      
      $this->_changes[] = "$field = '" . $value . "'";
      $this->_original[$field] = $current;
    }
  }
}
  
class user {
  var $id;
  var $name;
  var $lastname;
  var $phonenumber;
  var $username;
  var $email;
  var $profile_picture;
  var $school;
  var $esteem;
  var $engagement;
  var $lastaccess;
  var $joindate;
  var $last_visit;
  var $isadmin;
  
  function __construct($asArray=null) {
    $this->id = null;
    $this->name = "";
    $this->lastname = "";
    $this->phonenumber = "";
    $this->username = "";
    $this->email = "";
    $this->profile_picture = "";
    $this->school = "";
    $this->esteem = "0";
    $this->engagement = "0";
    $this->lastaccess = time();
    $this->joindate = time();
    $this->last_visit = time();
    $this->isadmin = false;
    if ($asArray !== null)
      $this->fromArray($asArray);
  }

  function fromArray($asArray) {
    $this->id = $asArray['id'];
    $this->name = $asArray['name'];
    $this->lastname = $asArray['lastname'];
    $this->phonenumber = $asArray['phonenumber'];
    $this->username = $asArray['username'];
    $this->email = $asArray['email'];
    $this->profile_picture = $asArray['profile_picture'];
    $this->school = $asArray['school'];
    $this->esteem = $asArray['esteem'];
    $this->engagement = $asArray['engagement'];
    $this->lastaccess = dataConnection::db2date($asArray['lastaccess']);
    $this->joindate = dataConnection::db2date($asArray['joindate']);
    $this->last_visit = dataConnection::db2time($asArray['last_visit']);
    $this->isadmin = ($asArray['isadmin']==0)?false:true;
    $this->original = $asArray;
  }
  
  static function retrieve_user($id) {
    $query = "SELECT * FROM user WHERE id='".dataConnection::safe($id)."';";
    $result = dataConnection::runQuery($query);
    if(sizeof($result)!=0)
      return new user($result[0]);
    else
      return false;
  }

  static function retrieve_user_matching($field, $value, $from = 0, $count = -1, $sort = null) {
    if (preg_replace('/\W/','',$field)!== $field)
      return false; // not a permitted field name;
    $query = "SELECT * FROM user WHERE $field='" . dataConnection::safe($value) . "'";
    if ($sort !== null && preg_replace('/\W/','',$sort)!== $sort)
      $query .= " ORDER BY ".$sort;
    if ($count != -1 && is_int($count) && is_int($from))
      $query .= " LIMIT " . $count . " OFFSET " . $from;
    $query .= ';';
    $result = dataConnection::runQuery($query);
    if (sizeof($result) != 0) {
      $output = array();
      foreach ($result as $r)
	$output[] = new user($r);
      return $output;
    } else
      return false;
  }
	
  function insert() {
    //#Any required insert methods for foreign keys need to be called here.
    $query = "INSERT INTO user(name, lastname, phonenumber, username, email, profile_picture, school, esteem, engagement, lastaccess, joindate, last_visit, isadmin) VALUES(";
    $query .= "'".dataConnection::safe($this->name)."', ";
    $query .= "'".dataConnection::safe($this->lastname)."', ";
    $query .= "'".dataConnection::safe($this->phonenumber)."', ";
    $query .= "'".dataConnection::safe($this->username)."', ";
    $query .= "'".dataConnection::safe($this->email)."', ";
    $query .= "'".dataConnection::safe($this->profile_picture)."', ";
    $query .= "'".dataConnection::safe($this->school)."', ";
    $query .= "'".dataConnection::safe($this->esteem)."', ";
    $query .= "'".dataConnection::safe($this->engagement)."', ";
    $query .= "'".dataConnection::date2db($this->lastaccess)."', ";
    $query .= "'".dataConnection::date2db($this->joindate)."', ";
    $query .= "'".dataConnection::time2db($this->last_visit)."', ";
    $query .= "'".(($this->isadmin===false)?0:1)."');";
    dataConnection::runQuery("BEGIN;");
    $result = dataConnection::runQuery($query);
    $result2 = dataConnection::runQuery("SELECT LAST_INSERT_ID() AS id;");
    dataConnection::runQuery("COMMIT;");
    $this->id = $result2[0]['id'];
    return $this->id;
  }

  function update() {
    $changes = new ChangeTracker($this->original);
    $changes->setIfChanged($this->name, 'name');
    $changes->setIfChanged($this->lastname, 'lastname');
    $changes->setIfChanged($this->phonenumber, 'phonenumber');
    $changes->setIfChanged($this->username, 'username');
    $changes->setIfChanged($this->email, 'email');
    $changes->setIfChanged($this->profile_picture, 'profile_picture');
    $changes->setIfChanged($this->school, 'school');
    $changes->setIfChanged($this->esteem, 'esteem');
    $changes->setIfChanged($this->engagement, 'engagement');
    $changes->setIfChanged($this->lastaccess, 'lastaccess', 'date');
    $changes->setIfChanged($this->joindate, 'joindate', 'date');
    $changes->setIfChanged($this->last_visit, 'last_visit', 'time');
    $changes->setIfChanged($this->isadmin, 'isadmin', 'boolean');

    if ($changes->hasChanges()) {
      if (!dataConnection::runQuery("update user set " . join(", ", $changes->changes()) . " where id = '" . dataConnection::safe($this->id) . "';"))
	return false;
      $this->original = $changes->_original;
    }

    return true;
  }
  
  static function count($where_name=null, $equals_value=null) {
    $query = "SELECT COUNT(*) AS count FROM user WHERE ";
    if($where_name==null)
      $query .= '1;';
    else
      $query .= "$where_name='".dataConnection::safe($equals_value)."';";
    $result = dataConnection::runQuery($query);
    if($result == false)
      return 0;
    else
      return $result['0']['count'];
  }

  function toXML() {
    $out = "<user>\n";
    $out .= '<id>'.htmlentities($this->id)."</id>\n";
    $out .= '<name>'.htmlentities($this->name)."</name>\n";
    $out .= '<lastname>'.htmlentities($this->lastname)."</lastname>\n";
    $out .= '<phonenumber>'.htmlentities($this->phonenumber)."</phonenumber>\n";
    $out .= '<username>'.htmlentities($this->username)."</username>\n";
    $out .= '<email>'.htmlentities($this->email)."</email>\n";
    $out .= '<profile_picture>'.htmlentities($this->profile_picture)."</profile_picture>\n";
    $out .= '<school>'.htmlentities($this->school)."</school>\n";
    $out .= '<esteem>'.htmlentities($this->esteem)."</esteem>\n";
    $out .= '<engagement>'.htmlentities($this->engagement)."</engagement>\n";
    $out .= '<lastaccess>'.htmlentities($this->lastaccess)."</lastaccess>\n";
    $out .= '<joindate>'.htmlentities($this->joindate)."</joindate>\n";
    $out .= '<last_visit>'.htmlentities($this->last_visit)."</last_visit>\n";
    $out .= '<isadmin>'.htmlentities($this->isadmin)."</isadmin>\n";
    $out .= "</user>\n";
    return $out;
  }
  
  static function get_number_users() {
    $result = dataConnection::runQuery("select count(id) as number_users from user");
    return $result[0]['number_users'];
  }
  
  static function retrieve_by_username($username) {
    $result = dataConnection::runQuery("select * from user where username = '" . dataConnection::safe($username) . "';");
    if (sizeof($result) != 0)
      return new user($result[0]);
    else
      return false;
  }

  static function get_all_users() {
    $output = array();
    foreach (dataConnection::runQuery("select * from user order by id asc") as $r)
      $output[] = new user($r);
    return $output;
  }

  static function get_most_tts($limit, $offset) {
    $query = "select u.*, count(tt.id) as number_tts from user as u inner join teachingtip as tt on u.id = tt.author_id
 where tt.status = 'active'
 group by u.id
 order by number_tts desc, u.id
 limit $limit offset $offset";
    $users = array();
    foreach (dataConnection::runQuery($query) as $r)
      array_push($users, array('user' => new user($r), 'n' => $r['number_tts']));
    return $users;
  }

  function get_school_for_tt($schoolList) {
    foreach (dataConnection::runQuery("select school from teachingtip where author_id = " . dataConnection::safe($this->id) . ' order by whencreated desc') as $row)
      if (in_array($row['school'], $schoolList))
	return $row['school'];

    $closest = '';
    foreach ($schoolList as $school) {
      $score = levenshtein(strtoupper($this->school), strtoupper($school), 5, 5, 1);
      if (!isset($bestscore) || $score < $bestscore) {
	$bestscore = $score;
	$closest = $school;
      }
    }

    return $closest;
  }
  
  function get_number_new_tts($since) {
    $result = dataConnection::runQuery("
select count(id) as number_tts
 from teachingtip as tt
 where tt.time > '" . dataConnection::time2db($since) . "' and status = 'active'");
    return $result[0]['number_tts'];
  }

  function getLikes() {
    $likes = array();
    foreach (dataConnection::runQuery("select * from user_likes_tt where user_id ='" . dataConnection::safe($this->id)."' and archived = 0 ") as $r)
      array_push($likes, new user_likes_tt($r));
    return $likes;
  }

  function getComments() {
    $comments = array();
    foreach(dataConnection::runQuery("select * from user_comments_tt where user_id ='" . dataConnection::safe($this->id)."' and archived = 0") as $r)
      array_push($comments, new user_comments_tt($r));
    return $comments;
  }

  function getShares() {
    $shares = array();
    foreach (dataConnection::runQuery("select * from user_shares_tt where sender ='" . dataConnection::safe($this->email) . "' and archived = 0") as $r)
      array_push($shares, new user_shares_tt($r));
    return $shares;
  }

  function get_number_tts() {
    $result = dataConnection::runQuery("select count(id) as number_tts from teachingtip where status = 'active' and author_id = '" . dataConnection::safe($this->id) . "'");
    return $result[0]['number_tts'];
  }

  function get_most_recent_tip_date() {
    $result = dataConnection::runQuery("select unix_timestamp(max(whencreated)) as w from teachingtip where status = 'active' and author_id = '" . dataConnection::safe($this->id) . "'");
    return $result[0]['w'];
  }
  
  function get_number_received_likes() {
    $result = dataConnection::runQuery("
select count(ultt.id) as number_likes
 from user_likes_tt as ultt inner join teachingtip as tt on ultt.teachingtip_id = tt.id
 where tt.status = 'active' and tt.author_id = '" . dataConnection::safe($this->id) . "' and ultt.user_id <> '" . dataConnection::safe($this->id) . "'");
    return $result[0]['number_likes'];
  }

  function get_number_received_comments() {
    $result = dataConnection::runQuery("
select count(uctt.id) as number_comments from user_comments_tt as uctt inner join teachingtip as tt on uctt.teachingtip_id = tt.id
 where tt.status = 'active' and tt.author_id = '" . dataConnection::safe($this->id) . "' and uctt.user_id <> '" . dataConnection::safe($this->id). "'");
    return $result[0]['number_comments'];
  }

  function get_number_shares_of_tts() {
    $result = dataConnection::runQuery("
select count(ustt.id) as number_shares from user_shares_tt as ustt inner join teachingtip as tt on ustt.teachingtip_id = tt.id
 where tt.status = 'active' and tt.author_id = '" . dataConnection::safe($this->id) . "' and ustt.sender <> '".dataConnection::safe($this->email). "'");
    return $result[0]['number_shares'];
  }

  function get_number_received_views_tts() {
    $result = dataConnection::runQuery("
select count(ttv.id) as number_views from ttview as ttv inner join teachingtip as tt on ttv.teachingtip_id = tt.id
 where tt.status = 'active' and tt.author_id = '" . dataConnection::safe($this->id) . "'");
    return $result[0]['number_views'];
  }

  function get_number_followers() {
    $result = dataConnection::runQuery("select count(*) as number_followers from user_follows_user where user_id = '" . dataConnection::safe($this->id) . "'");
    return $result[0]['number_followers'];
  }

  function get_number_given_likes() {
    $query = "SELECT COUNT(ultt.id) AS number_likes FROM user_likes_tt as ultt INNER JOIN teachingtip as tt ON ultt.teachingtip_id = tt.id WHERE tt.status = 'active' AND tt.author_id <> '".dataConnection::safe($this->id)."' AND ultt.user_id = '".dataConnection::safe($this->id). "'";
    $result = dataConnection::runQuery($query);
    return $result[0]['number_likes'];
  }

  function get_number_given_views() {
    $query = "SELECT COUNT(ttv.id) AS number_views FROM ttview as ttv INNER JOIN teachingtip as tt ON ttv.teachingtip_id = tt.id WHERE tt.status = 'active' AND ttv.user_id = '".dataConnection::safe($this->id). "' AND tt.author_id <> '".dataConnection::safe($this->id). "'";
    $result = dataConnection::runQuery($query);
    return $result[0]['number_views'];
  }
  
  function get_number_given_shares() {
    $query = "SELECT COUNT(ustt.id) AS number_shares FROM user_shares_tt as ustt INNER JOIN teachingtip as tt ON ustt.teachingtip_id = tt.id WHERE tt.status = 'active' AND tt.author_id <> '".dataConnection::safe($this->id)."' AND ustt.sender = '".dataConnection::safe($this->email). "'";
    $result = dataConnection::runQuery($query);
    return $result[0]['number_shares'];
  }
  
  function get_number_given_comments() {
    $query = "SELECT COUNT(uctt.id) AS number_comments FROM user_comments_tt as uctt INNER JOIN teachingtip as tt ON uctt.teachingtip_id = tt.id WHERE tt.status = 'active' AND tt.author_id <> '".dataConnection::safe($this->id)."' AND uctt.user_id = '".dataConnection::safe($this->id). "'";
    $result = dataConnection::runQuery($query);
    return $result[0]['number_comments'];
  }

  function get_number_following() {
    $query = "SELECT COUNT(*) AS number_following FROM user_follows_user WHERE follower_id = '" .dataConnection::safe($this->id) . "'";
    $result = dataConnection::runQuery($query);
    return $result[0]['number_following'];
  }

  function get_teaching_tips() {
    $tts = array();
    foreach(dataConnection::runQuery("select * from teachingtip where author_id = '".dataConnection::safe($this->id) . "' and status <> 'deleted'") as $r)
      array_push($tts, new teachingtip($r));
    return $tts;
  }

  function get_contr_teaching_tips(){
    $tts = array();
    foreach (dataConnection::runQuery("select teachingtip_id from contributors where user_id is not null and user_id = '".dataConnection::safe($this->id) . "' ") as $r)
      array_push($tts, teachingtip::retrieve_teachingtip($r['teachingtip_id']));
    return $tts;
  }
  
  function get_top_teaching_tips($limit = false) {
    $query = "select tt.*, count(ultt.id) as count_likes from teachingtip as tt left join user_likes_tt as ultt on tt.id = ultt.teachingtip_id where tt.author_id = '"
      . dataConnection::safe($this->id)
      . "' and tt.status = 'active' group by tt.id order by count_likes desc";
    if ($limit)
      $query .= " limit ". dataConnection::safe($limit);
    $tts = array();
    foreach(dataConnection::runQuery($query) as $r)
      array_push($tts, new teachingtip($r));
    return $tts;
  }

  /* REPUTATION FUNCTIONS */

  // ESTEEM

  // +ESTEEM_POST esteem for posting a new tt
  // function esteem_new_tt() {
  // 	$delta = $GLOBALS['ESTEEM_POST'];
  // 	$query = "UPDATE user SET esteem = esteem + $delta WHERE id = '" . dataConnection::safe($this->id) ."'";
  // 	$result = dataConnection::runQuery($query);
  // 	return $result;
  // }

  // -ESTEEM_POST esteem for deleting a TT (points for like/comments/shares remain)
  // function esteem_delete_tt() {
  // 	$delta = $GLOBALS['ESTEEM_POST'];
  // 	$query = "UPDATE user SET esteem = esteem - $delta WHERE id = '" . dataConnection::safe($this->id) ."'";
  // 	$result = dataConnection::runQuery($query);
  // 	return $result;
  // }

  // +ESTEEM_LIKE esteem when user receives a like on one of their TTs
  function esteem_like_tt() {
    $delta = $GLOBALS['ESTEEM_LIKE'];
    $query = "UPDATE user SET esteem = esteem + $delta WHERE id = '" . dataConnection::safe($this->id) ."'";
    $result = dataConnection::runQuery($query);
    return $result;
  }

  function esteem_unlike_tt() {
    $delta = $GLOBALS['ESTEEM_LIKE'];
    $query = "UPDATE user SET esteem = esteem - $delta WHERE id = '" . dataConnection::safe($this->id) ."'";
    $result = dataConnection::runQuery($query);
    return $result;
  }

  // +ESTEEM_SHARE esteem when someone shares one of the user's TTs
  function esteem_share_tt() {
    $delta = $GLOBALS['ESTEEM_SHARE'];
    $query = "UPDATE user SET esteem = esteem + $delta WHERE id = '" . dataConnection::safe($this->id) ."'";
    $result = dataConnection::runQuery($query);
    return $result;
  }

  // +ESTEEM_COMMENT esteem when user receives a comment on one of their TTS
  function esteem_comment_tt() {
    $delta = $GLOBALS['ESTEEM_COMMENT'];
    $query = "UPDATE user SET esteem = esteem + $delta WHERE id = '" . dataConnection::safe($this->id) ."'";
    $result = dataConnection::runQuery($query);
    return $result;
  }

  function esteem_delete_comment_tt() {
    $delta = $GLOBALS['ESTEEM_COMMENT'];
    $query = "UPDATE user SET esteem = esteem - $delta WHERE id = '" . dataConnection::safe($this->id) ."'";
    $result = dataConnection::runQuery($query);
    return $result;
  }

  // update esteem based on the number of views a SINGLE TT has
  function esteem_views_tt($number_views) {
    $delta = $GLOBALS['ESTEEM_VIEW'];
    $query = "UPDATE user SET esteem = esteem + $delta WHERE id = '" . dataConnection::safe($this->id) ."'";
    $result = dataConnection::runQuery($query);
    return $result;
  }

  // update esteem when another user follows this user
  function esteem_follow() {
    $delta = 1;
    $query = "UPDATE user SET esteem = esteem + $delta WHERE id = '" . dataConnection::safe($this->id) ."'";
    $result = dataConnection::runQuery($query);
    return $result;
  }

  // get the notifications settings for this user
  function get_settings() {
    $user_settings = user_settings::retrieve_user_settings($this->id);
    return $user_settings;
  }


  // ENGAGEMENT
  
  // +ENGAGEMENT_LIKE engagement when user likes other user's TT
  function engagement_like_tt() {
    $delta = $GLOBALS['ENGAGEMENT_LIKE'];
    $query = "UPDATE user SET engagement = engagement + $delta WHERE id = '" . dataConnection::safe($this->id) ."'";
    $result = dataConnection::runQuery($query);
    return $result;
  }

  function engagement_unlike_tt() {
    $delta = $GLOBALS['ENGAGEMENT_LIKE'];
    $query = "UPDATE user SET engagement = engagement - $delta WHERE id = '" . dataConnection::safe($this->id) ."'";
    $result = dataConnection::runQuery($query);
    return $result;
  }

  // +ENGAGEMENT_SHARE engagement when user shares other user's TT
  function engagement_share_tt() {
    $delta = $GLOBALS['ENGAGEMENT_SHARE'];
    $query = "UPDATE user SET engagement = engagement + $delta WHERE id = '" . dataConnection::safe($this->id) ."'";
    $result = dataConnection::runQuery($query);
    return $result;
  }

  // +ENGAGEMENT_COMMENT engagement when user comments on other user's TT
  function engagement_comment_tt() {
    $delta = $GLOBALS['ENGAGEMENT_COMMENT'];
    $query = "UPDATE user SET engagement = engagement + $delta WHERE id = '" . dataConnection::safe($this->id) ."'";
    $result = dataConnection::runQuery($query);
    return $result;
  }

  function engagement_delete_comment_tt() {
    $delta = $GLOBALS['ENGAGEMENT_COMMENT'];
    $query = "UPDATE user SET engagement = engagement - $delta WHERE id = '" . dataConnection::safe($this->id) ."'";
    $result = dataConnection::runQuery($query);
    return $result;
  }

  // update engagement based on the number of views this user has on other users' TTs
  function engagement_views_tt($number_views) {
    $delta = $GLOBALS['ENGAGEMENT_VIEW'];
    $query = "UPDATE user SET engagement = engagement + $delta WHERE id = '" . dataConnection::safe($this->id) ."'";
    $result = dataConnection::runQuery($query);
    return $result;
  }

  // update engagement when this user follows another user
  function engagement_follow() {
    $delta = 1;
    $query = "UPDATE user SET engagement = engagement + $delta WHERE id = '" . dataConnection::safe($this->id) ."'";
    $result = dataConnection::runQuery($query);
    return $result;
  }


  /* AWARDS */

  function get_awards() {
    $query = "
select a.* from user_earns_award as uea
 inner join award as a on uea.award_id = a.id
 where uea.user_id = '" . dataConnection::safe($this->id) . "' and uea.promoted = 0
 order by a.rank";
    $result = dataConnection::runQuery($query);
    $awards = array();
    foreach ($result as $r)
      array_push($awards, new award($r));
    return $awards;
  }

  // get the number of active (unpromoted) $rank awards in category $cat of type $type for this user
  function get_number_awards($cat, $type, $rank) {
    $query = "SELECT COUNT(uea.id) AS number_awards FROM user_earns_award as uea
			INNER JOIN award as a ON uea.award_id = a.id
			WHERE uea.user_id= '".dataConnection::safe($this->id)."' AND uea.promoted = 0 AND a.category = '".dataConnection::safe($cat)."' AND a.type = '".dataConnection::safe($type)."' AND a.rank = '".dataConnection::safe($rank)."'";
    $result = dataConnection::runQuery($query);
    return $result[0]['number_awards'];
  }

  // promote all active (unpromoted) $rank awards in category $cat of type $type for this user
  function promote_awards($cat, $type, $rank) {
    $query = "UPDATE user_earns_award as uea
			INNER JOIN award as a ON uea.award_id = a.id
			SET uea.promoted = 1 
			WHERE uea.user_id = '".dataConnection::safe($this->id)."' AND uea.promoted = 0 AND a.category = '".dataConnection::safe($cat)."' AND a.type = '".dataConnection::safe($type)."' AND a.rank = '".dataConnection::safe($rank)."'";
    $result = dataConnection::runQuery($query);
    return $result;
  }
  
	// get the highest rank award for this user in $cat category
  function get_highest_award($cat, $type) {
    $query = "SELECT DISTINCT uea.* FROM user_earns_award as uea
			INNER JOIN award as a ON uea.award_id = a.id
			WHERE uea.user_id = '". dataConnection::safe($this->id) ."' AND uea.promoted = 0 AND a.category = '". dataConnection::safe($cat) ."' AND a.type = '". dataConnection::safe($type) ."' ORDER BY a.rank DESC";
    $result = dataConnection::runQuery($query);
    if (sizeof($result) != 0)
      return new user_earns_award($result[0]);
    return false;
  }
  
  // update this user's awards if needed
  function update_awards($cat, $type) {
    if ($type == 'esteem') {
      switch ($cat) {
      case 'likes':
	$count = $this->get_number_received_likes();
	break;
      case 'views':
	$count = $this->get_number_received_views_tts();
	break;
      case 'comments':
	$count = $this->get_number_received_comments();
	break;
      case 'shares':
	$count = $this->get_number_shares_of_tts();
	break;
      case 'follows':
	$count = $this->get_number_followers();
	break;
      }
    } elseif ($type == 'engagement') {
      switch ($cat) {
      case 'likes':
	$count = $this->get_number_given_likes();
	break;
      case 'views':
	$count = $this->get_number_given_views();
	break;
      case 'comments':
	$count = $this->get_number_given_comments();
	break;
      case 'shares':
	$count = $this->get_number_given_shares();
	break;	
      case 'follows':
	$count = $this->get_number_following();
	break;
      }
    }
    
    /* 
     * A STAR award is given when the user reaches 5, 15 or 25 points in category $cat.
     *
     * A BRONZE award is given when the user reaches 10 points in category $cat.
     *
     * A SILVER award is given when the user reaches 20 points in category $cat.
     *
     * A GOLD award is given when the user reaches 30 points in category $cat.
     */
    
    $give_award = false;
    switch ($count) {
    case 5:
      $rank = 1;
      break;
    case 10:
      $rank = 2;
      $this->promote_awards($cat, $type, 1);
      break;
    case 15:
      $rank = 3;
      break;
    case 20:
      $rank = 4;
      $this->promote_awards($cat, $type, 2);
      $this->promote_awards($cat, $type, 3);
      break;
    case 25:
      $rank = 5;
      break;
    case 30:
      $rank = 6;
      $this->promote_awards($cat, $type, 4);
      $this->promote_awards($cat, $type, 5);
      break;
    default:
      return false;
    }
    
    // new award is needed - create new award matching $cat, $type, $rank
    $uea = new user_earns_award();
    $uea->user_id = $this->id;
    $uea->award_id = award::get_award_matching($cat, $type, $rank);
    $uea->promoted = 0;
    $aid = $uea->insert();
    return $aid;
  }
  
  // update this user's overall $type awards if needed
  function update_overall_awards($type) {
    $hul = $this->get_highest_award('likes', $type);
    $huv = $this->get_highest_award('views', $type);
    $hus = $this->get_highest_award('shares', $type);
    $huc = $this->get_highest_award('comments', $type);
    $huf = $this->get_highest_award('follows', $type);
    
    $hl = award::retrieve_award($hul->award_id)->rank;
    $hv = award::retrieve_award($huv->award_id)->rank;
    $hs = award::retrieve_award($hus->award_id)->rank;
    $hc = award::retrieve_award($huc->award_id)->rank;
    $hf = award::retrieve_award($huf->award_id)->rank;

    $ranks = array($hl, $hv, $hs, $hc, $hf);
    $min_rank = min($ranks);

    // check if award has already been given
    $huo = $this->get_highest_award('overall', $type);
    $ho = award::retrieve_award($huo->award_id)->rank;
    
    if ($ho == $min_rank) return false;

    // new overall award needed
    $uea = new user_earns_award();
    $uea->user_id = $this->id;
    $uea->award_id = award::get_award_matching('overall', $type, $min_rank);
    $uea->promoted = 0;
    $aid = $uea->insert();

    // promote the other overall awards (if necessary)
    if ($min_rank == 2) $this->promote_awards('overall', $type, 1);
    elseif ($min_rank == 4) {
      $this->promote_awards('overall', $type, 2);
      $this->promote_awards('overall', $type, 3);
    } elseif ($min_rank == 6) {
      $this->promote_awards($cat, $type, 4);
      $this->promote_awards($cat, $type, 5);
    }
    
    return $aid;
  }

  /* EMAIL NOTIFICATIONS */
  
  // get this user's notifications in $cats categories for the past week
  // if $cats = false, get notifications in all categories
  function get_past_week_notifications($cats=false) {
    $interval = 7;             // number of days
    $query = "SELECT * FROM notification WHERE user_id = '".dataConnection::safe($this->id)."'";
    if ($cats) {
      $query .= " AND (category = '$cats[0]'";
      if (sizeof($cats) > 1)
	foreach (array_slice($cats, 1) as $c) $query .= "OR category = '$c'";
      $query .= ")";
    }
    
    $query .= " AND DATEDIFF(NOW(), time) <= {$interval} ORDER BY time";
    $result = dataConnection::runQuery($query);
    if (sizeof($result) != 0) {
      $notifications = array();
      foreach($result as $r)
	array_push($notifications, new notification($r));
      return $notifications;
    } else
      return false;
  }
}

class user_settings {
  var $id; //primary key
  var $user_id; //foreign key
  var $school_posts;
  var $tts_activity;
  var $followers_posts;
  var $awards;
  
  function __construct($asArray=null) {
    $this->id = null; //primary key
    $this->user_id = null; // foreign key, needs dealt with.
    $this->school_posts = "0";
    $this->tts_activity = "0";
    $this->followers_posts = "0";
    $this->awards = "0";
    if($asArray!==null)
      $this->fromArray($asArray);
  }
  
  function fromArray($asArray)
	{
		$this->id = $asArray['id'];
		$this->user_id = $asArray['user_id']; // foreign key, check code
		$this->school_posts = $asArray['school_posts'];
		$this->tts_activity = $asArray['tts_activity'];
		$this->followers_posts = $asArray['followers_posts'];
		$this->awards = $asArray['awards'];
	}

	static function retrieve_user_settings($id)
	{
		$query = "SELECT * FROM user_settings WHERE id='".dataConnection::safe($id)."';";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
		{
			return new user_settings($result[0]);
		}
		else
			return false;
	}

	static function retrieve_user_settings_matching($field, $value, $from=0, $count=-1, $sort=null)
	{
	    if(preg_replace('/\W/','',$field)!== $field)
	        return false; // not a permitted field name;
	    $query = "SELECT * FROM user_settings WHERE $field='".dataConnection::safe($value)."'";
	    if(($sort !== null)&&(preg_replace('/\W/','',$sort)!== $sort))
	        $query .= " ORDER BY ".$sort;
	    if(($count != -1)&&(is_int($count))&&(is_int($from)))
	        $query .= " LIMIT ".$count." OFFSET ".$from;
	    $query .= ';';
	    $result = dataConnection::runQuery($query);
	    if(sizeof($result)!=0)
	    {
	        $output = array();
	        foreach($result as $r)
	            $output[] = new user_settings($r);
	        return $output;
	    }
	    else
	        return false;
	}
	
	function insert()
	{
		//#Any required insert methods for foreign keys need to be called here.
		$query = "INSERT INTO user_settings(user_id, school_posts, tts_activity, followers_posts, awards) VALUES(";
		if($this->user_id!==null)
			$query .= "'".dataConnection::safe($this->user_id)."', ";
		else
			$query .= "null, ";
		$query .= "'".dataConnection::safe($this->school_posts)."', ";
		$query .= "'".dataConnection::safe($this->tts_activity)."', ";
		$query .= "'".dataConnection::safe($this->followers_posts)."', ";
		$query .= "'".dataConnection::safe($this->awards)."');";
		dataConnection::runQuery("BEGIN;");
		$result = dataConnection::runQuery($query);
		$result2 = dataConnection::runQuery("SELECT LAST_INSERT_ID() AS id;");
		dataConnection::runQuery("COMMIT;");
		$this->id = $result2[0]['id'];
		return $this->id;
	}

	function update()
	{
		$query = "UPDATE user_settings ";
		$query .= "SET user_id='".dataConnection::safe($this->user_id)."' ";
		$query .= ", school_posts='".dataConnection::safe($this->school_posts)."' ";
		$query .= ", tts_activity='".dataConnection::safe($this->tts_activity)."' ";
		$query .= ", followers_posts='".dataConnection::safe($this->followers_posts)."' ";
		$query .= ", awards='".dataConnection::safe($this->awards)."' ";
		$query .= "WHERE id='".dataConnection::safe($this->id)."';";
		return dataConnection::runQuery($query);
	}

	static function count($where_name=null, $equals_value=null)
	{
		$query = "SELECT COUNT(*) AS count FROM user_settings WHERE ";
		if($where_name==null)
			$query .= '1;';
		else
			$query .= "$where_name='".dataConnection::safe($equals_value)."';";
		$result = dataConnection::runQuery($query);
		if($result == false)
			return 0;
		else
			return $result['0']['count'];
	}

	function toXML()
	{
		$out = "<user_settings>\n";
		$out .= '<id>'.htmlentities($this->id)."</id>\n";
		$out .= '<user>'.htmlentities($this->user)."</user>\n";
		$out .= '<school_posts>'.htmlentities($this->school_posts)."</school_posts>\n";
		$out .= '<tts_activity>'.htmlentities($this->tts_activity)."</tts_activity>\n";
		$out .= '<followers_posts>'.htmlentities($this->followers_posts)."</followers_posts>\n";
		$out .= '<awards>'.htmlentities($this->awards)."</awards>\n";
		$out .= "</user_settings>\n";
		return $out;
	}
	//[[USERCODE_user_settings]] Put code for custom class members in this block.

	//[[USERCODE_user_settings]] WEnd of custom class members.
}

class admin
{
	var $id; //primary key
	var $role;

	function __construct($asArray=null)
	{
		$this->id = null; //primary key
		$this->role = "";
		if($asArray!==null)
			$this->fromArray($asArray);
	}

	function fromArray($asArray)
	{
		$this->id = $asArray['id'];
		$this->role = $asArray['role'];
	}

	static function retrieve_admin($id)
	{
		$query = "SELECT * FROM admin WHERE id='".dataConnection::safe($id)."';";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
		{
			return new admin($result[0]);
		}
		else
			return false;
	}

	static function retrieve_admin_matching($field, $value, $from=0, $count=-1, $sort=null)
	{
	    if(preg_replace('/\W/','',$field)!== $field)
	        return false; // not a permitted field name;
	    $query = "SELECT * FROM admin WHERE $field='".dataConnection::safe($value)."'";
	    if(($sort !== null)&&(preg_replace('/\W/','',$sort)!== $sort))
	        $query .= " ORDER BY ".$sort;
	    if(($count != -1)&&(is_int($count))&&(is_int($from)))
	        $query .= " LIMIT ".$count." OFFSET ".$from;
	    $query .= ';';
	    $result = dataConnection::runQuery($query);
	    if(sizeof($result)!=0)
	    {
	        $output = array();
	        foreach($result as $r)
	            $output[] = new admin($r);
	        return $output;
	    }
	    else
	        return false;
	}
	
	function insert()
	{
		//#Any required insert methods for foreign keys need to be called here.
		$query = "INSERT INTO admin(role) VALUES(";
		$query .= "'".dataConnection::safe($this->role)."');";
		dataConnection::runQuery("BEGIN;");
		$result = dataConnection::runQuery($query);
		$result2 = dataConnection::runQuery("SELECT LAST_INSERT_ID() AS id;");
		dataConnection::runQuery("COMMIT;");
		$this->id = $result2[0]['id'];
		return $this->id;
	}

	function update()
	{
		$query = "UPDATE admin ";
		$query .= "SET role='".dataConnection::safe($this->role)."' ";
		$query .= "WHERE id='".dataConnection::safe($this->id)."';";
		return dataConnection::runQuery($query);
	}

	static function count($where_name=null, $equals_value=null)
	{
		$query = "SELECT COUNT(*) AS count FROM admin WHERE ";
		if($where_name==null)
			$query .= '1;';
		else
			$query .= "$where_name='".dataConnection::safe($equals_value)."';";
		$result = dataConnection::runQuery($query);
		if($result == false)
			return 0;
		else
			return $result['0']['count'];
	}

	function toXML()
	{
		$out = "<admin>\n";
		$out .= '<id>'.htmlentities($this->id)."</id>\n";
		$out .= '<role>'.htmlentities($this->role)."</role>\n";
		$out .= "</admin>\n";
		return $out;
	}
	//[[USERCODE_admin]] Put code for custom class members in this block.

	//[[USERCODE_admin]] WEnd of custom class members.
}

class admin_settings
{
	var $id; //primary key
	var $esteem_like;
	var $esteem_comment;
	var $esteem_share;
	var $esteem_view;
	var $esteem_follow;
	var $engagement_like;
	var $engagement_comment;
	var $engagement_share;
	var $engagement_view;
	var $engagement_follow;
	var $log_actions;

	function __construct($asArray=null)
	{
		$this->id = null; //primary key
		$this->esteem_like = "0";
		$this->esteem_comment = "0";
		$this->esteem_share = "0";
		$this->esteem_view = "0";
		$this->esteem_follow = "0";
		$this->engagement_like = "0";
		$this->engagement_comment = "0";
		$this->engagement_share = "0";
		$this->engagement_view = "0";
		$this->engagement_follow = "0";
		$this->log_actions = "0";
		if($asArray!==null)
			$this->fromArray($asArray);
	}

	function fromArray($asArray)
	{
		$this->id = $asArray['id'];
		$this->esteem_like = $asArray['esteem_like'];
		$this->esteem_comment = $asArray['esteem_comment'];
		$this->esteem_share = $asArray['esteem_share'];
		$this->esteem_view = $asArray['esteem_view'];
		$this->esteem_follow = $asArray['esteem_follow'];
		$this->engagement_like = $asArray['engagement_like'];
		$this->engagement_comment = $asArray['engagement_comment'];
		$this->engagement_share = $asArray['engagement_share'];
		$this->engagement_view = $asArray['engagement_view'];
		$this->engagement_follow = $asArray['engagement_follow'];
		$this->log_actions = $asArray['log_actions'];
	}

	static function retrieve_admin_settings($id)
	{
		$query = "SELECT * FROM admin_settings WHERE id='".dataConnection::safe($id)."';";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
		{
			return new admin_settings($result[0]);
		}
		else
			return false;
	}

	static function retrieve_admin_settings_matching($field, $value, $from=0, $count=-1, $sort=null)
	{
	    if(preg_replace('/\W/','',$field)!== $field)
	        return false; // not a permitted field name;
	    $query = "SELECT * FROM admin_settings WHERE $field='".dataConnection::safe($value)."'";
	    if(($sort !== null)&&(preg_replace('/\W/','',$sort)!== $sort))
	        $query .= " ORDER BY ".$sort;
	    if(($count != -1)&&(is_int($count))&&(is_int($from)))
	        $query .= " LIMIT ".$count." OFFSET ".$from;
	    $query .= ';';
	    $result = dataConnection::runQuery($query);
	    if(sizeof($result)!=0)
	    {
	        $output = array();
	        foreach($result as $r)
	            $output[] = new admin_settings($r);
	        return $output;
	    }
	    else
	        return false;
	}
	
	function insert()
	{
		//#Any required insert methods for foreign keys need to be called here.
		$query = "INSERT INTO admin_settings(esteem_like, esteem_comment, esteem_share, esteem_view, esteem_follow, engagement_like, engagement_comment, engagement_share, engagement_view, engagement_follow, log_actions) VALUES(";
		$query .= "'".dataConnection::safe($this->esteem_like)."', ";
		$query .= "'".dataConnection::safe($this->esteem_comment)."', ";
		$query .= "'".dataConnection::safe($this->esteem_share)."', ";
		$query .= "'".dataConnection::safe($this->esteem_view)."', ";
		$query .= "'".dataConnection::safe($this->esteem_follow)."', ";
		$query .= "'".dataConnection::safe($this->engagement_like)."', ";
		$query .= "'".dataConnection::safe($this->engagement_comment)."', ";
		$query .= "'".dataConnection::safe($this->engagement_share)."', ";
		$query .= "'".dataConnection::safe($this->engagement_view)."', ";
		$query .= "'".dataConnection::safe($this->engagement_follow)."', ";
		$query .= "'".dataConnection::safe($this->log_actions)."');";
		dataConnection::runQuery("BEGIN;");
		$result = dataConnection::runQuery($query);
		$result2 = dataConnection::runQuery("SELECT LAST_INSERT_ID() AS id;");
		dataConnection::runQuery("COMMIT;");
		$this->id = $result2[0]['id'];
		return $this->id;
	}

	function update()
	{
		$query = "UPDATE admin_settings ";
		$query .= "SET esteem_like='".dataConnection::safe($this->esteem_like)."' ";
		$query .= ", esteem_comment='".dataConnection::safe($this->esteem_comment)."' ";
		$query .= ", esteem_share='".dataConnection::safe($this->esteem_share)."' ";
		$query .= ", esteem_view='".dataConnection::safe($this->esteem_view)."' ";
		$query .= ", esteem_follow='".dataConnection::safe($this->esteem_follow)."' ";
		$query .= ", engagement_like='".dataConnection::safe($this->engagement_like)."' ";
		$query .= ", engagement_comment='".dataConnection::safe($this->engagement_comment)."' ";
		$query .= ", engagement_share='".dataConnection::safe($this->engagement_share)."' ";
		$query .= ", engagement_view='".dataConnection::safe($this->engagement_view)."' ";
		$query .= ", engagement_follow='".dataConnection::safe($this->engagement_follow)."' ";
		$query .= ", log_actions='".dataConnection::safe($this->log_actions)."' ";
		$query .= "WHERE id='".dataConnection::safe($this->id)."';";
		return dataConnection::runQuery($query);
	}

	static function count($where_name=null, $equals_value=null)
	{
		$query = "SELECT COUNT(*) AS count FROM admin_settings WHERE ";
		if($where_name==null)
			$query .= '1;';
		else
			$query .= "$where_name='".dataConnection::safe($equals_value)."';";
		$result = dataConnection::runQuery($query);
		if($result == false)
			return 0;
		else
			return $result['0']['count'];
	}

	function toXML()
	{
		$out = "<admin_settings>\n";
		$out .= '<id>'.htmlentities($this->id)."</id>\n";
		$out .= '<esteem_like>'.htmlentities($this->esteem_like)."</esteem_like>\n";
		$out .= '<esteem_comment>'.htmlentities($this->esteem_comment)."</esteem_comment>\n";
		$out .= '<esteem_share>'.htmlentities($this->esteem_share)."</esteem_share>\n";
		$out .= '<esteem_view>'.htmlentities($this->esteem_view)."</esteem_view>\n";
		$out .= '<esteem_follow>'.htmlentities($this->esteem_follow)."</esteem_follow>\n";
		$out .= '<engagement_like>'.htmlentities($this->engagement_like)."</engagement_like>\n";
		$out .= '<engagement_comment>'.htmlentities($this->engagement_comment)."</engagement_comment>\n";
		$out .= '<engagement_share>'.htmlentities($this->engagement_share)."</engagement_share>\n";
		$out .= '<engagement_view>'.htmlentities($this->engagement_view)."</engagement_view>\n";
		$out .= '<engagement_follow>'.htmlentities($this->engagement_follow)."</engagement_follow>\n";
		$out .= '<log_actions>'.htmlentities($this->log_actions)."</log_actions>\n";
		$out .= "</admin_settings>\n";
		return $out;
	}
	//[[USERCODE_admin_settings]] Put code for custom class members in this block.

	// get all settings
	static function get_settings() {
		$query = "SELECT * FROM admin_settings WHERE id = '1'";
		$result = dataConnection::runQuery($query);
		if (sizeof($result) != 0) return new admin_settings($result[0]);
		else return false;
	}

	//[[USERCODE_admin_settings]] WEnd of custom class members.
}

class award
{
	var $id; //primary key
	var $name;
	var $url;
	var $category;
	var $type;
	var $rank;
	var $about;

	function __construct($asArray=null)
	{
		$this->id = null; //primary key
		$this->name = "";
		$this->url = "";
		$this->category = "";
		$this->type = "";
		$this->rank = "0";
		$this->about = "";
		if($asArray!==null)
			$this->fromArray($asArray);
	}

	function fromArray($asArray)
	{
		$this->id = $asArray['id'];
		$this->name = $asArray['name'];
		$this->url = $asArray['url'];
		$this->category = $asArray['category'];
		$this->type = $asArray['type'];
		$this->rank = $asArray['rank'];
		$this->about = $asArray['about'];
	}

	static function retrieve_award($id)
	{
		$query = "SELECT * FROM award WHERE id='".dataConnection::safe($id)."';";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
		{
			return new award($result[0]);
		}
		else
			return false;
	}

	static function retrieve_award_matching($field, $value, $from=0, $count=-1, $sort=null)
	{
	    if(preg_replace('/\W/','',$field)!== $field)
	        return false; // not a permitted field name;
	    $query = "SELECT * FROM award WHERE $field='".dataConnection::safe($value)."'";
	    if(($sort !== null)&&(preg_replace('/\W/','',$sort)!== $sort))
	        $query .= " ORDER BY ".$sort;
	    if(($count != -1)&&(is_int($count))&&(is_int($from)))
	        $query .= " LIMIT ".$count." OFFSET ".$from;
	    $query .= ';';
	    $result = dataConnection::runQuery($query);
	    if(sizeof($result)!=0)
	    {
	        $output = array();
	        foreach($result as $r)
	            $output[] = new award($r);
	        return $output;
	    }
	    else
	        return false;
	}
	
	function insert()
	{
		//#Any required insert methods for foreign keys need to be called here.
		$query = "INSERT INTO award(name, url, category, type, rank, about) VALUES(";
		$query .= "'".dataConnection::safe($this->name)."', ";
		$query .= "'".dataConnection::safe($this->url)."', ";
		$query .= "'".dataConnection::safe($this->category)."', ";
		$query .= "'".dataConnection::safe($this->type)."', ";
		$query .= "'".dataConnection::safe($this->rank)."', ";
		$query .= "'".dataConnection::safe($this->about)."');";
		dataConnection::runQuery("BEGIN;");
		$result = dataConnection::runQuery($query);
		$result2 = dataConnection::runQuery("SELECT LAST_INSERT_ID() AS id;");
		dataConnection::runQuery("COMMIT;");
		$this->id = $result2[0]['id'];
		return $this->id;
	}

	function update()
	{
		$query = "UPDATE award ";
		$query .= "SET name='".dataConnection::safe($this->name)."' ";
		$query .= ", url='".dataConnection::safe($this->url)."' ";
		$query .= ", category='".dataConnection::safe($this->category)."' ";
		$query .= ", type='".dataConnection::safe($this->type)."' ";
		$query .= ", rank='".dataConnection::safe($this->rank)."' ";
		$query .= ", about='".dataConnection::safe($this->about)."' ";
		$query .= "WHERE id='".dataConnection::safe($this->id)."';";
		return dataConnection::runQuery($query);
	}

	static function count($where_name=null, $equals_value=null)
	{
		$query = "SELECT COUNT(*) AS count FROM award WHERE ";
		if($where_name==null)
			$query .= '1;';
		else
			$query .= "$where_name='".dataConnection::safe($equals_value)."';";
		$result = dataConnection::runQuery($query);
		if($result == false)
			return 0;
		else
			return $result['0']['count'];
	}

	function toXML()
	{
		$out = "<award>\n";
		$out .= '<id>'.htmlentities($this->id)."</id>\n";
		$out .= '<name>'.htmlentities($this->name)."</name>\n";
		$out .= '<url>'.htmlentities($this->url)."</url>\n";
		$out .= '<category>'.htmlentities($this->category)."</category>\n";
		$out .= '<type>'.htmlentities($this->type)."</type>\n";
		$out .= '<rank>'.htmlentities($this->rank)."</rank>\n";
		$out .= '<about>'.htmlentities($this->about)."</about>\n";
		$out .= "</award>\n";
		return $out;
	}
	//[[USERCODE_award]] Put code for custom class members in this block.

	static function get_award_matching($cat, $type, $rank) {
		$query = "SELECT id FROM award WHERE category = '".dataConnection::safe($cat)."' AND type ='".dataConnection::safe($type)."' AND rank = '".dataConnection::safe($rank)."'";
		$result = dataConnection::runQuery($query);
		if (sizeof($result) != 0) return $result[0]['id'];
		return false;
	}

	//[[USERCODE_award]] WEnd of custom class members.
}

class user_earns_award
{
	var $id; //primary key
	var $user_id; //foreign key
	var $award_id; //foreign key
	var $time;
	var $promoted;

	function __construct($asArray=null)
	{
		$this->id = null; //primary key
		$this->user_id = null; // foreign key, needs dealt with.
		$this->award_id = null; // foreign key, needs dealt with.
		$this->time = time();
		$this->promoted = "0";
		if($asArray!==null)
			$this->fromArray($asArray);
	}

	function fromArray($asArray)
	{
		$this->id = $asArray['id'];
		$this->user_id = $asArray['user_id']; // foreign key, check code
		$this->award_id = $asArray['award_id']; // foreign key, check code
		$this->time = dataConnection::db2time($asArray['time']);
		$this->promoted = $asArray['promoted'];
	}

	static function retrieve_user_earns_award($id)
	{
		$query = "SELECT * FROM user_earns_award WHERE id='".dataConnection::safe($id)."';";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
		{
			return new user_earns_award($result[0]);
		}
		else
			return false;
	}

	static function retrieve_user_earns_award_matching($field, $value, $from=0, $count=-1, $sort=null)
	{
	    if(preg_replace('/\W/','',$field)!== $field)
	        return false; // not a permitted field name;
	    $query = "SELECT * FROM user_earns_award WHERE $field='".dataConnection::safe($value)."'";
	    if(($sort !== null)&&(preg_replace('/\W/','',$sort)!== $sort))
	        $query .= " ORDER BY ".$sort;
	    if(($count != -1)&&(is_int($count))&&(is_int($from)))
	        $query .= " LIMIT ".$count." OFFSET ".$from;
	    $query .= ';';
	    $result = dataConnection::runQuery($query);
	    if(sizeof($result)!=0)
	    {
	        $output = array();
	        foreach($result as $r)
	            $output[] = new user_earns_award($r);
	        return $output;
	    }
	    else
	        return false;
	}
	
	function insert()
	{
		//#Any required insert methods for foreign keys need to be called here.
		$query = "INSERT INTO user_earns_award(user_id, award_id, time, promoted) VALUES(";
		if($this->user_id!==null)
			$query .= "'".dataConnection::safe($this->user_id)."', ";
		else
			$query .= "null, ";
		if($this->award_id!==null)
			$query .= "'".dataConnection::safe($this->award_id)."', ";
		else
			$query .= "null, ";
		$query .= "'".dataConnection::time2db($this->time)."', ";
		$query .= "'".dataConnection::safe($this->promoted)."');";
		dataConnection::runQuery("BEGIN;");
		$result = dataConnection::runQuery($query);
		$result2 = dataConnection::runQuery("SELECT LAST_INSERT_ID() AS id;");
		dataConnection::runQuery("COMMIT;");
		$this->id = $result2[0]['id'];
		return $this->id;
	}

	function update()
	{
		$query = "UPDATE user_earns_award ";
		$query .= "SET user_id='".dataConnection::safe($this->user_id)."' ";
		$query .= ", award_id='".dataConnection::safe($this->award_id)."' ";
		$query .= ", time='".dataConnection::time2db($this->time)."' ";
		$query .= ", promoted='".dataConnection::safe($this->promoted)."' ";
		$query .= "WHERE id='".dataConnection::safe($this->id)."';";
		return dataConnection::runQuery($query);
	}

	static function count($where_name=null, $equals_value=null)
	{
		$query = "SELECT COUNT(*) AS count FROM user_earns_award WHERE ";
		if($where_name==null)
			$query .= '1;';
		else
			$query .= "$where_name='".dataConnection::safe($equals_value)."';";
		$result = dataConnection::runQuery($query);
		if($result == false)
			return 0;
		else
			return $result['0']['count'];
	}

	function toXML()
	{
		$out = "<user_earns_award>\n";
		$out .= '<id>'.htmlentities($this->id)."</id>\n";
		$out .= '<user>'.htmlentities($this->user)."</user>\n";
		$out .= '<award>'.htmlentities($this->award)."</award>\n";
		$out .= '<time>'.htmlentities($this->time)."</time>\n";
		$out .= '<promoted>'.htmlentities($this->promoted)."</promoted>\n";
		$out .= "</user_earns_award>\n";
		return $out;
	}
	//[[USERCODE_user_earns_award]] Put code for custom class members in this block.

	//[[USERCODE_user_earns_award]] WEnd of custom class members.
}

class teachingtip {
	var $id; //primary key
	var $author_id; //foreign key
	var $title;
	var $time;
	var $rationale;
	var $description;
	var $practice;
	var $worksbetter;
	var $doesntworkunless;
	var $essence;
	var $school;
	var $status;

	function __construct($asArray=null)
	{
		$this->id = null; //primary key
		$this->author_id = null; // foreign key, needs dealt with.
		$this->title = "";
		$this->time = time();
		$this->whencreated = time();
		$this->rationale = "";
		$this->description = "";
		$this->practice = "";
		$this->worksbetter = "";
		$this->doesntworkunless = "";
		$this->essence = "";
		$this->school = '';
		$this->status = "draft";
		if($asArray!==null)
			$this->fromArray($asArray);
	}

	function fromArray($asArray)
	{
		$this->id = $asArray['id'];
		$this->author_id = $asArray['author_id']; // foreign key, check code
		$this->title = $asArray['title'];
		$this->time = dataConnection::db2time($asArray['time']);
		$this->whencreated = dataConnection::db2time($asArray['whencreated']);
		$this->rationale = $asArray['rationale'];
		$this->description = $asArray['description'];
		$this->practice = $asArray['practice'];
		$this->worksbetter = $asArray['worksbetter'];
		$this->doesntworkunless = $asArray['doesntworkunless'];
		$this->essence = $asArray['essence'];
		$this->school = $asArray['school'];
		$this->status = $asArray['status'];
	}

	static function retrieve_teachingtip($id)
	{
		$query = "SELECT * FROM teachingtip WHERE id='".dataConnection::safe($id)."';";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
		{
			return new teachingtip($result[0]);
		}
		else
			return false;
	}

	static function retrieve_teachingtip_matching($field, $value, $from=0, $count=-1, $sort=null)
	{
	    if(preg_replace('/\W/','',$field)!== $field)
	        return false; // not a permitted field name;
	    $query = "SELECT * FROM teachingtip WHERE $field='".dataConnection::safe($value)."'";
	    if(($sort !== null)&&(preg_replace('/\W/','',$sort)!== $sort))
	        $query .= " ORDER BY ".$sort;
	    if(($count != -1)&&(is_int($count))&&(is_int($from)))
	        $query .= " LIMIT ".$count." OFFSET ".$from;
	    $query .= ';';
	    $result = dataConnection::runQuery($query);
	    if(sizeof($result)!=0)
	    {
	        $output = array();
	        foreach($result as $r)
	            $output[] = new teachingtip($r);
	        return $output;
	    }
	    else
	        return false;
	}
	
	function insert()
	{
		//#Any required insert methods for foreign keys need to be called here.
		$query = "INSERT INTO teachingtip(author_id, title, time, rationale, description, practice, worksbetter, doesntworkunless, essence, school, status) VALUES(";
		if($this->author_id!==null)
			$query .= "'".dataConnection::safe($this->author_id)."', ";
		else
			$query .= "null, ";
		$query .= "'".dataConnection::safe($this->title)."', ";
		$query .= "'".dataConnection::time2db($this->time)."', ";
		$query .= "'".dataConnection::safe($this->rationale)."', ";
		$query .= "'".dataConnection::safe($this->description)."', ";
		$query .= "'".dataConnection::safe($this->practice)."', ";
		$query .= "'".dataConnection::safe($this->worksbetter)."', ";
		$query .= "'".dataConnection::safe($this->doesntworkunless)."', ";
		$query .= "'".dataConnection::safe($this->essence)."', ";
		$query .= "'".dataConnection::safe($this->school)."', ";
		$query .= "'".dataConnection::safe($this->status)."')";
		dataConnection::runQuery("BEGIN;");
		$result = dataConnection::runQuery($query);
		$result2 = dataConnection::runQuery("SELECT LAST_INSERT_ID() AS id;");
		dataConnection::runQuery("COMMIT;");
		$this->id = $result2[0]['id'];
		return $this->id;
	}

	function update()
	{
		$query = "UPDATE teachingtip ";
		$query .= "SET author_id='".dataConnection::safe($this->author_id)."' ";
		$query .= ", title='".dataConnection::safe($this->title)."' ";
		$query .= ", time='".dataConnection::time2db($this->time)."' ";
		$query .= ", rationale='".dataConnection::safe($this->rationale)."' ";
		$query .= ", description='".dataConnection::safe($this->description)."' ";
		$query .= ", practice='".dataConnection::safe($this->practice)."' ";
		$query .= ", worksbetter='".dataConnection::safe($this->worksbetter)."' ";
		$query .= ", doesntworkunless='".dataConnection::safe($this->doesntworkunless)."' ";
		$query .= ", essence='".dataConnection::safe($this->essence)."' ";
		$query .= ", school='".dataConnection::safe($this->school)."' ";
		$query .= ", status='".dataConnection::safe($this->status)."' ";
		$query .= "WHERE id='".dataConnection::safe($this->id)."';";
		return dataConnection::runQuery($query);
	}

	static function count($where_name=null, $equals_value=null)
	{
		$query = "SELECT COUNT(*) AS count FROM teachingtip WHERE ";
		if($where_name==null)
			$query .= '1;';
		else
			$query .= "$where_name='".dataConnection::safe($equals_value)."';";
		$result = dataConnection::runQuery($query);
		if($result == false)
			return 0;
		else
			return $result['0']['count'];
	}

	function toXML()
	{
		$out = "<teachingtip>\n";
		$out .= '<id>'.htmlentities($this->id)."</id>\n";
		$out .= '<author>'.htmlentities($this->author)."</author>\n";
		$out .= '<title>'.htmlentities($this->title)."</title>\n";
		$out .= '<time>'.htmlentities($this->time)."</time>\n";
		$out .= '<rationale>'.htmlentities($this->rationale)."</rationale>\n";
		$out .= '<description>'.htmlentities($this->description)."</description>\n";
		$out .= '<practice>'.htmlentities($this->practice)."</practice>\n";
		$out .= '<worksbetter>'.htmlentities($this->worksbetter)."</worksbetter>\n";
		$out .= '<doesntworkunless>'.htmlentities($this->doesntworkunless)."</doesntworkunless>\n";
		$out .= '<essence>'.htmlentities($this->essence)."</essence>\n";
		$out .= '<school>'.htmlentities($this->school)."</school>\n";
		$out .= '<status>'.htmlentities($this->status)."</status>\n";
		$out .= "</teachingtip>\n";
		return $out;
	}
	//[[USERCODE_teachingtip]] Put code for custom class members in this block.

	public function __toString() {
		return $this->id;
	}

	// get the number of published teaching tips in the system
	static function get_number_tts() {
		$query = "SELECT COUNT(id) AS number_tts FROM teachingtip WHERE status = 'active'";
		$result = dataConnection::runQuery($query);
		return $result[0]['number_tts'];
	}

	static function get_all_teaching_tips() {
	  $result = dataConnection::runQuery("select * from teachingtip order by id asc");
	  $tts = array();
	  foreach($result as $r)
	    array_push($tts, new teachingtip($r));
	  return $tts;
	}
	
	static function getPopularTeachingTips($limit, $offset, $table, $time) {
	  $andTime = $time == 0 ? '' : "and tt.time > '" . date("Y-m-d H:i:s", $time) . "'";
	  $result = dataConnection::runQuery("
select tt.*, count(u.id) as n
 from teachingtip as tt
 left join user_{$table}_tt u on tt.id = u.teachingtip_id
 where tt.status = 'active' $andTime
 group by tt.id
 order by n desc
 limit " . dataConnection::safe($limit) . " offset " . dataConnection::safe($offset));
	  $tts = array();
	  foreach($result as $r)
	    array_push($tts, new teachingtip($r));
	  return $tts;
	}

	static function get_latest_teaching_tips($limit = false) {
	  $query = "select * from teachingtip where status = 'active' order by time desc";
	  if ($limit) $query .= " limit $limit";
	  $result = dataConnection::runQuery($query);
	  if (sizeof($result) != 0) {
	    $tts = array();
	    foreach($result as $r)
	      array_push($tts, new teachingtip($r));
	    return $tts;
	  } else return false;
	}

	function get_author() {
	  $result = dataConnection::runQuery("select u.* from user as u inner join teachingtip as tt on u.id = tt.author_id where tt.id ='" . dataConnection::safe($this->id)."'");
	  if (sizeof($result) > 0)
	    return new user($result[0]);
	  else
	    return false;
	}

	function get_comments() {
	  $result = dataConnection::runQuery("
select c.* from ttcomment as c
 inner join user_comments_tt as uctt on c.id = uctt.comment_id
 where uctt.teachingtip_id ='" . dataConnection::safe($this->id) . "'");
	  $comments = array();
	  foreach ($result as $r)
	    array_push($comments, new ttcomment($r));
	  return $comments;
	}

	function get_keywords() {
	  $result = dataConnection::runQuery("select kw.* from ttkeyword as kw inner join teachingtip as tt on kw.ttid_id = tt.id where tt.id ='" . dataConnection::safe($this->id) . "'");
	  $keywords = array();
	  foreach ($result as $r)
	    array_push($keywords, new ttkeyword($r));
	  return $keywords;
	}

	function get_all_filters() {
	  $result = dataConnection::runQuery("select f.* from ttfilter as f inner join teachingtip as tt on f.teachingtip_id = tt.id where tt.id ='" . dataConnection::safe($this->id) . "'");
	  $filters = array();
	  foreach ($result as $r)
	    array_push($filters, new ttfilter($r));
	  return $filters;
	}

	function get_filters($cat) {
	  $result = dataConnection::runQuery("
select f.opt
 from ttfilter as f
 inner join teachingtip as tt on f.teachingtip_id = tt.id
 where tt.id ='" . dataConnection::safe($this->id) . "' and f.category = '" . dataConnection::safe($cat)."'");
	  $filters = array();
	  foreach ($result as $r)
	    array_push($filters, $r['opt']);
	  return $filters;
	}

	function get_number_likes() {
	  $result = dataConnection::runQuery("select count(*) as number_likes from user_likes_tt where teachingtip_id = '" . dataConnection::safe($this->id) . "'");
	  return $result[0]['number_likes'];
	}

	function get_number_likes_not_author() {
	  $result = dataConnection::runQuery("
select count(*) as number_likes
 from user_likes_tt
 where teachingtip_id = '" . dataConnection::safe($this->id) . "' and user_id <> '". dataConnection::safe($this->author_id) ."'");
	  return $result[0]['number_likes'];
	}

	function get_number_comments() {
	  $result = dataConnection::runQuery("select count(*) as number_comments from user_comments_tt where teachingtip_id = '" . dataConnection::safe($this->id) . "'");
	  return $result[0]['number_comments'];
	}

	// get the number of comments for this TT not including the author's comments (if any)
	function get_number_comments_not_author() {
	  $result = dataConnection::runQuery("
select count(*) as number_comments
 from user_comments_tt
 where teachingtip_id = '" . dataConnection::safe($this->id) . "' and user_id <> '". dataConnection::safe($this->author_id) ."'");
	  return $result[0]['number_comments'];
	}
	
	function get_number_shares() {
	  $result = dataConnection::runQuery("select count(*) as number_shares from user_shares_tt where teachingtip_id = '" . dataConnection::safe($this->id) . "'");
	  return $result[0]['number_shares'];
	}

	function get_number_views() {
	  $result = dataConnection::runQuery("select count(id) as count from ttview where teachingtip_id = '" . dataConnection::safe($this->id) . "'");
	  return $result[0]['count'];
	}

	function get_contributors() {
	  $result = dataConnection::runQuery("select * from contributors where teachingtip_id ='" . dataConnection::safe($this->id) . "' and user_id is not null");
	  $contributors = array();
	  foreach($result as $r)
	    array_push($contributors, user::retrieve_user($r['user_id']));
	  return $contributors;
	}

	function get_contributors_ids() {
	  $result = dataConnection::runQuery("select * from contributors where teachingtip_id ='" . dataConnection::safe($this->id) . "' and user_id is not null");
	  $contributors = array();
	  foreach($result as $r)
	    array_push($contributors, $r['user_id']);
	  return $contributors;
	}

	static function get_all_schools() {
	  $result = dataConnection::runQuery("select u.school from teachingtip as tt inner join user as u on tt.author_id = u.id group by u.school");
	  $schools = array();
	  foreach ($result as $r)
	    $schools[] = $r['school'];
	  return $schools;
	}

	static function get_tts_from_schools($schools) {
	  $query = "select tt.* from teachingtip as tt inner join user as u on tt.author_id = u.id where tt.status = 'active' and (tt.school = '$schools[0]'";
	  if (sizeof($schools > 1)) {
	    foreach (array_slice($schools, 1) as $school)
	      $query .= " or tt.school = '$school'";
	  }
	  
	  $query .= ") order by time desc";
	  $result = dataConnection::runQuery($query);
	  $tts = array();
	  foreach($result as $r)
	    array_push($tts, new teachingtip($r));
	  return $tts;
	}

	static function get_tts_from_filters($schools, $sizes, $envs, $sol, $itc) {
	  $filters = array();
	  
	  if ($schools) {
	    $schools_map = array_map(function($s) { return 'school = ' . "'" . $s . "'"; }, $schools);
	    if (!empty($schools_map))
	      $filters[] = '(' . implode(' or ', $schools_map) . ')';
	  }

	  if ($sizes) {
	    $sizes_map = array_map(function($cs) { return 'f.opt = ' . "'" . $cs . "'"; }, $sizes);
	    if (!empty($sizes_map))
	      $filters[] = '(' . implode(' or ', $sizes_map) . ')';
	  }
	  
	  if ($envs) {
	    $envs_map = array_map(function($e) { return 'f.opt = ' . "'" . $e . "'"; }, $envs);
	    if (!empty($envs_map))
	      $filters[] = '(' . implode(' or ', $envs_map) . ')';
	  }
	  
	  if ($sol) {
	    $sol_map = array_map(function($e) { return 'f.opt = ' . "'" . $e . "'"; }, $sol);
	    if (!empty($sol_map))
	      $filters[] = '(' . implode(' or ', $sol_map) . ')';
	  }

	  if ($itc) {
	    $itc_map = array_map(function($e) { return 'f.opt = ' . "'" . $e . "'"; }, $itc);
	    if (!empty($itc_map))
	      $filters[] = '(' . implode(' or ', $itc_map) . ')';
	  }

	  $rs = dataConnection::runQuery(
	    "select distinct tt.* from ttfilter as f inner join teachingtip as tt on f.teachingtip_id = tt.id where status = 'active' and (" . implode(' and ', $filters) . ") order by time desc");

	  $results = array();
	  foreach($rs as $r)
	    $results[] = new teachingtip($r);
	  return $results;
	}
}

class ttcomment
{
	var $id; //primary key
	var $time;
	var $comment;
	var $archived;

	function __construct($asArray=null)
	{
		$this->id = null; //primary key
		$this->time = time();
		$this->comment = "";
		$this->archived = "0";
		if($asArray!==null)
			$this->fromArray($asArray);
	}

	function fromArray($asArray)
	{
		$this->id = $asArray['id'];
		$this->time = dataConnection::db2time($asArray['time']);
		$this->comment = $asArray['comment'];
		$this->archived = $asArray['archived'];
	}

	static function retrieve_ttcomment($id)
	{
		$query = "SELECT * FROM ttcomment WHERE id='".dataConnection::safe($id)."';";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
		{
			return new ttcomment($result[0]);
		}
		else
			return false;
	}

	static function retrieve_ttcomment_matching($field, $value, $from=0, $count=-1, $sort=null)
	{
	    if(preg_replace('/\W/','',$field)!== $field)
	        return false; // not a permitted field name;
	    $query = "SELECT * FROM ttcomment WHERE $field='".dataConnection::safe($value)."'";
	    if(($sort !== null)&&(preg_replace('/\W/','',$sort)!== $sort))
	        $query .= " ORDER BY ".$sort;
	    if(($count != -1)&&(is_int($count))&&(is_int($from)))
	        $query .= " LIMIT ".$count." OFFSET ".$from;
	    $query .= ';';
	    $result = dataConnection::runQuery($query);
	    if(sizeof($result)!=0)
	    {
	        $output = array();
	        foreach($result as $r)
	            $output[] = new ttcomment($r);
	        return $output;
	    }
	    else
	        return false;
	}
	
	function insert()
	{
		//#Any required insert methods for foreign keys need to be called here.
		$query = "INSERT INTO ttcomment(time, comment, archived) VALUES(";
		$query .= "'".dataConnection::time2db($this->time)."', ";
		$query .= "'".dataConnection::safe($this->comment)."', ";
		$query .= "'".dataConnection::safe($this->archived)."');";
		dataConnection::runQuery("BEGIN;");
		$result = dataConnection::runQuery($query);
		$result2 = dataConnection::runQuery("SELECT LAST_INSERT_ID() AS id;");
		dataConnection::runQuery("COMMIT;");
		$this->id = $result2[0]['id'];
		return $this->id;
	}

	function update()
	{
		$query = "UPDATE ttcomment ";
		$query .= "SET time='".dataConnection::time2db($this->time)."' ";
		$query .= ", comment='".dataConnection::safe($this->comment)."' ";
		$query .= ", archived='".dataConnection::safe($this->archived)."' ";
		$query .= "WHERE id='".dataConnection::safe($this->id)."';";
		return dataConnection::runQuery($query);
	}

	static function count($where_name=null, $equals_value=null)
	{
		$query = "SELECT COUNT(*) AS count FROM ttcomment WHERE ";
		if($where_name==null)
			$query .= '1;';
		else
			$query .= "$where_name='".dataConnection::safe($equals_value)."';";
		$result = dataConnection::runQuery($query);
		if($result == false)
			return 0;
		else
			return $result['0']['count'];
	}

	function toXML()
	{
		$out = "<ttcomment>\n";
		$out .= '<id>'.htmlentities($this->id)."</id>\n";
		$out .= '<time>'.htmlentities($this->time)."</time>\n";
		$out .= '<comment>'.htmlentities($this->comment)."</comment>\n";
		$out .= '<archived>'.htmlentities($this->archived)."</archived>\n";
		$out .= "</ttcomment>\n";
		return $out;
	}
	//[[USERCODE_ttcomment]] Put code for custom class members in this block.

	function get_author() {
		$query = "SELECT u.* FROM user_comments_tt as uctt INNER JOIN user as u ON uctt.user_id = u.id INNER JOIN ttcomment as c ON uctt.comment_id = c.id WHERE c.id = '".dataConnection::safe($this->id)."'";
		$result = dataConnection::runQuery($query);
		if (sizeof($result) != 0) return new user($result[0]);
		else return false;
	}

	//[[USERCODE_ttcomment]] WEnd of custom class members.
}

class ttkeyword
{
	var $id; //primary key
	var $ttid_id; //foreign key
	var $keyword;
	var $archived;

	function __construct($asArray=null)
	{
		$this->id = null; //primary key
		$this->ttid_id = null; // foreign key, needs dealt with.
		$this->keyword = "";
		$this->archived = "0";
		if($asArray!==null)
			$this->fromArray($asArray);
	}

	function fromArray($asArray)
	{
		$this->id = $asArray['id'];
		$this->ttid_id = $asArray['ttid_id']; // foreign key, check code
		$this->keyword = $asArray['keyword'];
		$this->archived = $asArray['archived'];
	}

	static function retrieve_ttkeyword($id)
	{
		$query = "SELECT * FROM ttkeyword WHERE id='".dataConnection::safe($id)."';";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
		{
			return new ttkeyword($result[0]);
		}
		else
			return false;
	}

	static function retrieve_ttkeyword_matching($field, $value, $from=0, $count=-1, $sort=null)
	{
	    if(preg_replace('/\W/','',$field)!== $field)
	        return false; // not a permitted field name;
	    $query = "SELECT * FROM ttkeyword WHERE $field='".dataConnection::safe($value)."'";
	    if(($sort !== null)&&(preg_replace('/\W/','',$sort)!== $sort))
	        $query .= " ORDER BY ".$sort;
	    if(($count != -1)&&(is_int($count))&&(is_int($from)))
	        $query .= " LIMIT ".$count." OFFSET ".$from;
	    $query .= ';';
	    $result = dataConnection::runQuery($query);
	    if(sizeof($result)!=0)
	    {
	        $output = array();
	        foreach($result as $r)
	            $output[] = new ttkeyword($r);
	        return $output;
	    }
	    else
	        return false;
	}
	
	function insert()
	{
		//#Any required insert methods for foreign keys need to be called here.
		$query = "INSERT INTO ttkeyword(ttid_id, keyword, archived) VALUES(";
		if($this->ttid_id!==null)
			$query .= "'".dataConnection::safe($this->ttid_id)."', ";
		else
			$query .= "null, ";
		$query .= "'".dataConnection::safe($this->keyword)."', ";
		$query .= "'".dataConnection::safe($this->archived)."');";
		dataConnection::runQuery("BEGIN;");
		$result = dataConnection::runQuery($query);
		$result2 = dataConnection::runQuery("SELECT LAST_INSERT_ID() AS id;");
		dataConnection::runQuery("COMMIT;");
		$this->id = $result2[0]['id'];
		return $this->id;
	}

	function update()
	{
		$query = "UPDATE ttkeyword ";
		$query .= "SET ttid_id='".dataConnection::safe($this->ttid_id)."' ";
		$query .= ", keyword='".dataConnection::safe($this->keyword)."' ";
		$query .= ", archived='".dataConnection::safe($this->archived)."' ";
		$query .= "WHERE id='".dataConnection::safe($this->id)."';";
		return dataConnection::runQuery($query);
	}

	static function count($where_name=null, $equals_value=null)
	{
		$query = "SELECT COUNT(*) AS count FROM ttkeyword WHERE ";
		if($where_name==null)
			$query .= '1;';
		else
			$query .= "$where_name='".dataConnection::safe($equals_value)."';";
		$result = dataConnection::runQuery($query);
		if($result == false)
			return 0;
		else
			return $result['0']['count'];
	}

	function toXML()
	{
		$out = "<ttkeyword>\n";
		$out .= '<id>'.htmlentities($this->id)."</id>\n";
		$out .= '<ttid>'.htmlentities($this->ttid)."</ttid>\n";
		$out .= '<keyword>'.htmlentities($this->keyword)."</keyword>\n";
		$out .= '<archived>'.htmlentities($this->archived)."</archived>\n";
		$out .= "</ttkeyword>\n";
		return $out;
	}
	//[[USERCODE_ttkeyword]] Put code for custom class members in this block.

	public function __toString() {
		return $this->keyword;
	}

	//[[USERCODE_ttkeyword]] WEnd of custom class members.
}

class ttfilter
{
	var $id; //primary key
	var $teachingtip_id; //foreign key
	var $category;
	var $opt;

	function __construct($asArray=null)
	{
		$this->id = null; //primary key
		$this->teachingtip_id = null; // foreign key, needs dealt with.
		$this->category = "";
		$this->opt = "";
		if($asArray!==null)
			$this->fromArray($asArray);
	}

	function fromArray($asArray)
	{
		$this->id = $asArray['id'];
		$this->teachingtip_id = $asArray['teachingtip_id']; // foreign key, check code
		$this->category = $asArray['category'];
		$this->opt = $asArray['opt'];
	}

	static function retrieve_ttfilter($id)
	{
		$query = "SELECT * FROM ttfilter WHERE id='".dataConnection::safe($id)."';";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
		{
			return new ttfilter($result[0]);
		}
		else
			return false;
	}

	static function retrieve_ttfilter_matching($field, $value, $from=0, $count=-1, $sort=null)
	{
	    if(preg_replace('/\W/','',$field)!== $field)
	        return false; // not a permitted field name;
	    $query = "SELECT * FROM ttfilter WHERE $field='".dataConnection::safe($value)."'";
	    if(($sort !== null)&&(preg_replace('/\W/','',$sort)!== $sort))
	        $query .= " ORDER BY ".$sort;
	    if(($count != -1)&&(is_int($count))&&(is_int($from)))
	        $query .= " LIMIT ".$count." OFFSET ".$from;
	    $query .= ';';
	    $result = dataConnection::runQuery($query);
	    if(sizeof($result)!=0)
	    {
	        $output = array();
	        foreach($result as $r)
	            $output[] = new ttfilter($r);
	        return $output;
	    }
	    else
	        return false;
	}
	
	function insert()
	{
		//#Any required insert methods for foreign keys need to be called here.
		$query = "INSERT INTO ttfilter(teachingtip_id, category, opt) VALUES(";
		if($this->teachingtip_id!==null)
			$query .= "'".dataConnection::safe($this->teachingtip_id)."', ";
		else
			$query .= "null, ";
		$query .= "'".dataConnection::safe($this->category)."', ";
		$query .= "'".dataConnection::safe($this->opt)."');";
		dataConnection::runQuery("BEGIN;");
		$result = dataConnection::runQuery($query);
		$result2 = dataConnection::runQuery("SELECT LAST_INSERT_ID() AS id;");
		dataConnection::runQuery("COMMIT;");
		$this->id = $result2[0]['id'];
		return $this->id;
	}

	function update()
	{
		$query = "UPDATE ttfilter ";
		$query .= "SET teachingtip_id='".dataConnection::safe($this->teachingtip_id)."' ";
		$query .= ", category='".dataConnection::safe($this->category)."' ";
		$query .= ", opt='".dataConnection::safe($this->opt)."' ";
		$query .= "WHERE id='".dataConnection::safe($this->id)."';";
		return dataConnection::runQuery($query);
	}

	static function count($where_name=null, $equals_value=null)
	{
		$query = "SELECT COUNT(*) AS count FROM ttfilter WHERE ";
		if($where_name==null)
			$query .= '1;';
		else
			$query .= "$where_name='".dataConnection::safe($equals_value)."';";
		$result = dataConnection::runQuery($query);
		if($result == false)
			return 0;
		else
			return $result['0']['count'];
	}

	function toXML()
	{
		$out = "<ttfilter>\n";
		$out .= '<id>'.htmlentities($this->id)."</id>\n";
		$out .= '<teachingtip>'.htmlentities($this->teachingtip)."</teachingtip>\n";
		$out .= '<category>'.htmlentities($this->category)."</category>\n";
		$out .= '<opt>'.htmlentities($this->opt)."</opt>\n";
		$out .= "</ttfilter>\n";
		return $out;
	}
	//[[USERCODE_ttfilter]] Put code for custom class members in this block.

	//[[USERCODE_ttfilter]] WEnd of custom class members.
}

class ttview
{
	var $id; //primary key
	var $teachingtip_id; //foreign key
	var $user_id; //foreign key
	var $time;

	function __construct($asArray=null)
	{
		$this->id = null; //primary key
		$this->teachingtip_id = null; // foreign key, needs dealt with.
		$this->user_id = null; // foreign key, needs dealt with.
		$this->time = time();
		if($asArray!==null)
			$this->fromArray($asArray);
	}

	function fromArray($asArray)
	{
		$this->id = $asArray['id'];
		$this->teachingtip_id = $asArray['teachingtip_id']; // foreign key, check code
		$this->user_id = $asArray['user_id']; // foreign key, check code
		$this->time = dataConnection::db2time($asArray['time']);
	}

	static function retrieve_ttview($id)
	{
		$query = "SELECT * FROM ttview WHERE id='".dataConnection::safe($id)."';";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
		{
			return new ttview($result[0]);
		}
		else
			return false;
	}

	static function retrieve_ttview_matching($field, $value, $from=0, $count=-1, $sort=null)
	{
	    if(preg_replace('/\W/','',$field)!== $field)
	        return false; // not a permitted field name;
	    $query = "SELECT * FROM ttview WHERE $field='".dataConnection::safe($value)."'";
	    if(($sort !== null)&&(preg_replace('/\W/','',$sort)!== $sort))
	        $query .= " ORDER BY ".$sort;
	    if(($count != -1)&&(is_int($count))&&(is_int($from)))
	        $query .= " LIMIT ".$count." OFFSET ".$from;
	    $query .= ';';
	    $result = dataConnection::runQuery($query);
	    if(sizeof($result)!=0)
	    {
	        $output = array();
	        foreach($result as $r)
	            $output[] = new ttview($r);
	        return $output;
	    }
	    else
	        return false;
	}
	
	function insert()
	{
		//#Any required insert methods for foreign keys need to be called here.
		$query = "INSERT INTO ttview(teachingtip_id, user_id, time) VALUES(";
		if($this->teachingtip_id!==null)
			$query .= "'".dataConnection::safe($this->teachingtip_id)."', ";
		else
			$query .= "null, ";
		if($this->user_id!==null)
			$query .= "'".dataConnection::safe($this->user_id)."', ";
		else
			$query .= "null, ";
		$query .= "'".dataConnection::time2db($this->time)."');";
		dataConnection::runQuery("BEGIN;");
		$result = dataConnection::runQuery($query);
		$result2 = dataConnection::runQuery("SELECT LAST_INSERT_ID() AS id;");
		dataConnection::runQuery("COMMIT;");
		$this->id = $result2[0]['id'];
		return $this->id;
	}

	function update()
	{
		$query = "UPDATE ttview ";
		$query .= "SET teachingtip_id='".dataConnection::safe($this->teachingtip_id)."' ";
		$query .= ", user_id='".dataConnection::safe($this->user_id)."' ";
		$query .= ", time='".dataConnection::time2db($this->time)."' ";
		$query .= "WHERE id='".dataConnection::safe($this->id)."';";
		return dataConnection::runQuery($query);
	}

	static function count($where_name=null, $equals_value=null)
	{
		$query = "SELECT COUNT(*) AS count FROM ttview WHERE ";
		if($where_name==null)
			$query .= '1;';
		else
			$query .= "$where_name='".dataConnection::safe($equals_value)."';";
		$result = dataConnection::runQuery($query);
		if($result == false)
			return 0;
		else
			return $result['0']['count'];
	}

	function toXML()
	{
		$out = "<ttview>\n";
		$out .= '<id>'.htmlentities($this->id)."</id>\n";
		$out .= '<teachingtip>'.htmlentities($this->teachingtip)."</teachingtip>\n";
		$out .= '<user>'.htmlentities($this->user)."</user>\n";
		$out .= '<time>'.htmlentities($this->time)."</time>\n";
		$out .= "</ttview>\n";
		return $out;
	}
	//[[USERCODE_ttview]] Put code for custom class members in this block.

	// check is $userID has already seen the specified TT
	static function check_viewed($userID, $ttID) {
		$query = "SELECT COUNT(*) as count FROM ttview WHERE user_id = '" . dataConnection::safe($userID) . "' AND teachingtip_id = '" . dataConnection::safe($ttID) . "'";
		$result = dataConnection::runQuery($query);
		return ($result[0]['count'] > 0) ? true : false;
	}

	//[[USERCODE_ttview]] WEnd of custom class members.
}

class contributors
{
	var $id; //primary key
	var $user_id; //foreign key
	var $teachingtip_id; //foreign key
	var $email;
	var $seen;

	function __construct($asArray=null)
	{
		$this->id = null; //primary key
		$this->user_id = null; // foreign key, needs dealt with.
		$this->teachingtip_id = null; // foreign key, needs dealt with.
		$this->email = "";
		$this->seen = "0";
		if($asArray!==null)
			$this->fromArray($asArray);
	}

	function fromArray($asArray)
	{
		$this->id = $asArray['id'];
		$this->user_id = $asArray['user_id']; // foreign key, check code
		$this->teachingtip_id = $asArray['teachingtip_id']; // foreign key, check code
		$this->email = $asArray['email'];
		$this->seen = $asArray['seen'];
	}

	static function retrieve_contributors($id)
	{
		$query = "SELECT * FROM contributors WHERE id='".dataConnection::safe($id)."';";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
		{
			return new contributors($result[0]);
		}
		else
			return false;
	}

	static function retrieve_contributors_matching($field, $value, $from=0, $count=-1, $sort=null)
	{
	    if(preg_replace('/\W/','',$field)!== $field)
	        return false; // not a permitted field name;
	    $query = "SELECT * FROM contributors WHERE $field='".dataConnection::safe($value)."'";
	    if(($sort !== null)&&(preg_replace('/\W/','',$sort)!== $sort))
	        $query .= " ORDER BY ".$sort;
	    if(($count != -1)&&(is_int($count))&&(is_int($from)))
	        $query .= " LIMIT ".$count." OFFSET ".$from;
	    $query .= ';';
	    $result = dataConnection::runQuery($query);
	    $output = array();
	    foreach($result as $r)
	      $output[] = new contributors($r);
	    return $output;
	}
	
	function insert()
	{
		//#Any required insert methods for foreign keys need to be called here.
		$query = "INSERT INTO contributors(user_id, teachingtip_id, email, seen) VALUES(";
		if($this->user_id!==null)
			$query .= "'".dataConnection::safe($this->user_id)."', ";
		else
			$query .= "null, ";
		if($this->teachingtip_id!==null)
			$query .= "'".dataConnection::safe($this->teachingtip_id)."', ";
		else
			$query .= "null, ";
		$query .= "'".dataConnection::safe($this->email)."', ";
		$query .= "'".dataConnection::safe($this->seen)."');";
		dataConnection::runQuery("BEGIN;");
		$result = dataConnection::runQuery($query);
		$result2 = dataConnection::runQuery("SELECT LAST_INSERT_ID() AS id;");
		dataConnection::runQuery("COMMIT;");
		$this->id = $result2[0]['id'];
		return $this->id;
	}

	function update()
	{
		$query = "UPDATE contributors ";
		$query .= "SET user_id='".dataConnection::safe($this->user_id)."' ";
		$query .= ", teachingtip_id='".dataConnection::safe($this->teachingtip_id)."' ";
		$query .= ", email='".dataConnection::safe($this->email)."' ";
		$query .= ", seen='".dataConnection::safe($this->seen)."' ";
		$query .= "WHERE id='".dataConnection::safe($this->id)."';";
		return dataConnection::runQuery($query);
	}

	static function count($where_name=null, $equals_value=null)
	{
		$query = "SELECT COUNT(*) AS count FROM contributors WHERE ";
		if($where_name==null)
			$query .= '1;';
		else
			$query .= "$where_name='".dataConnection::safe($equals_value)."';";
		$result = dataConnection::runQuery($query);
		if($result == false)
			return 0;
		else
			return $result['0']['count'];
	}

	function toXML()
	{
		$out = "<contributors>\n";
		$out .= '<id>'.htmlentities($this->id)."</id>\n";
		$out .= '<user>'.htmlentities($this->user)."</user>\n";
		$out .= '<teachingtip>'.htmlentities($this->teachingtip)."</teachingtip>\n";
		$out .= '<email>'.htmlentities($this->email)."</email>\n";
		$out .= '<seen>'.htmlentities($this->seen)."</seen>\n";
		$out .= "</contributors>\n";
		return $out;
	}
	//[[USERCODE_contributors]] Put code for custom class members in this block.

	//[[USERCODE_contributors]] WEnd of custom class members.
}

class user_comments_tt
{
	var $id; //primary key
	var $user_id; //foreign key
	var $teachingtip_id; //foreign key
	var $comment_id; //foreign key
	var $time;
	var $archived;

	function __construct($asArray=null)
	{
		$this->id = null; //primary key
		$this->user_id = null; // foreign key, needs dealt with.
		$this->teachingtip_id = null; // foreign key, needs dealt with.
		$this->comment_id = null; // foreign key, needs dealt with.
		$this->time = time();
		$this->archived = "0";
		if($asArray!==null)
			$this->fromArray($asArray);
	}

	function fromArray($asArray)
	{
		$this->id = $asArray['id'];
		$this->user_id = $asArray['user_id']; // foreign key, check code
		$this->teachingtip_id = $asArray['teachingtip_id']; // foreign key, check code
		$this->comment_id = $asArray['comment_id']; // foreign key, check code
		$this->time = dataConnection::db2time($asArray['time']);
		$this->archived = $asArray['archived'];
	}

	static function retrieve_user_comments_tt($id)
	{
		$query = "SELECT * FROM user_comments_tt WHERE id='".dataConnection::safe($id)."';";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
		{
			return new user_comments_tt($result[0]);
		}
		else
			return false;
	}

	static function retrieve_user_comments_tt_matching($field, $value, $from=0, $count=-1, $sort=null)
	{
	    if(preg_replace('/\W/','',$field)!== $field)
	        return false; // not a permitted field name;
	    $query = "SELECT * FROM user_comments_tt WHERE $field='".dataConnection::safe($value)."'";
	    if(($sort !== null)&&(preg_replace('/\W/','',$sort)!== $sort))
	        $query .= " ORDER BY ".$sort;
	    if(($count != -1)&&(is_int($count))&&(is_int($from)))
	        $query .= " LIMIT ".$count." OFFSET ".$from;
	    $query .= ';';
	    $result = dataConnection::runQuery($query);
	    if(sizeof($result)!=0)
	    {
	        $output = array();
	        foreach($result as $r)
	            $output[] = new user_comments_tt($r);
	        return $output;
	    }
	    else
	        return false;
	}
	
	function insert()
	{
		//#Any required insert methods for foreign keys need to be called here.
		$query = "INSERT INTO user_comments_tt(user_id, teachingtip_id, comment_id, time, archived) VALUES(";
		if($this->user_id!==null)
			$query .= "'".dataConnection::safe($this->user_id)."', ";
		else
			$query .= "null, ";
		if($this->teachingtip_id!==null)
			$query .= "'".dataConnection::safe($this->teachingtip_id)."', ";
		else
			$query .= "null, ";
		if($this->comment_id!==null)
			$query .= "'".dataConnection::safe($this->comment_id)."', ";
		else
			$query .= "null, ";
		$query .= "'".dataConnection::time2db($this->time)."', ";
		$query .= "'".dataConnection::safe($this->archived)."');";
		dataConnection::runQuery("BEGIN;");
		$result = dataConnection::runQuery($query);
		$result2 = dataConnection::runQuery("SELECT LAST_INSERT_ID() AS id;");
		dataConnection::runQuery("COMMIT;");
		$this->id = $result2[0]['id'];
		return $this->id;
	}

	function update()
	{
		$query = "UPDATE user_comments_tt ";
		$query .= "SET user_id='".dataConnection::safe($this->user_id)."' ";
		$query .= ", teachingtip_id='".dataConnection::safe($this->teachingtip_id)."' ";
		$query .= ", comment_id='".dataConnection::safe($this->comment_id)."' ";
		$query .= ", time='".dataConnection::time2db($this->time)."' ";
		$query .= ", archived='".dataConnection::safe($this->archived)."' ";
		$query .= "WHERE id='".dataConnection::safe($this->id)."';";
		return dataConnection::runQuery($query);
	}

	static function count($where_name=null, $equals_value=null)
	{
		$query = "SELECT COUNT(*) AS count FROM user_comments_tt WHERE ";
		if($where_name==null)
			$query .= '1;';
		else
			$query .= "$where_name='".dataConnection::safe($equals_value)."';";
		$result = dataConnection::runQuery($query);
		if($result == false)
			return 0;
		else
			return $result['0']['count'];
	}

	function toXML()
	{
		$out = "<user_comments_tt>\n";
		$out .= '<id>'.htmlentities($this->id)."</id>\n";
		$out .= '<user>'.htmlentities($this->user)."</user>\n";
		$out .= '<teachingtip>'.htmlentities($this->teachingtip)."</teachingtip>\n";
		$out .= '<comment>'.htmlentities($this->comment)."</comment>\n";
		$out .= '<time>'.htmlentities($this->time)."</time>\n";
		$out .= '<archived>'.htmlentities($this->archived)."</archived>\n";
		$out .= "</user_comments_tt>\n";
		return $out;
	}
	//[[USERCODE_user_comments_tt]] Put code for custom class members in this block.

	static function retrieve_user_comments_tt_teachingtip($ttId,$userId){
		$query = "SELECT * FROM user_comments_tt WHERE teachingtip_id='".dataConnection::safe($ttId)."'AND user_id='".dataConnection::safe($userId)."'";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
		{
			return new user_comments_tt($result[0]);
		}
		else
			return false;
	}

	//[[USERCODE_user_comments_tt]] WEnd of custom class members.
}

class user_likes_tt
{
	var $id; //primary key
	var $user_id; //foreign key
	var $teachingtip_id; //foreign key
	var $time;
	var $archived;

	function __construct($asArray=null)
	{
		$this->id = null; //primary key
		$this->user_id = null; // foreign key, needs dealt with.
		$this->teachingtip_id = null; // foreign key, needs dealt with.
		$this->time = time();
		$this->archived = "0";
		if($asArray!==null)
			$this->fromArray($asArray);
	}

	function fromArray($asArray)
	{
		$this->id = $asArray['id'];
		$this->user_id = $asArray['user_id']; // foreign key, check code
		$this->teachingtip_id = $asArray['teachingtip_id']; // foreign key, check code
		$this->time = dataConnection::db2time($asArray['time']);
		$this->archived = $asArray['archived'];
	}

	static function retrieve_user_likes_tt($id)
	{
		$query = "SELECT * FROM user_likes_tt WHERE id='".dataConnection::safe($id)."';";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
		{
			return new user_likes_tt($result[0]);
		}
		else
			return false;
	}

	static function retrieve_user_likes_tt_matching($field, $value, $from=0, $count=-1, $sort=null)
	{
	    if(preg_replace('/\W/','',$field)!== $field)
	        return false; // not a permitted field name;
	    $query = "SELECT * FROM user_likes_tt WHERE $field='".dataConnection::safe($value)."'";
	    if(($sort !== null)&&(preg_replace('/\W/','',$sort)!== $sort))
	        $query .= " ORDER BY ".$sort;
	    if(($count != -1)&&(is_int($count))&&(is_int($from)))
	        $query .= " LIMIT ".$count." OFFSET ".$from;
	    $query .= ';';
	    $result = dataConnection::runQuery($query);
	    if(sizeof($result)!=0)
	    {
	        $output = array();
	        foreach($result as $r)
	            $output[] = new user_likes_tt($r);
	        return $output;
	    }
	    else
	        return false;
	}
	
	function insert()
	{
		//#Any required insert methods for foreign keys need to be called here.
		$query = "INSERT INTO user_likes_tt(user_id, teachingtip_id, time, archived) VALUES(";
		if($this->user_id!==null)
			$query .= "'".dataConnection::safe($this->user_id)."', ";
		else
			$query .= "null, ";
		if($this->teachingtip_id!==null)
			$query .= "'".dataConnection::safe($this->teachingtip_id)."', ";
		else
			$query .= "null, ";
		$query .= "'".dataConnection::time2db($this->time)."', ";
		$query .= "'".dataConnection::safe($this->archived)."');";
		dataConnection::runQuery("BEGIN;");
		$result = dataConnection::runQuery($query);
		$result2 = dataConnection::runQuery("SELECT LAST_INSERT_ID() AS id;");
		dataConnection::runQuery("COMMIT;");
		$this->id = $result2[0]['id'];
		return $this->id;
	}

	function update()
	{
		$query = "UPDATE user_likes_tt ";
		$query .= "SET user_id='".dataConnection::safe($this->user_id)."' ";
		$query .= ", teachingtip_id='".dataConnection::safe($this->teachingtip_id)."' ";
		$query .= ", time='".dataConnection::time2db($this->time)."' ";
		$query .= ", archived='".dataConnection::safe($this->archived)."' ";
		$query .= "WHERE id='".dataConnection::safe($this->id)."';";
		return dataConnection::runQuery($query);
	}

	static function count($where_name=null, $equals_value=null)
	{
		$query = "SELECT COUNT(*) AS count FROM user_likes_tt WHERE ";
		if($where_name==null)
			$query .= '1;';
		else
			$query .= "$where_name='".dataConnection::safe($equals_value)."';";
		$result = dataConnection::runQuery($query);
		if($result == false)
			return 0;
		else
			return $result['0']['count'];
	}

	function toXML()
	{
		$out = "<user_likes_tt>\n";
		$out .= '<id>'.htmlentities($this->id)."</id>\n";
		$out .= '<user>'.htmlentities($this->user)."</user>\n";
		$out .= '<teachingtip>'.htmlentities($this->teachingtip)."</teachingtip>\n";
		$out .= '<time>'.htmlentities($this->time)."</time>\n";
		$out .= '<archived>'.htmlentities($this->archived)."</archived>\n";
		$out .= "</user_likes_tt>\n";
		return $out;
	}
	//[[USERCODE_user_likes_tt]] Put code for custom class members in this block.

	static function retrieve_user_likes_tt_teachingtip($ttId,$userId){
		$query = "SELECT * FROM user_likes_tt WHERE teachingtip_id='".dataConnection::safe($ttId)."'AND user_id='".dataConnection::safe($userId)."'";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
		{
			return new user_likes_tt($result[0]);
		}
		else
			return false;
	}

	//[[USERCODE_user_likes_tt]] WEnd of custom class members.
}

class user_shares_tt
{
	var $id; //primary key
	var $sender;
	var $recipient;
	var $teachingtip_id; //foreign key
	var $time;
	var $message;
	var $archived;

	function __construct($asArray=null)
	{
		$this->id = null; //primary key
		$this->sender = "";
		$this->recipient = "";
		$this->teachingtip_id = null; // foreign key, needs dealt with.
		$this->time = time();
		$this->message = "";
		$this->archived = "0";
		if($asArray!==null)
			$this->fromArray($asArray);
	}

	function fromArray($asArray)
	{
		$this->id = $asArray['id'];
		$this->sender = $asArray['sender'];
		$this->recipient = $asArray['recipient'];
		$this->teachingtip_id = $asArray['teachingtip_id']; // foreign key, check code
		$this->time = dataConnection::db2time($asArray['time']);
		$this->message = $asArray['message'];
		$this->archived = $asArray['archived'];
	}

	static function retrieve_user_shares_tt($id)
	{
		$query = "SELECT * FROM user_shares_tt WHERE id='".dataConnection::safe($id)."';";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
		{
			return new user_shares_tt($result[0]);
		}
		else
			return false;
	}

	static function retrieve_user_shares_tt_matching($field, $value, $from=0, $count=-1, $sort=null)
	{
	    if(preg_replace('/\W/','',$field)!== $field)
	        return false; // not a permitted field name;
	    $query = "SELECT * FROM user_shares_tt WHERE $field='".dataConnection::safe($value)."'";
	    if(($sort !== null)&&(preg_replace('/\W/','',$sort)!== $sort))
	        $query .= " ORDER BY ".$sort;
	    if(($count != -1)&&(is_int($count))&&(is_int($from)))
	        $query .= " LIMIT ".$count." OFFSET ".$from;
	    $query .= ';';
	    $result = dataConnection::runQuery($query);
	    if(sizeof($result)!=0)
	    {
	        $output = array();
	        foreach($result as $r)
	            $output[] = new user_shares_tt($r);
	        return $output;
	    }
	    else
	        return false;
	}
	
	function insert()
	{
		//#Any required insert methods for foreign keys need to be called here.
		$query = "INSERT INTO user_shares_tt(sender, recipient, teachingtip_id, time, message, archived) VALUES(";
		$query .= "'".dataConnection::safe($this->sender)."', ";
		$query .= "'".dataConnection::safe($this->recipient)."', ";
		if($this->teachingtip_id!==null)
			$query .= "'".dataConnection::safe($this->teachingtip_id)."', ";
		else
			$query .= "null, ";
		$query .= "'".dataConnection::time2db($this->time)."', ";
		$query .= "'".dataConnection::safe($this->message)."', ";
		$query .= "'".dataConnection::safe($this->archived)."');";
		dataConnection::runQuery("BEGIN;");
		$result = dataConnection::runQuery($query);
		$result2 = dataConnection::runQuery("SELECT LAST_INSERT_ID() AS id;");
		dataConnection::runQuery("COMMIT;");
		$this->id = $result2[0]['id'];
		return $this->id;
	}

	function update()
	{
		$query = "UPDATE user_shares_tt ";
		$query .= "SET sender='".dataConnection::safe($this->sender)."' ";
		$query .= ", recipient='".dataConnection::safe($this->recipient)."' ";
		$query .= ", teachingtip_id='".dataConnection::safe($this->teachingtip_id)."' ";
		$query .= ", time='".dataConnection::time2db($this->time)."' ";
		$query .= ", message='".dataConnection::safe($this->message)."' ";
		$query .= ", archived='".dataConnection::safe($this->archived)."' ";
		$query .= "WHERE id='".dataConnection::safe($this->id)."';";
		return dataConnection::runQuery($query);
	}

	static function count($where_name=null, $equals_value=null)
	{
		$query = "SELECT COUNT(*) AS count FROM user_shares_tt WHERE ";
		if($where_name==null)
			$query .= '1;';
		else
			$query .= "$where_name='".dataConnection::safe($equals_value)."';";
		$result = dataConnection::runQuery($query);
		if($result == false)
			return 0;
		else
			return $result['0']['count'];
	}

	function toXML()
	{
		$out = "<user_shares_tt>\n";
		$out .= '<id>'.htmlentities($this->id)."</id>\n";
		$out .= '<sender>'.htmlentities($this->sender)."</sender>\n";
		$out .= '<recipient>'.htmlentities($this->recipient)."</recipient>\n";
		$out .= '<teachingtip>'.htmlentities($this->teachingtip)."</teachingtip>\n";
		$out .= '<time>'.htmlentities($this->time)."</time>\n";
		$out .= '<message>'.htmlentities($this->message)."</message>\n";
		$out .= '<archived>'.htmlentities($this->archived)."</archived>\n";
		$out .= "</user_shares_tt>\n";
		return $out;
	}
	//[[USERCODE_user_shares_tt]] Put code for custom class members in this block.

	static function retrieve_user_shares_tt_teachingtip($ttId,$userId){
		$query = "SELECT * FROM user_shares_tt WHERE teachingtip_id='".dataConnection::safe($ttId)."'AND sender_id='".dataConnection::safe($userId)."'";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
		{
			return new user_shares_tt($result[0]);
		}
		else
			return false;
	}

	//[[USERCODE_user_shares_tt]] WEnd of custom class members.
}

class user_follows_user
{
	var $id; //primary key
	var $follower_id; //foreign key
	var $user_id; //foreign key
	var $time;

	function __construct($asArray=null)
	{
		$this->id = null; //primary key
		$this->follower_id = null; // foreign key, needs dealt with.
		$this->user_id = null; // foreign key, needs dealt with.
		$this->time = time();
		if($asArray!==null)
			$this->fromArray($asArray);
	}

	function fromArray($asArray)
	{
		$this->id = $asArray['id'];
		$this->follower_id = $asArray['follower_id']; // foreign key, check code
		$this->user_id = $asArray['user_id']; // foreign key, check code
		$this->time = dataConnection::db2time($asArray['time']);
	}

	static function retrieve_user_follows_user($id)
	{
		$query = "SELECT * FROM user_follows_user WHERE id='".dataConnection::safe($id)."';";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
		{
			return new user_follows_user($result[0]);
		}
		else
			return false;
	}

	static function retrieve_user_follows_user_matching($field, $value, $from=0, $count=-1, $sort=null)
	{
	    if(preg_replace('/\W/','',$field)!== $field)
	        return false; // not a permitted field name;
	    $query = "SELECT * FROM user_follows_user WHERE $field='".dataConnection::safe($value)."'";
	    if(($sort !== null)&&(preg_replace('/\W/','',$sort)!== $sort))
	        $query .= " ORDER BY ".$sort;
	    if(($count != -1)&&(is_int($count))&&(is_int($from)))
	        $query .= " LIMIT ".$count." OFFSET ".$from;
	    $query .= ';';
	    $result = dataConnection::runQuery($query);
	    if(sizeof($result)!=0)
	    {
	        $output = array();
	        foreach($result as $r)
	            $output[] = new user_follows_user($r);
	        return $output;
	    }
	    else
	        return false;
	}
	
	function insert()
	{
		//#Any required insert methods for foreign keys need to be called here.
		$query = "INSERT INTO user_follows_user(follower_id, user_id, time) VALUES(";
		if($this->follower_id!==null)
			$query .= "'".dataConnection::safe($this->follower_id)."', ";
		else
			$query .= "null, ";
		if($this->user_id!==null)
			$query .= "'".dataConnection::safe($this->user_id)."', ";
		else
			$query .= "null, ";
		$query .= "'".dataConnection::time2db($this->time)."');";
		dataConnection::runQuery("BEGIN;");
		$result = dataConnection::runQuery($query);
		$result2 = dataConnection::runQuery("SELECT LAST_INSERT_ID() AS id;");
		dataConnection::runQuery("COMMIT;");
		$this->id = $result2[0]['id'];
		return $this->id;
	}

	function update()
	{
		$query = "UPDATE user_follows_user ";
		$query .= "SET follower_id='".dataConnection::safe($this->follower_id)."' ";
		$query .= ", user_id='".dataConnection::safe($this->user_id)."' ";
		$query .= ", time='".dataConnection::time2db($this->time)."' ";
		$query .= "WHERE id='".dataConnection::safe($this->id)."';";
		return dataConnection::runQuery($query);
	}

	static function count($where_name=null, $equals_value=null)
	{
		$query = "SELECT COUNT(*) AS count FROM user_follows_user WHERE ";
		if($where_name==null)
			$query .= '1;';
		else
			$query .= "$where_name='".dataConnection::safe($equals_value)."';";
		$result = dataConnection::runQuery($query);
		if($result == false)
			return 0;
		else
			return $result['0']['count'];
	}

	function toXML()
	{
		$out = "<user_follows_user>\n";
		$out .= '<id>'.htmlentities($this->id)."</id>\n";
		$out .= '<follower>'.htmlentities($this->follower)."</follower>\n";
		$out .= '<user>'.htmlentities($this->user)."</user>\n";
		$out .= '<time>'.htmlentities($this->time)."</time>\n";
		$out .= "</user_follows_user>\n";
		return $out;
	}
	//[[USERCODE_user_follows_user]] Put code for custom class members in this block.

	//[[USERCODE_user_follows_user]] WEnd of custom class members.
}

class notification
{
	var $id; //primary key
	var $user_id; //foreign key
	var $activity_id;
	var $activity_type;
	var $category;
	var $time;
	var $seen;

	function __construct($asArray=null)
	{
		$this->id = null; //primary key
		$this->user_id = null; // foreign key, needs dealt with.
		$this->activity_id = "0";
		$this->activity_type = "";
		$this->category = "";
		$this->time = time();
		$this->seen = "0";
		if($asArray!==null)
			$this->fromArray($asArray);
	}

	function fromArray($asArray)
	{
		$this->id = $asArray['id'];
		$this->user_id = $asArray['user_id']; // foreign key, check code
		$this->activity_id = $asArray['activity_id'];
		$this->activity_type = $asArray['activity_type'];
		$this->category = $asArray['category'];
		$this->time = dataConnection::db2time($asArray['time']);
		$this->seen = $asArray['seen'];
	}

	static function retrieve_notification($id)
	{
		$query = "SELECT * FROM notification WHERE id='".dataConnection::safe($id)."';";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
		{
			return new notification($result[0]);
		}
		else
			return false;
	}

	static function retrieve_notification_matching($field, $value, $from=0, $count=-1, $sort=null)
	{
	    if(preg_replace('/\W/','',$field)!== $field)
	        return false; // not a permitted field name;
	    $query = "SELECT * FROM notification WHERE $field='".dataConnection::safe($value)."'";
	    if(($sort !== null)&&(preg_replace('/\W/','',$sort)!== $sort))
	        $query .= " ORDER BY ".$sort;
	    if(($count != -1)&&(is_int($count))&&(is_int($from)))
	        $query .= " LIMIT ".$count." OFFSET ".$from;
	    $query .= ';';
	    $result = dataConnection::runQuery($query);
	    if(sizeof($result)!=0)
	    {
	        $output = array();
	        foreach($result as $r)
	            $output[] = new notification($r);
	        return $output;
	    }
	    else
	        return false;
	}
	
	function insert()
	{
		//#Any required insert methods for foreign keys need to be called here.
		$query = "INSERT INTO notification(user_id, activity_id, activity_type, category, time, seen) VALUES(";
		if($this->user_id!==null)
			$query .= "'".dataConnection::safe($this->user_id)."', ";
		else
			$query .= "null, ";
		$query .= "'".dataConnection::safe($this->activity_id)."', ";
		$query .= "'".dataConnection::safe($this->activity_type)."', ";
		$query .= "'".dataConnection::safe($this->category)."', ";
		$query .= "'".dataConnection::time2db($this->time)."', ";
		$query .= "'".dataConnection::safe($this->seen)."');";
		dataConnection::runQuery("BEGIN;");
		$result = dataConnection::runQuery($query);
		$result2 = dataConnection::runQuery("SELECT LAST_INSERT_ID() AS id;");
		dataConnection::runQuery("COMMIT;");
		$this->id = $result2[0]['id'];
		return $this->id;
	}

	function update()
	{
		$query = "UPDATE notification ";
		$query .= "SET user_id='".dataConnection::safe($this->user_id)."' ";
		$query .= ", activity_id='".dataConnection::safe($this->activity_id)."' ";
		$query .= ", activity_type='".dataConnection::safe($this->activity_type)."' ";
		$query .= ", category='".dataConnection::safe($this->category)."' ";
		$query .= ", time='".dataConnection::time2db($this->time)."' ";
		$query .= ", seen='".dataConnection::safe($this->seen)."' ";
		$query .= "WHERE id='".dataConnection::safe($this->id)."';";
		return dataConnection::runQuery($query);
	}

	static function count($where_name=null, $equals_value=null)
	{
		$query = "SELECT COUNT(*) AS count FROM notification WHERE ";
		if($where_name==null)
			$query .= '1;';
		else
			$query .= "$where_name='".dataConnection::safe($equals_value)."';";
		$result = dataConnection::runQuery($query);
		if($result == false)
			return 0;
		else
			return $result['0']['count'];
	}

	function toXML()
	{
		$out = "<notification>\n";
		$out .= '<id>'.htmlentities($this->id)."</id>\n";
		$out .= '<user>'.htmlentities($this->user)."</user>\n";
		$out .= '<activity_id>'.htmlentities($this->activity_id)."</activity_id>\n";
		$out .= '<activity_type>'.htmlentities($this->activity_type)."</activity_type>\n";
		$out .= '<category>'.htmlentities($this->category)."</category>\n";
		$out .= '<time>'.htmlentities($this->time)."</time>\n";
		$out .= '<seen>'.htmlentities($this->seen)."</seen>\n";
		$out .= "</notification>\n";
		return $out;
	}
	//[[USERCODE_notification]] Put code for custom class members in this block.

	static function getNotifications($userID, $limit = false, $seen = 2, $start = false) {
	  $query = "select * from notification where user_id = $userID";
	  if ($seen == 0) $query .= " and seen = 0 ";
	  if ($seen == 1) $query .= " and seen = 1 ";
	  $query .= " order by id desc ";
	  if ($limit) $query .= " limit ". dataConnection::safe($limit);
	  if ($start) $query .= " offset " . dataConnection::safe($start);
	  $query .= " ;";
	  $result = dataConnection::runQuery($query);
	  $notifications = array();
	  foreach($result as $r)
	    array_push($notifications, new notification($r));
	  return $notifications;
	}

	//[[USERCODE_notification]] WEnd of custom class members.
}
