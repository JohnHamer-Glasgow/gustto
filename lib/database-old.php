<?php
require_once('corelib/dataaccess.php');

function initializeDataBase_()
{
	$query = "CREATE TABLE user(id INTEGER PRIMARY KEY AUTO_INCREMENT, name VARCHAR(20), lastname VARCHAR(20), phonenumber VARCHAR(20), username VARCHAR(20), password VARCHAR(20), email VARCHAR(20), specialization VARCHAR(20), points INTEGER, lastaccess DATE, joindate DATE, isadmin INTEGER);";
	dataConnection::runQuery($query);
	$query = "CREATE TABLE admin(id INTEGER PRIMARY KEY AUTO_INCREMENT, role VARCHAR(20));";
	dataConnection::runQuery($query);
	$query = "CREATE TABLE badge(id INTEGER PRIMARY KEY AUTO_INCREMENT, url VARCHAR(20));";
	dataConnection::runQuery($query);
	$query = "CREATE TABLE user_earns_badge(id INTEGER PRIMARY KEY AUTO_INCREMENT, user_id INTEGER, badge_id INTEGER);";
	dataConnection::runQuery($query);
	$query = "CREATE TABLE message(id INTEGER PRIMARY KEY AUTO_INCREMENT, user_id INTEGER, time DATETIME);";
	dataConnection::runQuery($query);
	$query = "CREATE TABLE teachingpractice(id INTEGER PRIMARY KEY AUTO_INCREMENT, author_id INTEGER, title VARCHAR(20), time DATETIME, problemstatement TEXT, thisbundle TEXT, wayitworks TEXT, worksbetter TEXT, doesntwork TEXT, doesntworkunless TEXT, workedif TEXT, variations TEXT, solutionstatement TEXT);";
	dataConnection::runQuery($query);
	$query = "CREATE TABLE user_comments_teachingpractice(id INTEGER PRIMARY KEY AUTO_INCREMENT, user_id INTEGER, teachingpractice_id INTEGER, time DATETIME, comment TEXT);";
	dataConnection::runQuery($query);
	$query = "CREATE TABLE user_likes_teachingpractice(id INTEGER PRIMARY KEY AUTO_INCREMENT, user_id INTEGER, teachingpractice_id INTEGER, time DATETIME);";
	dataConnection::runQuery($query);
	$query = "CREATE TABLE help_documentation(id INTEGER PRIMARY KEY AUTO_INCREMENT, admin_id INTEGER, time DATETIME);";
	dataConnection::runQuery($query);
}

function dropAll_(){

	$query = "DROP TABLE user;";
	dataConnection::runQuery($query);
	$query = "DROP TABLE admin;";
	dataConnection::runQuery($query);
	$query = "DROP TABLE badge;";
	dataConnection::runQuery($query);
	$query = "DROP TABLE user_earns_badge;";
	dataConnection::runQuery($query);
	$query = "DROP TABLE message;";
	dataConnection::runQuery($query);
	$query = "DROP TABLE teachingpractice;";
	dataConnection::runQuery($query);
	$query = "DROP TABLE user_comments_teachingpractice;";
	dataConnection::runQuery($query);
	$query = "DROP TABLE user_likes_teachingpractice;";
	dataConnection::runQuery($query);
	$query = "DROP TABLE help_documentation;";
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
	var $password;
	var $email;
	var $specialization;
	var $points;
	var $lastaccess;
	var $joindate;
	var $isadmin;

	function user($asArray=null)
	{
		$this->id = null; //primary key
		$this->name = "";
		$this->lastname = "";
		$this->phonenumber = "";
		$this->username = "";
		$this->password = "";
		$this->email = "";
		$this->specialization = "";
		$this->points = "0";
		$this->lastaccess = time();
		$this->joindate = time();
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
		$this->password = $asArray['password'];
		$this->email = $asArray['email'];
		$this->specialization = $asArray['specialization'];
		$this->points = $asArray['points'];
		$this->lastaccess = dataConnection::db2date($asArray['lastaccess']);
		$this->joindate = dataConnection::db2date($asArray['joindate']);
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
		$query = "INSERT INTO user(name, lastname, phonenumber, username, password, email, specialization, points, lastaccess, joindate, isadmin) VALUES(";
		$query .= "'".dataConnection::safe($this->name)."', ";
		$query .= "'".dataConnection::safe($this->lastname)."', ";
		$query .= "'".dataConnection::safe($this->phonenumber)."', ";
		$query .= "'".dataConnection::safe($this->username)."', ";
		$query .= "'".dataConnection::safe($this->password)."', ";
		$query .= "'".dataConnection::safe($this->email)."', ";
		$query .= "'".dataConnection::safe($this->specialization)."', ";
		$query .= "'".dataConnection::safe($this->points)."', ";
		$query .= "'".dataConnection::date2db($this->lastaccess)."', ";
		$query .= "'".dataConnection::date2db($this->joindate)."', ";
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
		$query .= ", password='".dataConnection::safe($this->password)."' ";
		$query .= ", email='".dataConnection::safe($this->email)."' ";
		$query .= ", specialization='".dataConnection::safe($this->specialization)."' ";
		$query .= ", points='".dataConnection::safe($this->points)."' ";
		$query .= ", lastaccess='".dataConnection::date2db($this->lastaccess)."' ";
		$query .= ", joindate='".dataConnection::date2db($this->joindate)."' ";
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
		$out .= '<password>'.htmlentities($this->password)."</password>\n";
		$out .= '<email>'.htmlentities($this->email)."</email>\n";
		$out .= '<specialization>'.htmlentities($this->specialization)."</specialization>\n";
		$out .= '<points>'.htmlentities($this->points)."</points>\n";
		$out .= '<lastaccess>'.htmlentities($this->lastaccess)."</lastaccess>\n";
		$out .= '<joindate>'.htmlentities($this->joindate)."</joindate>\n";
		$out .= '<isadmin>'.htmlentities($this->isadmin)."</isadmin>\n";
		$out .= "</user>\n";
		return $out;
	}
	//[[USERCODE_user]] Put code for custom class members in this block.
	
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

	//[[USERCODE_user]] WEnd of custom class members.
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

class badge
{
	var $id; //primary key
	var $url;

	function badge($asArray=null)
	{
		$this->id = null; //primary key
		$this->url = "";
		if($asArray!==null)
			$this->fromArray($asArray);
	}

	function fromArray($asArray)
	{
		$this->id = $asArray['id'];
		$this->url = $asArray['url'];
	}

	static function retrieve_badge($id)
	{
		$query = "SELECT * FROM badge WHERE id='".dataConnection::safe($id)."';";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
		{
			return new badge($result[0]);
		}
		else
			return false;
	}

	static function retrieve_badge_matching($field, $value, $from=0, $count=-1, $sort=null)
	{
	    if(preg_replace('/\W/','',$field)!== $field)
	        return false; // not a permitted field name;
	    $query = "SELECT * FROM badge WHERE $field='".dataConnection::safe($value)."'";
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
	            $output[] = new badge($r);
	        return $output;
	    }
	    else
	        return false;
	}

	function insert()
	{
		//#Any required insert methods for foreign keys need to be called here.
		$query = "INSERT INTO badge(url) VALUES(";
		$query .= "'".dataConnection::safe($this->url)."');";
		dataConnection::runQuery("BEGIN;");
		$result = dataConnection::runQuery($query);
		$result2 = dataConnection::runQuery("SELECT LAST_INSERT_ID() AS id;");
		dataConnection::runQuery("COMMIT;");
		$this->id = $result2[0]['id'];
		return $this->id;
	}

	function update()
	{
		$query = "UPDATE badge ";
		$query .= "SET url='".dataConnection::safe($this->url)."' ";
		$query .= "WHERE id='".dataConnection::safe($this->id)."';";
		return dataConnection::runQuery($query);
	}

	static function count($where_name=null, $equals_value=null)
	{
		$query = "SELECT COUNT(*) AS count FROM badge WHERE ";
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
		$out = "<badge>\n";
		$out .= '<id>'.htmlentities($this->id)."</id>\n";
		$out .= '<url>'.htmlentities($this->url)."</url>\n";
		$out .= "</badge>\n";
		return $out;
	}
	//[[USERCODE_badge]] Put code for custom class members in this block.

	//[[USERCODE_badge]] WEnd of custom class members.
}

class user_earns_badge
{
	var $id; //primary key
	var $user_id; //foreign key
	var $badge_id; //foreign key

	function user_earns_badge($asArray=null)
	{
		$this->id = null; //primary key
		$this->user_id = null; // foreign key, needs dealt with.
		$this->badge_id = null; // foreign key, needs dealt with.
		if($asArray!==null)
			$this->fromArray($asArray);
	}

	function fromArray($asArray)
	{
		$this->id = $asArray['id'];
		$this->user_id = $asArray['user_id']; // foreign key, check code
		$this->badge_id = $asArray['badge_id']; // foreign key, check code
	}

	static function retrieve_user_earns_badge($id)
	{
		$query = "SELECT * FROM user_earns_badge WHERE id='".dataConnection::safe($id)."';";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
		{
			return new user_earns_badge($result[0]);
		}
		else
			return false;
	}

	static function retrieve_user_earns_badge_matching($field, $value, $from=0, $count=-1, $sort=null)
	{
	    if(preg_replace('/\W/','',$field)!== $field)
	        return false; // not a permitted field name;
	    $query = "SELECT * FROM user_earns_badge WHERE $field='".dataConnection::safe($value)."'";
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
	            $output[] = new user_earns_badge($r);
	        return $output;
	    }
	    else
	        return false;
	}

	function insert()
	{
		//#Any required insert methods for foreign keys need to be called here.
		$query = "INSERT INTO user_earns_badge(user_id, badge_id) VALUES(";
		if($this->user_id!==null)
			$query .= "'".dataConnection::safe($this->user_id)."', ";
		else
			$query .= "null, ";
		if($this->badge_id!==null)
			$query .= "'".dataConnection::safe($this->badge_id)."');";
		else
			$query .= "null);";
		dataConnection::runQuery("BEGIN;");
		$result = dataConnection::runQuery($query);
		$result2 = dataConnection::runQuery("SELECT LAST_INSERT_ID() AS id;");
		dataConnection::runQuery("COMMIT;");
		$this->id = $result2[0]['id'];
		return $this->id;
	}

	function update()
	{
		$query = "UPDATE user_earns_badge ";
		$query .= "SET user_id='".dataConnection::safe($this->user_id)."' ";
		$query .= ", badge_id='".dataConnection::safe($this->badge_id)."' ";
		$query .= "WHERE id='".dataConnection::safe($this->id)."';";
		return dataConnection::runQuery($query);
	}

	static function count($where_name=null, $equals_value=null)
	{
		$query = "SELECT COUNT(*) AS count FROM user_earns_badge WHERE ";
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
		$out = "<user_earns_badge>\n";
		$out .= '<id>'.htmlentities($this->id)."</id>\n";
		$out .= '<user>'.htmlentities($this->user)."</user>\n";
		$out .= '<badge>'.htmlentities($this->badge)."</badge>\n";
		$out .= "</user_earns_badge>\n";
		return $out;
	}
	//[[USERCODE_user_earns_badge]] Put code for custom class members in this block.

	//[[USERCODE_user_earns_badge]] WEnd of custom class members.
}

class message
{
	var $id; //primary key
	var $user_id; //foreign key
	var $time;

	function message($asArray=null)
	{
		$this->id = null; //primary key
		$this->user_id = null; // foreign key, needs dealt with.
		$this->time = time();
		if($asArray!==null)
			$this->fromArray($asArray);
	}

	function fromArray($asArray)
	{
		$this->id = $asArray['id'];
		$this->user_id = $asArray['user_id']; // foreign key, check code
		$this->time = dataConnection::db2time($asArray['time']);
	}

	static function retrieve_message($id)
	{
		$query = "SELECT * FROM message WHERE id='".dataConnection::safe($id)."';";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
		{
			return new message($result[0]);
		}
		else
			return false;
	}

	static function retrieve_message_matching($field, $value, $from=0, $count=-1, $sort=null)
	{
	    if(preg_replace('/\W/','',$field)!== $field)
	        return false; // not a permitted field name;
	    $query = "SELECT * FROM message WHERE $field='".dataConnection::safe($value)."'";
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
	            $output[] = new message($r);
	        return $output;
	    }
	    else
	        return false;
	}

	function insert()
	{
		//#Any required insert methods for foreign keys need to be called here.
		$query = "INSERT INTO message(user_id, time) VALUES(";
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
		$query = "UPDATE message ";
		$query .= "SET user_id='".dataConnection::safe($this->user_id)."' ";
		$query .= ", time='".dataConnection::time2db($this->time)."' ";
		$query .= "WHERE id='".dataConnection::safe($this->id)."';";
		return dataConnection::runQuery($query);
	}

	static function count($where_name=null, $equals_value=null)
	{
		$query = "SELECT COUNT(*) AS count FROM message WHERE ";
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
		$out = "<message>\n";
		$out .= '<id>'.htmlentities($this->id)."</id>\n";
		$out .= '<user>'.htmlentities($this->user)."</user>\n";
		$out .= '<time>'.htmlentities($this->time)."</time>\n";
		$out .= "</message>\n";
		return $out;
	}
	//[[USERCODE_message]] Put code for custom class members in this block.

	//[[USERCODE_message]] WEnd of custom class members.
}

class teachingpractice
{
	var $id; //primary key
	var $author_id; //foreign key
	var $title;
	var $time;
	var $problemstatement;
	var $thisbundle;
	var $wayitworks;
	var $worksbetter;
	var $doesntwork;
	var $doesntworkunless;
	var $workedif;
	var $variations;
	var $solutionstatement;

	function teachingpractice($asArray=null)
	{
		$this->id = null; //primary key
		$this->author_id = null; // foreign key, needs dealt with.
		$this->title = "";
		$this->time = time();
		$this->problemstatement = "";
		$this->thisbundle = "";
		$this->wayitworks = "";
		$this->worksbetter = "";
		$this->doesntwork = "";
		$this->doesntworkunless = "";
		$this->workedif = "";
		$this->variations = "";
		$this->solutionstatement = "";
		if($asArray!==null)
			$this->fromArray($asArray);
	}

	function fromArray($asArray)
	{
		$this->id = $asArray['id'];
		$this->author_id = $asArray['author_id']; // foreign key, check code
		$this->title = $asArray['title'];
		$this->time = dataConnection::db2time($asArray['time']);
		$this->problemstatement = $asArray['problemstatement'];
		$this->thisbundle = $asArray['thisbundle'];
		$this->wayitworks = $asArray['wayitworks'];
		$this->worksbetter = $asArray['worksbetter'];
		$this->doesntwork = $asArray['doesntwork'];
		$this->doesntworkunless = $asArray['doesntworkunless'];
		$this->workedif = $asArray['workedif'];
		$this->variations = $asArray['variations'];
		$this->solutionstatement = $asArray['solutionstatement'];
	}

	static function retrieve_teachingpractice($id)
	{
		$query = "SELECT * FROM teachingpractice WHERE id='".dataConnection::safe($id)."';";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
		{
			return new teachingpractice($result[0]);
		}
		else
			return false;
	}

	static function retrieve_teachingpractice_matching($field, $value, $from=0, $count=-1, $sort=null)
	{
	    if(preg_replace('/\W/','',$field)!== $field)
	        return false; // not a permitted field name;
	    $query = "SELECT * FROM teachingpractice WHERE $field='".dataConnection::safe($value)."'";
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
	            $output[] = new teachingpractice($r);
	        return $output;
	    }
	    else
	        return false;
	}

	function insert()
	{
		//#Any required insert methods for foreign keys need to be called here.
		$query = "INSERT INTO teachingpractice(author_id, title, time, problemstatement, thisbundle, wayitworks, worksbetter, doesntwork, doesntworkunless, workedif, variations, solutionstatement) VALUES(";
		if($this->author_id!==null)
			$query .= "'".dataConnection::safe($this->author_id)."', ";
		else
			$query .= "null, ";
		$query .= "'".dataConnection::safe($this->title)."', ";
		$query .= "'".dataConnection::time2db($this->time)."', ";
		$query .= "'".dataConnection::safe($this->problemstatement)."', ";
		$query .= "'".dataConnection::safe($this->thisbundle)."', ";
		$query .= "'".dataConnection::safe($this->wayitworks)."', ";
		$query .= "'".dataConnection::safe($this->worksbetter)."', ";
		$query .= "'".dataConnection::safe($this->doesntwork)."', ";
		$query .= "'".dataConnection::safe($this->doesntworkunless)."', ";
		$query .= "'".dataConnection::safe($this->workedif)."', ";
		$query .= "'".dataConnection::safe($this->variations)."', ";
		$query .= "'".dataConnection::safe($this->solutionstatement)."');";
		dataConnection::runQuery("BEGIN;");
		$result = dataConnection::runQuery($query);
		$result2 = dataConnection::runQuery("SELECT LAST_INSERT_ID() AS id;");
		dataConnection::runQuery("COMMIT;");
		$this->id = $result2[0]['id'];
		return $this->id;
	}

	function update()
	{
		$query = "UPDATE teachingpractice ";
		$query .= "SET author_id='".dataConnection::safe($this->author_id)."' ";
		$query .= ", title='".dataConnection::safe($this->title)."' ";
		$query .= ", time='".dataConnection::time2db($this->time)."' ";
		$query .= ", problemstatement='".dataConnection::safe($this->problemstatement)."' ";
		$query .= ", thisbundle='".dataConnection::safe($this->thisbundle)."' ";
		$query .= ", wayitworks='".dataConnection::safe($this->wayitworks)."' ";
		$query .= ", worksbetter='".dataConnection::safe($this->worksbetter)."' ";
		$query .= ", doesntwork='".dataConnection::safe($this->doesntwork)."' ";
		$query .= ", doesntworkunless='".dataConnection::safe($this->doesntworkunless)."' ";
		$query .= ", workedif='".dataConnection::safe($this->workedif)."' ";
		$query .= ", variations='".dataConnection::safe($this->variations)."' ";
		$query .= ", solutionstatement='".dataConnection::safe($this->solutionstatement)."' ";
		$query .= "WHERE id='".dataConnection::safe($this->id)."';";
		return dataConnection::runQuery($query);
	}

	static function count($where_name=null, $equals_value=null)
	{
		$query = "SELECT COUNT(*) AS count FROM teachingpractice WHERE ";
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
		$out = "<teachingpractice>\n";
		$out .= '<id>'.htmlentities($this->id)."</id>\n";
		$out .= '<author>'.htmlentities($this->author)."</author>\n";
		$out .= '<title>'.htmlentities($this->title)."</title>\n";
		$out .= '<time>'.htmlentities($this->time)."</time>\n";
		$out .= '<problemstatement>'.htmlentities($this->problemstatement)."</problemstatement>\n";
		$out .= '<thisbundle>'.htmlentities($this->thisbundle)."</thisbundle>\n";
		$out .= '<wayitworks>'.htmlentities($this->wayitworks)."</wayitworks>\n";
		$out .= '<worksbetter>'.htmlentities($this->worksbetter)."</worksbetter>\n";
		$out .= '<doesntwork>'.htmlentities($this->doesntwork)."</doesntwork>\n";
		$out .= '<doesntworkunless>'.htmlentities($this->doesntworkunless)."</doesntworkunless>\n";
		$out .= '<workedif>'.htmlentities($this->workedif)."</workedif>\n";
		$out .= '<variations>'.htmlentities($this->variations)."</variations>\n";
		$out .= '<solutionstatement>'.htmlentities($this->solutionstatement)."</solutionstatement>\n";
		$out .= "</teachingpractice>\n";
		return $out;
	}
	//[[USERCODE_teachingpractice]] Put code for custom class members in this block.

	//[[USERCODE_teachingpractice]] WEnd of custom class members.
}

class user_comments_teachingpractice
{
	var $id; //primary key
	var $user_id; //foreign key
	var $teachingpractice_id; //foreign key
	var $time;
	var $comment;

	function user_comments_teachingpractice($asArray=null)
	{
		$this->id = null; //primary key
		$this->user_id = null; // foreign key, needs dealt with.
		$this->teachingpractice_id = null; // foreign key, needs dealt with.
		$this->time = time();
		$this->comment = "";
		if($asArray!==null)
			$this->fromArray($asArray);
	}

	function fromArray($asArray)
	{
		$this->id = $asArray['id'];
		$this->user_id = $asArray['user_id']; // foreign key, check code
		$this->teachingpractice_id = $asArray['teachingpractice_id']; // foreign key, check code
		$this->time = dataConnection::db2time($asArray['time']);
		$this->comment = $asArray['comment'];
	}

	static function retrieve_user_comments_teachingpractice($id)
	{
		$query = "SELECT * FROM user_comments_teachingpractice WHERE id='".dataConnection::safe($id)."';";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
		{
			return new user_comments_teachingpractice($result[0]);
		}
		else
			return false;
	}

	static function retrieve_user_comments_teachingpractice_matching($field, $value, $from=0, $count=-1, $sort=null)
	{
	    if(preg_replace('/\W/','',$field)!== $field)
	        return false; // not a permitted field name;
	    $query = "SELECT * FROM user_comments_teachingpractice WHERE $field='".dataConnection::safe($value)."'";
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
	            $output[] = new user_comments_teachingpractice($r);
	        return $output;
	    }
	    else
	        return false;
	}

	function insert()
	{
		//#Any required insert methods for foreign keys need to be called here.
		$query = "INSERT INTO user_comments_teachingpractice(user_id, teachingpractice_id, time, comment) VALUES(";
		if($this->user_id!==null)
			$query .= "'".dataConnection::safe($this->user_id)."', ";
		else
			$query .= "null, ";
		if($this->teachingpractice_id!==null)
			$query .= "'".dataConnection::safe($this->teachingpractice_id)."', ";
		else
			$query .= "null, ";
		$query .= "'".dataConnection::time2db($this->time)."', ";
		$query .= "'".dataConnection::safe($this->comment)."');";
		dataConnection::runQuery("BEGIN;");
		$result = dataConnection::runQuery($query);
		$result2 = dataConnection::runQuery("SELECT LAST_INSERT_ID() AS id;");
		dataConnection::runQuery("COMMIT;");
		$this->id = $result2[0]['id'];
		return $this->id;
	}

	function update()
	{
		$query = "UPDATE user_comments_teachingpractice ";
		$query .= "SET user_id='".dataConnection::safe($this->user_id)."' ";
		$query .= ", teachingpractice_id='".dataConnection::safe($this->teachingpractice_id)."' ";
		$query .= ", time='".dataConnection::time2db($this->time)."' ";
		$query .= ", comment='".dataConnection::safe($this->comment)."' ";
		$query .= "WHERE id='".dataConnection::safe($this->id)."';";
		return dataConnection::runQuery($query);
	}

	static function count($where_name=null, $equals_value=null)
	{
		$query = "SELECT COUNT(*) AS count FROM user_comments_teachingpractice WHERE ";
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
		$out = "<user_comments_teachingpractice>\n";
		$out .= '<id>'.htmlentities($this->id)."</id>\n";
		$out .= '<user>'.htmlentities($this->user)."</user>\n";
		$out .= '<teachingpractice>'.htmlentities($this->teachingpractice)."</teachingpractice>\n";
		$out .= '<time>'.htmlentities($this->time)."</time>\n";
		$out .= '<comment>'.htmlentities($this->comment)."</comment>\n";
		$out .= "</user_comments_teachingpractice>\n";
		return $out;
	}
	//[[USERCODE_user_comments_teachingpractice]] Put code for custom class members in this block.

	//[[USERCODE_user_comments_teachingpractice]] WEnd of custom class members.
}

class user_likes_teachingpractice
{
	var $id; //primary key
	var $user_id; //foreign key
	var $teachingpractice_id; //foreign key
	var $time;

	function user_likes_teachingpractice($asArray=null)
	{
		$this->id = null; //primary key
		$this->user_id = null; // foreign key, needs dealt with.
		$this->teachingpractice_id = null; // foreign key, needs dealt with.
		$this->time = time();
		if($asArray!==null)
			$this->fromArray($asArray);
	}

	function fromArray($asArray)
	{
		$this->id = $asArray['id'];
		$this->user_id = $asArray['user_id']; // foreign key, check code
		$this->teachingpractice_id = $asArray['teachingpractice_id']; // foreign key, check code
		$this->time = dataConnection::db2time($asArray['time']);
	}

	static function retrieve_user_likes_teachingpractice($id)
	{
		$query = "SELECT * FROM user_likes_teachingpractice WHERE id='".dataConnection::safe($id)."';";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
		{
			return new user_likes_teachingpractice($result[0]);
		}
		else
			return false;
	}

	static function retrieve_user_likes_teachingpractice_matching($field, $value, $from=0, $count=-1, $sort=null)
	{
	    if(preg_replace('/\W/','',$field)!== $field)
	        return false; // not a permitted field name;
	    $query = "SELECT * FROM user_likes_teachingpractice WHERE $field='".dataConnection::safe($value)."'";
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
	            $output[] = new user_likes_teachingpractice($r);
	        return $output;
	    }
	    else
	        return false;
	}

	function insert()
	{
		//#Any required insert methods for foreign keys need to be called here.
		$query = "INSERT INTO user_likes_teachingpractice(user_id, teachingpractice_id, time) VALUES(";
		if($this->user_id!==null)
			$query .= "'".dataConnection::safe($this->user_id)."', ";
		else
			$query .= "null, ";
		if($this->teachingpractice_id!==null)
			$query .= "'".dataConnection::safe($this->teachingpractice_id)."', ";
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
		$query = "UPDATE user_likes_teachingpractice ";
		$query .= "SET user_id='".dataConnection::safe($this->user_id)."' ";
		$query .= ", teachingpractice_id='".dataConnection::safe($this->teachingpractice_id)."' ";
		$query .= ", time='".dataConnection::time2db($this->time)."' ";
		$query .= "WHERE id='".dataConnection::safe($this->id)."';";
		return dataConnection::runQuery($query);
	}

	static function count($where_name=null, $equals_value=null)
	{
		$query = "SELECT COUNT(*) AS count FROM user_likes_teachingpractice WHERE ";
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
		$out = "<user_likes_teachingpractice>\n";
		$out .= '<id>'.htmlentities($this->id)."</id>\n";
		$out .= '<user>'.htmlentities($this->user)."</user>\n";
		$out .= '<teachingpractice>'.htmlentities($this->teachingpractice)."</teachingpractice>\n";
		$out .= '<time>'.htmlentities($this->time)."</time>\n";
		$out .= "</user_likes_teachingpractice>\n";
		return $out;
	}
	//[[USERCODE_user_likes_teachingpractice]] Put code for custom class members in this block.

	//[[USERCODE_user_likes_teachingpractice]] WEnd of custom class members.
}

class help_documentation
{
	var $id; //primary key
	var $admin_id; //foreign key
	var $time;

	function help_documentation($asArray=null)
	{
		$this->id = null; //primary key
		$this->admin_id = null; // foreign key, needs dealt with.
		$this->time = time();
		if($asArray!==null)
			$this->fromArray($asArray);
	}

	function fromArray($asArray)
	{
		$this->id = $asArray['id'];
		$this->admin_id = $asArray['admin_id']; // foreign key, check code
		$this->time = dataConnection::db2time($asArray['time']);
	}

	static function retrieve_help_documentation($id)
	{
		$query = "SELECT * FROM help_documentation WHERE id='".dataConnection::safe($id)."';";
		$result = dataConnection::runQuery($query);
		if(sizeof($result)!=0)
		{
			return new help_documentation($result[0]);
		}
		else
			return false;
	}

	static function retrieve_help_documentation_matching($field, $value, $from=0, $count=-1, $sort=null)
	{
	    if(preg_replace('/\W/','',$field)!== $field)
	        return false; // not a permitted field name;
	    $query = "SELECT * FROM help_documentation WHERE $field='".dataConnection::safe($value)."'";
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
	            $output[] = new help_documentation($r);
	        return $output;
	    }
	    else
	        return false;
	}

	function insert()
	{
		//#Any required insert methods for foreign keys need to be called here.
		$query = "INSERT INTO help_documentation(admin_id, time) VALUES(";
		if($this->admin_id!==null)
			$query .= "'".dataConnection::safe($this->admin_id)."', ";
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
		$query = "UPDATE help_documentation ";
		$query .= "SET admin_id='".dataConnection::safe($this->admin_id)."' ";
		$query .= ", time='".dataConnection::time2db($this->time)."' ";
		$query .= "WHERE id='".dataConnection::safe($this->id)."';";
		return dataConnection::runQuery($query);
	}

	static function count($where_name=null, $equals_value=null)
	{
		$query = "SELECT COUNT(*) AS count FROM help_documentation WHERE ";
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
		$out = "<help_documentation>\n";
		$out .= '<id>'.htmlentities($this->id)."</id>\n";
		$out .= '<admin>'.htmlentities($this->admin)."</admin>\n";
		$out .= '<time>'.htmlentities($this->time)."</time>\n";
		$out .= "</help_documentation>\n";
		return $out;
	}
	//[[USERCODE_help_documentation]] Put code for custom class members in this block.

	//[[USERCODE_help_documentation]] WEnd of custom class members.
}

?>
