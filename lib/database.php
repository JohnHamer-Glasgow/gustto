<?php
require_once(__DIR__.'/../corelib/dataaccess.php');

function initializeDataBase_()
{
	$query = "CREATE TABLE user(id INTEGER PRIMARY KEY AUTO_INCREMENT, name VARCHAR(30), lastname VARCHAR(30), phonenumber VARCHAR(20), username VARCHAR(20), email VARCHAR(50), profile_picture VARCHAR(50), college VARCHAR(50), school VARCHAR(50), esteem INTEGER, engagement INTEGER, lastaccess DATE, joindate DATE, last_visit DATETIME, isadmin INTEGER, FULLTEXT(name,lastname)) ENGINE=MyISAM;";
	dataConnection::runQuery($query);
	$query = "CREATE TABLE user_settings(id INTEGER PRIMARY KEY AUTO_INCREMENT, user_id INTEGER, school_posts INTEGER, tts_activity INTEGER, followers_posts INTEGER, awards INTEGER) ENGINE=MyISAM;";
	dataConnection::runQuery($query);
	$query = "CREATE TABLE admin(id INTEGER PRIMARY KEY AUTO_INCREMENT, role VARCHAR(20)) ENGINE=MyISAM;";
	dataConnection::runQuery($query);
	$query = "CREATE TABLE admin_settings(id INTEGER PRIMARY KEY AUTO_INCREMENT, esteem_like INTEGER, esteem_comment INTEGER, esteem_share INTEGER, esteem_view INTEGER, esteem_follow INTEGER, engagement_like INTEGER, engagement_comment INTEGER, engagement_share INTEGER, engagement_view INTEGER, engagement_follow INTEGER, log_actions INTEGER) ENGINE=MyISAM;";
	dataConnection::runQuery($query);
	$query = "CREATE TABLE award(id INTEGER PRIMARY KEY AUTO_INCREMENT, name VARCHAR(40), url VARCHAR(50), category VARCHAR(20), type VARCHAR(20), rank INTEGER, about VARCHAR(128)) ENGINE=MyISAM;";
	dataConnection::runQuery($query);
	$query = "CREATE TABLE user_earns_award(id INTEGER PRIMARY KEY AUTO_INCREMENT, user_id INTEGER, award_id INTEGER, time DATETIME, promoted INTEGER) ENGINE=MyISAM;";
	dataConnection::runQuery($query);
	$query = "CREATE TABLE teachingtip(id INTEGER PRIMARY KEY AUTO_INCREMENT, author_id INTEGER, title VARCHAR(128), time DATETIME, rationale TEXT, description TEXT, practice TEXT, worksbetter TEXT, doesntworkunless TEXT, essence TEXT, archived INTEGER, draft INTEGER, FULLTEXT(title,rationale,description,practice,worksbetter,doesntworkunless,essence)) ENGINE=MyISAM;";
	dataConnection::runQuery($query);
	$query = "CREATE TABLE ttcomment(id INTEGER PRIMARY KEY AUTO_INCREMENT, time DATETIME, comment TEXT, archived INTEGER) ENGINE=MyISAM;";
	dataConnection::runQuery($query);
	$query = "CREATE TABLE ttkeyword(id INTEGER PRIMARY KEY AUTO_INCREMENT, ttid_id INTEGER, keyword VARCHAR(30), archived INTEGER, FULLTEXT(keyword)) ENGINE=MyISAM;";
	dataConnection::runQuery($query);
	$query = "CREATE TABLE ttfilter(id INTEGER PRIMARY KEY AUTO_INCREMENT, teachingtip_id INTEGER, category VARCHAR(30), opt VARCHAR(30)) ENGINE=MyISAM;";
	dataConnection::runQuery($query);
	$query = "CREATE TABLE ttview(id INTEGER PRIMARY KEY AUTO_INCREMENT, teachingtip_id INTEGER, user_id INTEGER, time DATETIME) ENGINE=MyISAM;";
	dataConnection::runQuery($query);
	$query = "CREATE TABLE contributors(id INTEGER PRIMARY KEY AUTO_INCREMENT, user_id INTEGER, teachingtip_id INTEGER, email VARCHAR(50), seen INTEGER) ENGINE=MyISAM;";
	dataConnection::runQuery($query);
	$query = "CREATE TABLE user_comments_tt(id INTEGER PRIMARY KEY AUTO_INCREMENT, user_id INTEGER, teachingtip_id INTEGER, comment_id INTEGER, time DATETIME, archived INTEGER) ENGINE=MyISAM;";
	dataConnection::runQuery($query);
	$query = "CREATE TABLE user_likes_tt(id INTEGER PRIMARY KEY AUTO_INCREMENT, user_id INTEGER, teachingtip_id INTEGER, time DATETIME, archived INTEGER) ENGINE=MyISAM;";
	dataConnection::runQuery($query);
	$query = "CREATE TABLE user_shares_tt(id INTEGER PRIMARY KEY AUTO_INCREMENT, sender VARCHAR(50), recipient VARCHAR(50), teachingtip_id INTEGER, time DATETIME, message TEXT, archived INTEGER) ENGINE=MyISAM;";
	dataConnection::runQuery($query);
	$query = "CREATE TABLE user_follows_user(id INTEGER PRIMARY KEY AUTO_INCREMENT, follower_id INTEGER, user_id INTEGER, time DATETIME) ENGINE=MyISAM;";
	dataConnection::runQuery($query);
	$query = "CREATE TABLE notification(id INTEGER PRIMARY KEY AUTO_INCREMENT, user_id INTEGER, activity_id INTEGER, activity_type VARCHAR(10), category VARCHAR(20), time DATETIME, seen INTEGER) ENGINE=MyISAM;";
	dataConnection::runQuery($query);
}

//Skeleton PHP classes for data tables

class user
{
	var $id; //primary key
	var $name;
	var $lastname;
	var $phonenumber;
	var $username;
	var $email;
	var $profile_picture;
	var $college;
	var $school;
	var $esteem;
	var $engagement;
	var $lastaccess;
	var $joindate;
	var $last_visit;
	var $isadmin;

	function user($asArray=null)
	{
		$this->id = null; //primary key
		$this->name = "";
		$this->lastname = "";
		$this->phonenumber = "";
		$this->username = "";
		$this->email = "";
		$this->profile_picture = "";
		$this->college = "";
		$this->school = "";
		$this->esteem = "0";
		$this->engagement = "0";
		$this->lastaccess = time();
		$this->joindate = time();
		$this->last_visit = time();
		$this->isadmin = false;
		if($asArray!==null)
			$this->fromArray($asArray);
	}

	function fromArray($asArray)
	{
		$this->id = $asArray['id'];
		$this->name = $asArray['name'];
		$this->lastname = $asArray['lastname'];
		$this->phonenumber = $asArray['phonenumber'];
		$this->username = $asArray['username'];
		$this->email = $asArray['email'];
		$this->profile_picture = $asArray['profile_picture'];
		$this->college = $asArray['college'];
		$this->school = $asArray['school'];
		$this->esteem = $asArray['esteem'];
		$this->engagement = $asArray['engagement'];
		$this->lastaccess = dataConnection::db2date($asArray['lastaccess']);
		$this->joindate = dataConnection::db2date($asArray['joindate']);
		$this->last_visit = dataConnection::db2time($asArray['last_visit']);
		$this->isadmin = ($asArray['isadmin']==0)?false:true;
	}

	static function retrieve_user($id)
	{
		$query = "SELECT * FROM user WHERE id='".dataConnection::safe($id)."';";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
		{
			return new user($result[0]);
		}
		else
			return false;
	}

	static function retrieve_user_matching($field, $value, $from=0, $count=-1, $sort=null)
	{
	    if(preg_replace('/\W/','',$field)!== $field)
	        return false; // not a permitted field name;
	    $query = "SELECT * FROM user WHERE $field='".dataConnection::safe($value)."'";
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
	            $output[] = new user($r);
	        return $output;
	    }
	    else
	        return false;
	}
	
	function insert()
	{
		//#Any required insert methods for foreign keys need to be called here.
		$query = "INSERT INTO user(name, lastname, phonenumber, username, email, profile_picture, college, school, esteem, engagement, lastaccess, joindate, last_visit, isadmin) VALUES(";
		$query .= "'".dataConnection::safe($this->name)."', ";
		$query .= "'".dataConnection::safe($this->lastname)."', ";
		$query .= "'".dataConnection::safe($this->phonenumber)."', ";
		$query .= "'".dataConnection::safe($this->username)."', ";
		$query .= "'".dataConnection::safe($this->email)."', ";
		$query .= "'".dataConnection::safe($this->profile_picture)."', ";
		$query .= "'".dataConnection::safe($this->college)."', ";
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

	function update()
	{
		$query = "UPDATE user ";
		$query .= "SET name='".dataConnection::safe($this->name)."' ";
		$query .= ", lastname='".dataConnection::safe($this->lastname)."' ";
		$query .= ", phonenumber='".dataConnection::safe($this->phonenumber)."' ";
		$query .= ", username='".dataConnection::safe($this->username)."' ";
		$query .= ", email='".dataConnection::safe($this->email)."' ";
		$query .= ", profile_picture='".dataConnection::safe($this->profile_picture)."' ";
		$query .= ", college='".dataConnection::safe($this->college)."' ";
		$query .= ", school='".dataConnection::safe($this->school)."' ";
		$query .= ", esteem='".dataConnection::safe($this->esteem)."' ";
		$query .= ", engagement='".dataConnection::safe($this->engagement)."' ";
		$query .= ", lastaccess='".dataConnection::date2db($this->lastaccess)."' ";
		$query .= ", joindate='".dataConnection::date2db($this->joindate)."' ";
		$query .= ", last_visit='".dataConnection::time2db($this->last_visit)."' ";
		$query .= ", isadmin='".(($this->isadmin===false)?0:1)."' ";
		$query .= "WHERE id='".dataConnection::safe($this->id)."';";
		return dataConnection::runQuery($query);
	}

	static function count($where_name=null, $equals_value=null)
	{
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

	function toXML()
	{
		$out = "<user>\n";
		$out .= '<id>'.htmlentities($this->id)."</id>\n";
		$out .= '<name>'.htmlentities($this->name)."</name>\n";
		$out .= '<lastname>'.htmlentities($this->lastname)."</lastname>\n";
		$out .= '<phonenumber>'.htmlentities($this->phonenumber)."</phonenumber>\n";
		$out .= '<username>'.htmlentities($this->username)."</username>\n";
		$out .= '<email>'.htmlentities($this->email)."</email>\n";
		$out .= '<profile_picture>'.htmlentities($this->profile_picture)."</profile_picture>\n";
		$out .= '<college>'.htmlentities($this->college)."</college>\n";
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
	//[[USERCODE_user]] Put code for custom class members in this block.

	// get the number of users in the system
	static function get_number_users() {
		$query = "SELECT COUNT(id) AS number_users FROM user";
		$result = dataConnection::runQuery($query);
		return $result[0]['number_users'];
	}

	static function retrieve_by_username($username)
	{
		$query = "SELECT * FROM user WHERE username='".dataConnection::safe($username)."';";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
		{
			return new user($result[0]);
		}
		else
			return false;
	}

	// get all users in the db
	static function get_all_users() {
		$query = "SELECT * FROM user ORDER BY id ASC";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
	    {
	        $output = array();
	        foreach($result as $r)
	            $output[] = new user($r);
	        return $output;
	    }
	    else
	        return false;

	}

	// get a list of all users ordered by the number of tts they have published
	static function get_most_tts() {
		$query = "SELECT u.*, COUNT(tt.id) as number_tts FROM user as u INNER JOIN teachingtip as tt ON u.id = tt.author_id WHERE tt.draft = 0 AND tt.archived = 0 GROUP BY u.id ORDER BY number_tts DESC, u.id";
		$result = dataConnection::runQuery($query);
		if (sizeof($result) != 0) {
			$users = array();
			foreach($result as $r){
				$user = new user($r);
				array_push($users, array($user, $r['number_tts']));
			}
			return $users;
		} else return false;
	}

	// get the number of new tts (published since the user last logged in)
	function get_number_new_tts() {
		$query = "SELECT COUNT(id) AS number_tts FROM teachingtip as tt WHERE tt.time > '". dataConnection::time2db($this->last_visit) ."' AND tt.author_id <> '". dataConnection::safe($this->id) ."' AND archived = 0 AND draft = 0";
		$result = dataConnection::runQuery($query);
		return $result[0]['number_tts'];
	}

	//get the likes of the user (INCLUDING own likes)
	function getLikes() {
		$query = "SELECT * FROM user_likes_tt WHERE user_id ='".dataConnection::safe($this->id)."' AND archived='0' ";
		$result = dataConnection::runQuery($query);
		if (sizeof($result) != 0) {
			$likes = array();
			foreach($result as $r){
				$like = new user_likes_tt($r);
				array_push($likes, $like);
			}
			return $likes;
		}else return false;
	}

	//get the comments of the user (INCLUDING own comments)
	function getComments() {
		$query = "SELECT * FROM user_comments_tt WHERE user_id ='".dataConnection::safe($this->id)."' AND archived='0' ";
		$result = dataConnection::runQuery($query);
		if (sizeof($result) != 0) {
			$comments = array();
			foreach($result as $r){
				$comment = new user_comments_tt($r);
				array_push($comments, $comment);
			}
			return $comments;
		}else return false;
	}

	//get the shares of the user (INCLUDING own shares)
	function getShares() {
		$query = "SELECT * FROM user_shares_tt WHERE sender ='".dataConnection::safe($this->email)."' AND archived='0' ";
		$result = dataConnection::runQuery($query);
		if (sizeof($result) != 0) {
			$shares = array();
			foreach($result as $r){
				$share = new user_shares_tt($r);
				array_push($shares, $share);
			}
			return $shares;
		}else return false;
	}

	// get the number of TTs this user has posted
	function get_number_tts() {
		$query = "SELECT COUNT(id) AS number_tts FROM teachingtip WHERE archived = 0 AND draft = 0 AND author_id = '" . dataConnection::safe($this->id) ."'";
		$result = dataConnection::runQuery($query);
		return $result[0]['number_tts'];
	}

	// get the number of likes this user received on their teaching tips (EXCLUDING own likes)
	function get_number_received_likes() {
		$query = "SELECT COUNT(ultt.id) AS number_likes FROM user_likes_tt as ultt INNER JOIN teachingtip as tt ON ultt.teachingtip_id = tt.id WHERE tt.archived = 0 AND tt.draft = 0 AND tt.author_id = '".dataConnection::safe($this->id)."' AND ultt.user_id <> '".dataConnection::safe($this->id). "'";
		$result = dataConnection::runQuery($query);
		return $result[0]['number_likes'];
	}

	// get the number of comments this user received on their teaching tips (EXCLUDING comments posted by themselves)
	function get_number_received_comments() {
		$query = "SELECT COUNT(uctt.id) AS number_comments FROM user_comments_tt as uctt INNER JOIN teachingtip as tt ON uctt.teachingtip_id = tt.id WHERE tt.archived = 0 AND tt.draft = 0 AND tt.author_id = '".dataConnection::safe($this->id)."' AND uctt.user_id <> '".dataConnection::safe($this->id). "'";
		$result = dataConnection::runQuery($query);
		return $result[0]['number_comments'];
	}

	// get the number of times this user's tts have been shared (EXCLUDING own shares)
	function get_number_shares_of_tts() {
		$query = "SELECT COUNT(ustt.id) AS number_shares FROM user_shares_tt as ustt INNER JOIN teachingtip as tt ON ustt.teachingtip_id = tt.id WHERE tt.archived = 0 AND tt.draft = 0 AND tt.author_id = '".dataConnection::safe($this->id)."' AND ustt.sender <> '".dataConnection::safe($this->email). "'";
		$result = dataConnection::runQuery($query);
		return $result[0]['number_shares'];
	}

	// get the number of views this user has on all their TTs
	function get_number_received_views_tts() {
		$query = "SELECT COUNT(ttv.id) AS number_views FROM ttview as ttv INNER JOIN teachingtip as tt ON ttv.teachingtip_id = tt.id WHERE tt.archived = 0 AND tt.draft = 0 AND tt.author_id = '" .dataConnection::safe($this->id) . "'";
		$result = dataConnection::runQuery($query);
		return $result[0]['number_views'];
	}

	// get the number of followers this user has
	function get_number_followers() {
		$query = "SELECT COUNT(*) AS number_followers FROM user_follows_user WHERE user_id = '" .dataConnection::safe($this->id) . "'";
	    $result = dataConnection::runQuery($query);
	    return $result[0]['number_followers'];
	}

	// get the number of likes this user has given to other users' TTs
	function get_number_given_likes() {
		$query = "SELECT COUNT(ultt.id) AS number_likes FROM user_likes_tt as ultt INNER JOIN teachingtip as tt ON ultt.teachingtip_id = tt.id WHERE tt.archived = 0 AND tt.draft = 0 AND tt.author_id <> '".dataConnection::safe($this->id)."' AND ultt.user_id = '".dataConnection::safe($this->id). "'";
		$result = dataConnection::runQuery($query);
		return $result[0]['number_likes'];
	}

	// get the number of TTs that this user has viewed
	function get_number_given_views() {
		$query = "SELECT COUNT(ttv.id) AS number_views FROM ttview as ttv INNER JOIN teachingtip as tt ON ttv.teachingtip_id = tt.id WHERE tt.archived = 0 AND tt.draft = 0 AND ttv.user_id = '".dataConnection::safe($this->id). "' AND tt.author_id <> '".dataConnection::safe($this->id). "'";
		$result = dataConnection::runQuery($query);
		return $result[0]['number_views'];
	}

	// get the number of times this user has shared other users' TTs
	function get_number_given_shares() {
		$query = "SELECT COUNT(ustt.id) AS number_shares FROM user_shares_tt as ustt INNER JOIN teachingtip as tt ON ustt.teachingtip_id = tt.id WHERE tt.archived = 0 AND tt.draft = 0 AND tt.author_id <> '".dataConnection::safe($this->id)."' AND ustt.sender = '".dataConnection::safe($this->email). "'";
		$result = dataConnection::runQuery($query);
		return $result[0]['number_shares'];
	}

	// get the number of comments posted by this user on other users' TTs
	function get_number_given_comments() {
		$query = "SELECT COUNT(uctt.id) AS number_comments FROM user_comments_tt as uctt INNER JOIN teachingtip as tt ON uctt.teachingtip_id = tt.id WHERE tt.archived = 0 AND tt.draft = 0 AND tt.author_id <> '".dataConnection::safe($this->id)."' AND uctt.user_id = '".dataConnection::safe($this->id). "'";
		$result = dataConnection::runQuery($query);
		return $result[0]['number_comments'];
	}

	// get the number of people this user follows
	function get_number_following() {
		$query = "SELECT COUNT(*) AS number_following FROM user_follows_user WHERE follower_id = '" .dataConnection::safe($this->id) . "'";
	    $result = dataConnection::runQuery($query);
	    return $result[0]['number_following'];
	}

	// get the teaching tips posted by this user
	function get_teaching_tips() {
		$query = "SELECT * FROM teachingtip WHERE author_id = '".dataConnection::safe($this->id) . "' AND archived='0'";
	    $result = dataConnection::runQuery($query);
	    if (sizeof($result) != 0) {
			$tts = array();
			foreach($result as $r){
				$tt = new teachingtip($r);
				array_push($tts, $tt);
			}
			return $tts;
		} else return false;
	    
	}

	// get the teaching tips that the user contributed  -- Return Objects
	function get_contr_teaching_tips(){
		$query = "SELECT * FROM contributors WHERE user_id IS NOT NULL AND user_id = '".dataConnection::safe($this->id) . "' ";
		$result = dataConnection::runQuery($query);
		if (sizeof($result) != 0) {
			$tts = array();
			foreach($result as $r){
				$tt = teachingtip::retrieve_teachingtip($r['teachingtip_id']);
				array_push($tts, $tt);
			}
			return $tts;
		} else return false;
	}

	// get the $limit top teaching tips for this user based on number of likes
	function get_top_teaching_tips($limit=false) {
		$query = "SELECT tt.*, COUNT(ultt.id) as count_likes FROM teachingtip as tt LEFT JOIN user_likes_tt as ultt ON tt.id = ultt.teachingtip_id WHERE tt.author_id = '".dataConnection::safe($this->id)."' AND tt.archived = '0' AND tt.draft = '0' GROUP BY tt.id ORDER BY count_likes DESC";
		if ($limit) $query .= " LIMIT ". dataConnection::safe($limit);
    	$result = dataConnection::runQuery($query);
    	if (sizeof($result) != 0) {
			$tts = array();
			foreach($result as $r){
				$tt = new teachingtip($r);
				array_push($tts, $tt);
			}
			return $tts;
		} else return false;
	}

	static function findUser($namepart)
	{
	    $query = "SELECT * FROM user WHERE name LIKE '%".dataConnection::safe($namepart)."%'";
	    $query .= ';';
	    $result = dataConnection::runQuery($query);
	    if(sizeof($result)!=0)
	    {
	        $output = array();
	        foreach($result as $r)
	            $output[] = new user($r);
	        return $output;
	    }
	    else
	        return false;
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

	// get all the awards for this user in category $cat of type $type ordered by rank
	function get_awards($cat, $type) {
		$query = "SELECT a.* FROM user_earns_award as uea
			INNER JOIN award as a ON uea.award_id = a.id
			WHERE uea.user_id = '".dataConnection::safe($this->id)."' AND uea.promoted = 0 AND a.category = '".dataConnection::safe($cat)."' AND a.type = '".dataConnection::safe($type)."'
			ORDER BY a.rank";
		$result = dataConnection::runQuery($query);
		if (sizeof($result) != 0) {
			$awards = array();
			foreach($result as $r){
				$a = new award($r);
				array_push($awards, $a);
			}
			return $awards;
		} else return false;
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
		if (sizeof($result) != 0) return new user_earns_award($result[0]);
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
			case 0:
				return false;
				break;

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
				break;
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

		if($ho == $min_rank) return false;

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
		}
		elseif ($min_rank == 6) {
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
			foreach($result as $r){
				$n = new notification($r);
				array_push($notifications, $n);
			}
			return $notifications;
		} else return false;
		

	}

	//[[USERCODE_user]] WEnd of custom class members.
}

class user_settings
{
	var $id; //primary key
	var $user_id; //foreign key
	var $school_posts;
	var $tts_activity;
	var $followers_posts;
	var $awards;

	function user_settings($asArray=null)
	{
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

	function admin($asArray=null)
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

	function admin_settings($asArray=null)
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

	function award($asArray=null)
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

	function user_earns_award($asArray=null)
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

class teachingtip
{
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
	var $archived;
	var $draft;

	function teachingtip($asArray=null)
	{
		$this->id = null; //primary key
		$this->author_id = null; // foreign key, needs dealt with.
		$this->title = "";
		$this->time = time();
		$this->rationale = "";
		$this->description = "";
		$this->practice = "";
		$this->worksbetter = "";
		$this->doesntworkunless = "";
		$this->essence = "";
		$this->archived = "0";
		$this->draft = "0";
		if($asArray!==null)
			$this->fromArray($asArray);
	}

	function fromArray($asArray)
	{
		$this->id = $asArray['id'];
		$this->author_id = $asArray['author_id']; // foreign key, check code
		$this->title = $asArray['title'];
		$this->time = dataConnection::db2time($asArray['time']);
		$this->rationale = $asArray['rationale'];
		$this->description = $asArray['description'];
		$this->practice = $asArray['practice'];
		$this->worksbetter = $asArray['worksbetter'];
		$this->doesntworkunless = $asArray['doesntworkunless'];
		$this->essence = $asArray['essence'];
		$this->archived = $asArray['archived'];
		$this->draft = $asArray['draft'];
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
		$query = "INSERT INTO teachingtip(author_id, title, time, rationale, description, practice, worksbetter, doesntworkunless, essence, archived, draft) VALUES(";
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
		$query .= "'".dataConnection::safe($this->archived)."', ";
		$query .= "'".dataConnection::safe($this->draft)."');";
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
		$query .= ", archived='".dataConnection::safe($this->archived)."' ";
		$query .= ", draft='".dataConnection::safe($this->draft)."' ";
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
		$out .= '<archived>'.htmlentities($this->archived)."</archived>\n";
		$out .= '<draft>'.htmlentities($this->draft)."</draft>\n";
		$out .= "</teachingtip>\n";
		return $out;
	}
	//[[USERCODE_teachingtip]] Put code for custom class members in this block.

	public function __toString() {
		return $this->id;
	}

	// get the number of published teaching tips in the system
	static function get_number_tts() {
		$query = "SELECT COUNT(id) AS number_tts FROM teachingtip WHERE draft = 0 AND archived = 0";
		$result = dataConnection::runQuery($query);
		return $result[0]['number_tts'];
	}

	// get all teaching tips in the database (except for drafts)
	static function get_all_teaching_tips() {
		$query = "SELECT * FROM teachingtip WHERE draft = 0 ORDER BY id ASC";
		$result = dataConnection::runQuery($query);
		if (sizeof($result) != 0) {
			$tts = array();
			foreach($result as $r){
				$tt = new teachingtip($r);
				array_push($tts, $tt);
			}
			return $tts;
		} else return false;
	}
	
	// Get popular teaching tips based on their likes if true,else comments  
	// use of limmit as false so can retrieve all
	// use of start as 0 to consider all by default
	static function getPopularTeachingTips($limit=false,$start=false,$type=true,$operator='<',$time=false){
		if(!$time){
			$time= time();
		}
		
		// Conversion to db format for querring
		$time = date("Y-m-d H:i:s",$time);
		if($type){
			$query = "SELECT tt.*, COUNT(ultt.id) as count_likes FROM teachingtip as tt LEFT JOIN user_likes_tt as ultt ON tt.id = ultt.teachingtip_id WHERE tt.archived = '0' AND tt.draft = '0' AND tt.time ".$operator." '".$time."' GROUP BY tt.id ORDER BY count_likes DESC";
		}else{
			$query = "SELECT tt.*, COUNT(uctt.id) as count_comments FROM teachingtip as tt LEFT JOIN user_comments_tt as uctt ON tt.id = uctt.teachingtip_id WHERE tt.archived = '0' AND tt.draft = '0' AND tt.time ".$operator." '".$time."' GROUP BY tt.id ORDER BY count_comments DESC";
		}
		if ($limit) $query .= " LIMIT ". dataConnection::safe($limit);
		if ($start) $query .= " OFFSET " . dataConnection::safe($start); 
		$query .= " ;";
    	$result = dataConnection::runQuery($query);
    	if (sizeof($result) != 0) {
			$tts = array();
			foreach($result as $r){
				$tt = new teachingtip($r);
				array_push($tts, $tt);
			}
			return $tts;
		} else return false;	
	}

	static function get_latest_teaching_tips($limit=false) {
		$query = "SELECT * FROM teachingtip WHERE archived='0' AND draft='0' ORDER BY time DESC";
		if ($limit) $query .= " LIMIT $limit";
		$result = dataConnection::runQuery($query);
		if (sizeof($result) != 0) {
			$tts = array();
			foreach($result as $r){
				$tt = new teachingtip($r);
				array_push($tts, $tt);
			}
			return $tts;
		} else return false;


	}

	function get_author() {
		$query = "SELECT u.* FROM user as u INNER JOIN teachingtip as tt ON u.id = tt.author_id WHERE tt.id ='".dataConnection::safe($this->id)."'";
		$result = dataConnection::runQuery($query);
		if (sizeof($result) != 0) return new user($result[0]);
		else return false;
	}

	// get the comments for this teaching tip as an array of ttcomment objects
	function get_comments() {
		$query = "SELECT c.* FROM ttcomment as c INNER JOIN user_comments_tt as uctt ON c.id = uctt.comment_id WHERE uctt.teachingtip_id ='".dataConnection::safe($this->id)."'";
		$result = dataConnection::runQuery($query);
		if (sizeof($result) != 0) {
			$comments = array();
			foreach ($result as $r)
            {
               $comment = new ttcomment($r);
               array_push($comments, $comment);
             
            }
            return $comments;
		} else return false;
	}

	// get the keywords for this teaching tip as an array of ttkeyword objects
	function get_keywords() {
		$query = "SELECT kw.* FROM ttkeyword as kw INNER JOIN teachingtip as tt ON kw.ttid_id = tt.id WHERE tt.id ='".dataConnection::safe($this->id)."'";
		$result = dataConnection::runQuery($query);
		if (sizeof($result) != 0) {
			$keywords = array();
			foreach ($result as $r)
            {
               $keyword = new ttkeyword($r);
               array_push($keywords, $keyword);
             
            }
            return $keywords;
		} else return false;
	}

	// get all the filters for this teachingtip
	function get_all_filters() {
		$query = "SELECT f.* FROM ttfilter as f INNER JOIN teachingtip as tt ON f.teachingtip_id = tt.id WHERE tt.id ='".dataConnection::safe($this->id)."'";
		$result = dataConnection::runQuery($query);
		if (sizeof($result) != 0) {
			$filters = array();
			foreach ($result as $r)
            {
            	$filter = new ttfilter($r);
               array_push($filters, $filter);
            }
            return $filters;
		} else return false;
	}

	// get the filter options for $cat category
	function get_filters($cat) {
		$query = "SELECT f.* FROM ttfilter as f INNER JOIN teachingtip as tt ON f.teachingtip_id = tt.id WHERE tt.id ='".dataConnection::safe($this->id)."' AND f.category = '".dataConnection::safe($cat)."'";
		$result = dataConnection::runQuery($query);
		if (sizeof($result) != 0) {
			$filters = array();
			foreach ($result as $r)
            {
               array_push($filters, $r['opt']);
            }
            return $filters;
		} else return false;
	}

	function get_number_likes() {
		$query = "SELECT COUNT(*) AS number_likes FROM user_likes_tt WHERE teachingtip_id = '".dataConnection::safe($this->id)."'";
		$result = dataConnection::runQuery($query);
		return $result[0]['number_likes'];
	}

	// get the number of likes for this TT not including the own like (if existent)
	function get_number_likes_not_author() {
		$query = "SELECT COUNT(*) AS number_likes FROM user_likes_tt WHERE teachingtip_id = '".dataConnection::safe($this->id)."' AND user_id <> '". dataConnection::safe($this->author_id) ."'";
		$result = dataConnection::runQuery($query);
		return $result[0]['number_likes'];
	}

	function get_number_comments() {
		$query = "SELECT COUNT(*) AS number_comments FROM user_comments_tt WHERE teachingtip_id = '".dataConnection::safe($this->id)."'";
		$result = dataConnection::runQuery($query);
		return $result[0]['number_comments'];
	}


	// get the number of comments for this TT not including the author's comments (if any)
	function get_number_comments_not_author() {
		$query = "SELECT COUNT(*) AS number_comments FROM user_comments_tt WHERE teachingtip_id = '".dataConnection::safe($this->id)."' AND user_id <> '". dataConnection::safe($this->author_id) ."'";
		$result = dataConnection::runQuery($query);
		return $result[0]['number_comments'];
	}

	function get_number_shares() {
		$query = "SELECT COUNT(*) AS number_shares FROM user_shares_tt WHERE teachingtip_id = '".dataConnection::safe($this->id)."'";
		$result = dataConnection::runQuery($query);
		return $result[0]['number_shares'];
	}

	function get_number_views() {
		$query = "SELECT COUNT(id) as count FROM ttview WHERE teachingtip_id = '" . dataConnection::safe($this->id) . "'";
		$result = dataConnection::runQuery($query);
		return $result[0]['count'];
	}

	// get the contributors for this TT as user objects
	function get_contributors() {
		$query = "SELECT * FROM contributors WHERE teachingtip_id ='". dataConnection::safe($this->id) ."' AND user_id IS NOT NULL";
		$result = dataConnection::runQuery($query);
		if (sizeof($result) != 0) {
			$contributors = array();
			foreach($result as $r){
				$contributor = user::retrieve_user($r['user_id']);
				array_push($contributors, $contributor);
			}
			return $contributors;
		} else return false;

	}

	function get_contributors_ids() {
		$query = "SELECT * FROM contributors WHERE teachingtip_id ='". dataConnection::safe($this->id) ."' AND user_id IS NOT NULL";
		$result = dataConnection::runQuery($query);
		if (sizeof($result) != 0) {
			$contributors = array();
			foreach($result as $r){
				array_push($contributors, $r['user_id']);
			}
			return $contributors;
		} else return false;
	}

	static function get_all_schools() {
		$query = "SELECT u.school FROM teachingtip as tt INNER JOIN user as u ON tt.author_id = u.id GROUP BY u.school";
		$result = dataConnection::runQuery($query);
		if (sizeof($result) != 0) {
			$schools = array();
			foreach ($result as $r) {
				$schools[] = $r['school'];
			}
		}
		return $schools;
	}

	// get the schools (for which there is at least one TT in the db) that belong to colleges specified in $colleges array 
	// if $colleges argument not given -> get all the schools in the db
	static function get_schools_for_colleges($colleges=false) {
		$query = "SELECT DISTINCT u.school, COUNT(u.school) as school_count FROM teachingtip as tt INNER JOIN user as u ON tt.author_id = u.id WHERE archived = 0 AND draft = 0";
		if ($colleges) {
			$query .= " AND (u.college = '$colleges[0]'";
			if (sizeof($colleges) > 1)
				foreach (array_slice($colleges, 1) as $college) $query .= "OR college = '$college'";
			$query .= ")";
		}
		$query .= " GROUP BY u.school ORDER BY u.college, u.school";
		$result = dataConnection::runQuery($query);
		if (sizeof($result) != 0) {
			$schools = array();
			foreach ($result as $r) {
				$schools[] = array('school' => $r['school'], 'count' => $r['school_count']);
			}
		}
		return $schools;

	}

	// get the TTs from all colleges in $colleges array
	static function get_tts_from_colleges($colleges) {
		$query = "SELECT tt.* FROM teachingtip as tt INNER JOIN user as u ON tt.author_id = u.id WHERE archived = 0 AND draft = 0 AND (u.college = '$colleges[0]'";
		if (sizeof($colleges > 1)) {
			foreach (array_slice($colleges, 1) as $college) $query .= " OR u.college = '$college'";
		}
		$query .= ") ORDER BY time DESC";
		$result = dataConnection::runQuery($query);
		if (sizeof($result) != 0) {
			$tts = array();
			foreach($result as $r){
				$tt = new teachingtip($r);
				array_push($tts, $tt);
			}
			return $tts;
		} else return false;
	}

	// get the TTs from all schools in $schools array
	static function get_tts_from_schools($schools) {
		$query = "SELECT tt.* FROM teachingtip as tt INNER JOIN user as u ON tt.author_id = u.id WHERE archived = 0 AND draft = 0 AND (u.school = '$schools[0]'";
		if (sizeof($schools > 1)) {
			foreach (array_slice($schools, 1) as $school) $query .= " OR u.school = '$school'";
		}
		$query .= ") ORDER BY time DESC";
		$result = dataConnection::runQuery($query);
		if (sizeof($result) != 0) {
			$tts = array();
			foreach($result as $r){
				$tt = new teachingtip($r);
				array_push($tts, $tt);
			}
			return $tts;
		} else return false;
	}

	// get the TTs for all the filters applied by the user
	static function get_tts_from_filters($schools, $sizes, $envs, $sol, $itc) {
		$list = array();

		if ($schools) {
			$schools_map = array_map(
				function($s) { return 'u.school='."'".$s."'"; }, 
				$schools
			);
			if (!empty($schools_map)) {
				$schools_string = implode(' OR ', $schools_map);
				$query = "SELECT DISTINCT tt.* FROM teachingtip as tt INNER JOIN user as u ON tt.author_id = u.id WHERE archived = 0 AND draft = 0 AND " . $schools_string . "ORDER BY time DESC";
				$result = dataConnection::runQuery($query);

				if (sizeof($result) != 0) {
					$r1 = array();
					foreach($result as $r){
						$tt = new teachingtip($r);
						array_push($r1, $tt);
					}
					$list[] = $r1;
				} 

			}
		}
		
		if ($sizes) {
			$sizes_map = array_map(
				function($cs) { return 'f.opt='."'".$cs."'"; }, 
				$sizes
			);
			if (!empty($sizes_map)) {
				$sizes_string = "f.category = 'class_size' AND (".implode(' OR ', $sizes_map).") ";
				$r2 = getTTsWithFilters($sizes_string);
				$list[] = $r2;
			}
		}
		
		if ($envs) {
			$envs_map = array_map(
				function($e) { return 'f.opt='."'".$e."'"; }, 
				$envs
			);
			if (!empty($envs_map)) {
				$envs_string = "f.category = 'environment' AND (".implode(' OR ', $envs_map).") ";
				$r3 = getTTsWithFilters($envs_string);
				$list[] = $r3;
			}
		}

		if ($sol) {
			$sol_map = array_map(
				function($e) { return 'f.opt='."'".$e."'"; }, 
				$sol
			);
			if (!empty($sol_map)) {
				$sol_string = "f.category = 'suitable_ol' AND (".implode(' OR ', $sol_map).") ";
				$r4 = getTTsWithFilters($sol_string);
				$list[] = $r4;
			}
		}

		if ($itc) {
			$itc_map = array_map(
				function($e) { return 'f.opt='."'".$e."'"; }, 
				$itc
			);
			if (!empty($itc_map)) {
				$itc_string = "f.category = 'it_competency' AND (".implode(' OR ', $itc_map).") ";
				$r5 = getTTsWithFilters($itc_string);
				$list[] = $r5;
			}	
		}
		
		if (sizeof($list) > 1) $results = call_user_func_array('array_intersect', $list);
		else $results = $list[0];

		if (sizeof($results) != 0) return $results;
		else return false;
	}

	//[[USERCODE_teachingtip]] WEnd of custom class members.
}

class ttcomment
{
	var $id; //primary key
	var $time;
	var $comment;
	var $archived;

	function ttcomment($asArray=null)
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

	function ttkeyword($asArray=null)
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

	function ttfilter($asArray=null)
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

	function ttview($asArray=null)
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

	function contributors($asArray=null)
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
	    if(sizeof($result)!=0)
	    {
	        $output = array();
	        foreach($result as $r)
	            $output[] = new contributors($r);
	        return $output;
	    }
	    else
	        return false;
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

	function user_comments_tt($asArray=null)
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

	function user_likes_tt($asArray=null)
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

	function user_shares_tt($asArray=null)
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

	function user_follows_user($asArray=null)
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

	function notification($asArray=null)
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

	// Get the notifications for each user
	// seen 0 = query unseen >>>> seen 1 = query seen >>>> seen 2 = get all
	static function getNotifications($userID,$limit=false,$seen=2,$start=false){
		$query = "SELECT * FROM notification WHERE user_id = $userID";
		if ($seen == 0) $query .= " AND seen = 0 ";
		if ($seen == 1) $query .= " AND seen = 1 ";
		$query.= " ORDER BY id DESC ";
		if ($limit) $query .= " LIMIT ". dataConnection::safe($limit);
		if ($start) $query .= " OFFSET " . dataConnection::safe($start);
		$query .= " ;";
    	$result = dataConnection::runQuery($query);
    	if (sizeof($result) != 0) {
			$notifications = array();
			foreach($result as $r){
				$notification = new notification($r);
				array_push($notifications, $notification);
			}
			return $notifications;
		} else return false;	

	}

	//[[USERCODE_notification]] WEnd of custom class members.
}

?>

 