<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');
require_once(__DIR__.'/../lib/formfunctions.php');
require_once(__DIR__.'/../lib/constants.php');

session_start();

$uinfo = checkLoggedInUser();
$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;

if($uinfo==false)
{
	header("Location: ../login.php");
	exit();
}
else {
	$data = array();
	
	// Check a POST is valid.
	if (!(isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token'])) exit();

	// POST is valid

	if (isset($_POST['ttID']) && is_numeric($_POST['ttID']) && $_POST['ttID'] >= 0) {
		$ttID = $_POST['ttID'];
	} else exit ();

	if (isset($_POST['comment']) && strlen(trim($_POST['comment'])) > 0) {
		$comment = sanitize_input($_POST['comment']);
	} else exit();

	$c = new ttcomment();
    $c->comment = $comment;
    $cID = $c->insert();  // comment ID

    // if not success delete the comment
    if (is_null($cID)){
    	$query = "DELETE FROM ttcomment WHERE id=".dataConnection::safe($cID)."";
    	dataConnection::runquery($query);
    	exit();
    }

    $data['c_time'] = date('H:i d M y',$c->time);
    $data['c_id'] = $c->id;
    $data['comment'] = $c->comment;

    // create new user_comments_tt object and insert it into the db
	$uctt = new user_comments_tt();
	$uctt->user_id = $loggedUserID;
	$uctt->teachingtip_id = $ttID;
	$uctt->comment_id = $cID;
	$success = $uctt->insert();

	// NOTIFICATIONS
	// Notification to the author
	$tt =  teachingtip::retrieve_teachingtip($ttID);
	if($loggedUserID != $tt->author_id) createNotification($tt->author_id,$uctt->id,'comment','tts_activity');

	// Notification to the followers
	$followers = getFollowers($loggedUserID);
	$fl = array();            // followers ids
	if($followers){
		foreach ($followers as $follower) {
			if ($follower->id != $tt->author_id)
				createNotification($follower->id,$uctt->id,'comment','followers_activity');	
			$fl[] = $follower->id;
		}
	}

	// Notification to the commenters
	$commenters = getTtCommentUsers ($tt,$loggedUserID);
	if ($commenters){
		foreach ($commenters as $commenter) 
			if (!in_array($commenter->id, $fl))
				createNotification($commenter->id,$uctt->id,'comment','commenters_activity');
	}

	// REPUTATION and AWARD update
	if ($tt->author_id != $loggedUserID) {
		$tt_author = $tt->get_author();
		$tt_author->esteem_comment_tt();
		$loggedUser = user::retrieve_user($loggedUserID);
		$loggedUser->engagement_comment_tt();
		

		// Update author's and user's awards
		$tt_author = $tt->get_author();
		$author_aid = $tt_author->update_awards('comments', 'esteem');
		$author_oaid = $tt_author->update_overall_awards('esteem');

		$loggedUser = user::retrieve_user($loggedUserID);
		$user_aid = $loggedUser->update_awards('comments', 'engagement');
		$user_oaid = $loggedUser->update_overall_awards('engagement');

		// Award notifications
		if ($author_aid) createNotification($tt_author->id, $author_aid, 'award', 'awards');
		if ($author_oaid) createNotification($tt_author->id, $author_oaid, 'award', 'awards');

		if ($user_aid) createNotification($loggedUser->id, $user_aid, 'award', 'awards');
		if ($user_oaid) createNotification($loggedUser->id, $user_oaid, 'award', 'awards');

	}
	

	// if not success delete the comment
	if(!$success){
		$query = "DELETE FROM ttcomment WHERE id=".dataConnection::safe($cID)."";
    	dataConnection::runquery($query);
		$query = "DELETE FROM user_comments_tt WHERE comment_id=".dataConnection::safe($cID)."";
    	dataConnection::runquery($query);

    	// Notification Delete
    	deleteNotification($tt->author_id,$uctt->id,'comment');

    	// Notification to Followers Delete
    	if($followers){
			foreach ($followers as $follower) deleteNotification($follower->id,$uctt->id,'comment');
		}

		// Notification to Commenters Delete
		if ($commenters){
			foreach ($commenters as $commenter) deleteNotification($commenter->id,$uctt->id,'comment');
		}

		exit();
	} 

	// get the comment author
	$author = $c->get_author();
	$data['a_firstname'] = $author->name;
	$data['a_lastname'] = $author->lastname;
	$data['a_profilepic'] = $author->profile_picture;

	
	echo json_encode($data);

	
	
}

?>
