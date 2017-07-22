<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');
require_once(__DIR__.'/../lib/constants.php');

$uinfo = checkLoggedInUser(false, $error);

if($uinfo == false)
  exit();

session_start();

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token'])
  exit();

if (!isset($_POST['ttID']) || !is_numeric($_POST['ttID']) || $_POST['ttID'] < 0)
  exit();

$ttID = $_POST['ttID'];

$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;

if (isset($_POST['like']) && is_numeric($_POST['like']) && ($_POST['like'] == 0 || $_POST['like'] == 1)) {
  $like = $_POST['like'];
  $data = array();
  $tt =  teachingtip::retrieve_teachingtip($ttID);
  if ($like == 1) {
    $ultt = userLikesTT($ttID, $loggedUserID);
    if ($ultt) {
      $data['action'] = 'tt-liked';

      $tt = teachingtip::retrieve_teachingtip($ttID);
      if ($loggedUserID != $tt->author_id)
	createNotification($tt->author_id, $ultt->id, 'like', 'tts_activity');

      foreach (getFollowers($loggedUserID) as $follower) {
	if ($follower->id != $tt->author_id) 
	  createNotification($follower->id,$ultt->id,'like','followers_activity');
      }

      // REPUTATION and AWARD update
      if ($tt->author_id != $loggedUserID) {
	$author = $tt->get_author();
	$author->esteem_like_tt();
	$loggedUser = user::retrieve_user($loggedUserID);
	$loggedUser->engagement_like_tt();

	$author = $tt->get_author();
	$author_aid = $author->update_awards('likes', 'esteem');
	$author_oaid = $author->update_overall_awards('esteem');

	$loggedUser = user::retrieve_user($loggedUserID);
	$user_aid = $loggedUser->update_awards('likes', 'engagement');
	$user_oaid = $loggedUser->update_overall_awards('engagement');

	// Award notifications
	if ($author_aid) createNotification($author->id, $author_aid, 'award', 'awards');
	if ($author_oaid) createNotification($author->id, $author_oaid, 'award', 'awards');

	if ($user_aid) createNotification($loggedUser->id, $user_aid, 'award', 'awards');
	if ($user_oaid) createNotification($loggedUser->id, $user_oaid, 'award', 'awards');
      }
    } else
      $data['action'] = '0';
  } else {
    if (userUnlikesTT($ttID, $loggedUserID)) {
      $data['action'] = 'tt-unliked';

      // REPUTATION update
      if ($tt->author_id != $loggedUserID) {
	$author = $tt->get_author();
	$author->esteem_unlike_tt();
	$loggedUser = user::retrieve_user($loggedUserID);
	$loggedUser->engagement_unlike_tt();
      }
    } else
      $data['action'] = '0';
  }

  echo json_encode($data);
}
