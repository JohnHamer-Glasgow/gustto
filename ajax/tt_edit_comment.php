<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');
require_once(__DIR__.'/../lib/formfunctions.php');

session_start();

$uinfo = checkLoggedInUser();
$dbUser = getUserRecord($uinfo);
$userID = $dbUser->id;

if($uinfo==false)
{
	header("Location: ../login.php");
	exit();
}

// Check a POST is valid.
if (!(isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token'])) exit();

// POST is valid
if (isset($_POST['cID']) && is_numeric($_POST['cID']) && $_POST['cID'] >= 0) {
	$cID = $_POST['cID'];
} else exit();

if (isset($_POST['comment']) && strlen(trim($_POST['comment'])) > 0) {
	$comment = sanitize_input($_POST['comment']);
} else exit();

$data = array();

$uctt = user_comments_tt::retrieve_user_comments_tt_matching('comment_id', $cID)[0];

// extra security check
if ($uctt->user_id != $userID) exit();

$c = ttcomment::retrieve_ttcomment($cID);
$c->comment = $comment;
//TODO update the time maybe? (after we add edited time to ttcomment table)
$success = $c->update();

if ($success) {
	$data['comment'] = $c->comment;
	$data['action'] = 'edited';
}
else $data['action'] = '0';

echo json_encode($data);

?>