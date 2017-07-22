<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');
require_once(__DIR__.'/../lib/constants.php');

$uinfo = checkLoggedInUser(false, $error);
if ($uinfo == false)
  exit();

session_start();

if (!(isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token'])) exit();

if (!isset($_POST['cID']) || !is_numeric($_POST['cID']) || $_POST['cID'] < 0)
  exit();

$cID = $_POST['cID'];

$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;

$uctt = user_comments_tt::retrieve_user_comments_tt_matching('comment_id', $cID)[0];

if ($uctt->user_id != $loggedUserID)
  exit();

$data = array();

$tt = teachingtip::retrieve_teachingtip($uctt->teachingtip_id);
if ($tt->author_id != $loggedUserID) {
  $tt_author = $tt->get_author();
  $tt_author->esteem_delete_comment_tt();
  $loggedUser = user::retrieve_user($loggedUserID);
  $loggedUser->engagement_delete_comment_tt();
}

if (deleteComment($cID, $loggedUserID))
  $data['action'] = 'deleted';
else
  $data['action'] = '0';

echo json_encode($data);
