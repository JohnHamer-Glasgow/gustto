<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');
require_once(__DIR__.'/../lib/formfunctions.php');
require_once(__DIR__.'/../lib/constants.php');

$uinfo = checkLoggedInUser();

if ($uinfo == false)
  exit();

$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;

$action = '';

session_start();
if (isset($_POST['csrf_token']) &&
    $_POST['csrf_token'] === $_SESSION['csrf_token'] &&
    isset($_POST['ttId']) &&
    is_numeric($_POST['ttId']) &&
    $_POST['ttId'] >= 0 ) {
  $ttId = dataConnection::safe(sanitize_input($_POST['ttId']));
  $tt = teachingtip::retrieve_teachingtip($ttId);
  
  if ($loggedUserID == $tt->author_id && $tt->archived == '0') {
    if ($tt->draft == '1') {
      $action = 'Deleted';
      dataConnection::runQuery("update teachingtip set archived = '1' where id = $ttId");
    } else {
      $action = 'Unpublished';
      dataConnection::runQuery("update teachingtip set draft = '1' where id = $ttId");
      $loggedUser = user::retrieve_user($loggedUserID);
      foreach (getSameSchoolUsers($loggedUser->school, $loggedUserID) as $userId)
	deleteNotification($userId['id'], $ttId, 'post');

      $userSchool = $loggedUser->school;
      foreach (getFollowers($loggedUserID) as $follower) {
	//avoid deleting twice as the user is deleted from the school loop
	if ($follower->school != $userSchool)
	  deleteNotification($follower->id, $ttId, 'post');
      }
    }
  }
}

echo json_encode($action);
