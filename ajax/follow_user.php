<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');

$uinfo = checkLoggedInUser(false, $error);
if ($uinfo == false)
  exit();

session_start();

if (!(isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']))
  exit();

if (!isset($_POST['userID']) || !is_numeric($_POST['userID']) || $_POST['userID'] < 0)
  exit();

if (!isset($_POST['followed']) || !is_numeric($_POST['followed']) | ($_POST['followed'] != 0 && $_POST['followed'] != 1))
  exit();

$userID = $_POST['userID'];
$followed = $_POST['followed'];

$data = array();

$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;

if ($followed == 0) {
  $ufu = new user_follows_user();
  $ufu->follower_id = $loggedUserID;
  $ufu->user_id = $userID;
  $fid = $ufu->insert();
  if ($fid) {
    $data['followed'] = 'followed';
    
    createNotification($userID, $fid, 'follow', 'follow');
    
    if ($userID != $loggedUserID) {
      $user = user::retrieve_user($userID);
      $user->esteem_follow();
      $follower = user::retrieve_user($loggedUserID);
      $follower->engagement_follow();

      $user = user::retrieve_user($loggedUserID);
      $user_aid = $user->update_awards('follows', 'esteem');
      $user_oaid = $user->update_overall_awards('esteem');
      
      $follower = user::retrieve_user($loggedUserID);
      $follower_aid = $follower->update_awards('follows', 'engagement');
      $follower_oaid = $follower->update_overall_awards('engagement');

      if ($user_aid) createNotification($user->id, $user_aid, 'award', 'awards');
      if ($user_oaid) createNotification($user->id, $user_oaid, 'award', 'awards');

      if ($follower_aid) createNotification($follower->id, $follower_aid, 'award', 'awards');
      if ($follower_oaid) createNotification($follower->id, $follower_oaid, 'award', 'awards');
    }
  }
  else
    $data['followed'] = '0';
} elseif ($followed == 1) {
  if (userUnfollowsUser($loggedUserID, $userID))
    $data['followed'] = 'unfollowed';
  else
    $data['followed'] = '0';
}
	
echo json_encode($data);
