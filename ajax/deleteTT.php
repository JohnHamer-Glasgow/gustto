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

if ($uinfo == false) {
  header("Location: ../index.php");
  exit();
}

$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;
session_start();

$data = array();

if (isset($_POST['csrf_token']) &&
    $_POST['csrf_token'] === $_SESSION['csrf_token'] &&
    isset($_POST['ttId']) &&
    is_numeric($_POST['ttId']) &&
    $_POST['ttId'] >= 0 ) {
  $ttId = dataConnection::safe(sanitize_input($_POST['ttId']));
  $tt = teachingtip::retrieve_teachingtip($ttId);
  $author = $tt->get_author();

  if ($loggedUserID != $author->id)
    exit();

  $result = dataConnection::runQuery("update teachingtip set archived = '1' where id = $ttId");
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

$data['ttId'] = $ttId;
echo json_encode($data);
