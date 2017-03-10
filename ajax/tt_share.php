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

if (!(isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token'])) exit();

$data = array();

if (isset($_POST['ttID']) && is_numeric($_POST['ttID']) && $_POST['ttID'] >= 0) {
	$ttID = $_POST['ttID'];
} else exit();

if (isset($_POST['recipient']) && filter_var($_POST['recipient'], FILTER_SANITIZE_EMAIL)) {
	$recipientEmail = sanitize_input($_POST['recipient']);
} else exit();

$message = '';
if (isset($_POST['message'])) {
	$message = sanitize_input($_POST['message']);
} 


// update db
$data = array();
$loggedUser = user::retrieve_user($loggedUserID);
$tt =  teachingtip::retrieve_teachingtip($ttID);
$tt_author = $tt->get_author();

// don't allow user to share a TT with themselves
if ($loggedUser->email == $recipientEmail) {
	$data['error'] = 'You cannot share a Teaching Tip with yourself.';
	echo json_encode($data);
	exit();
}

// don't allow user to share a TT with its author
if ($tt_author->email == $recipientEmail) {
	$data['error'] = 'You cannot share a Teaching Tip with its author.';
	echo json_encode($data);
	exit();
}

// $recipient = user::retrieve_user_matching('email', $recipientEmail)[0];

if (!userSharedTT($ttID, $loggedUser->email, $recipientEmail)) {
	$ustt = new user_shares_tt();
	$ustt->sender = $loggedUser->email;
	$ustt->recipient = $recipientEmail;
	$ustt->teachingtip_id = $ttID;
	if ($message != '') $ustt->message = $message;
	$usttID = $ustt->insert();
	
	if ($usttID) {
		 // Notification to the user
		

		if ($loggedUserID != $tt->author_id) createNotification($tt->author_id,$ustt->id,'share','tts_activity');

		// REPUTATION and AWARD update
		if ($tt->author_id != $loggedUserID) {
			$tt_author = $tt->get_author();
			$tt_author->esteem_share_tt();
			$loggedUser = user::retrieve_user($loggedUserID);
			$loggedUser->engagement_share_tt();
			

			// Update author's and user's awards
			$tt_author = $tt->get_author();
			$author_aid = $tt_author->update_awards('shares', 'esteem');
			$author_oaid = $tt_author->update_overall_awards('esteem');

			$loggedUser = user::retrieve_user($loggedUserID);
			$user_aid = $loggedUser->update_awards('shares', 'engagement');
			$user_oaid = $loggedUser->update_overall_awards('engagement');

			// Award notifications
			if ($author_aid) createNotification($tt_author->id, $author_aid, 'award', 'awards');
			if ($author_oaid) createNotification($tt_author->id, $author_oaid, 'award', 'awards');

			if ($user_aid) createNotification($loggedUser->id, $user_aid, 'award', 'awards');
			if ($user_oaid) createNotification($loggedUser->id, $user_oaid, 'award', 'awards');

		}

		// Send email to recipient
		sendEmailShare($usttID);

		$data['success'] = '1';
		
	}
	else $data['success'] = '0';
} else {
	$data['error'] = 'You already shared this Teaching Tip with ' . $recipientEmail;
}



echo json_encode($data);

?>

