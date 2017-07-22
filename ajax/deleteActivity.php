<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');
require_once(__DIR__.'/../lib/formfunctions.php');

$uinfo = checkLoggedInUser(false, $error);
if (!$uinfo)
  exit();

$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;

session_start();

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token'] || !isset($_POST['ttId']) || !is_numeric($_POST['ttId']) || $_POST['ttId'] < 0)
  exit();

$ttId = dataConnection::safe(sanitize_input($_POST['ttId']));
$tt = teachingtip::retrieve_teachingtip($ttId);

if (isset($_POST['likeId']) && is_numeric($_POST['likeId']) && $_POST['likeId'] >= 0 && isset($_POST['type']) && $_POST['type']==='1') {
  $likeId = dataConnection::safe($_POST['likeId']);
  dataConnection::runquery("delete from user_likes_tt where id = $likeId");
}

if (isset($_POST['commentId']) && is_numeric($_POST['commentId']) && $_POST['commentId'] >= 0 && isset($_POST['type']) && $_POST['type']==='2') {
  $commentId = dataConnection::safe(sanitize_input($_POST['commentId']));
  dataConnection::runquery("delete from user_comments_tt where id = $commentId");
  dataConnection::runquery("delete from ttcomment where id = $commentId");
}

echo json_encode(array('type' => sanitize_input($_POST['type'])));
