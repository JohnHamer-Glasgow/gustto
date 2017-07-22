<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');
require_once(__DIR__.'/../lib/formfunctions.php');
require_once(__DIR__.'/../lib/constants.php');

$uinfo = checkLoggedInUser(false, $error);
if ($uinfo == false)
  exit();

session_start();

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token'])
  exit();

if (!isset($_POST['ttID']) || !is_numeric($_POST['ttID']) || $_POST['ttID'] < 0)
  exit();

if (!isset($_POST['recipient']) || !filter_var($_POST['recipient'], FILTER_SANITIZE_EMAIL))
  exit();

$recipientEmail = sanitize_input($_POST['recipient']);

$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;

$message = '';
if (isset($_POST['message']))
  $message = sanitize_input($_POST['message']);

$data = array();
$loggedUser = user::retrieve_user($loggedUserID);

$ttID = $_POST['ttID'];
$tt =  teachingtip::retrieve_teachingtip($ttID);
$tt_author = $tt->get_author();

if ($loggedUser->email == $recipientEmail) {
  $data['error'] = 'You cannot share a Teaching Tip with yourself.';
  echo json_encode($data);
  exit();
}

if ($tt_author->email == $recipientEmail) {
  $data['error'] = 'You cannot share a Teaching Tip with its author.';
  echo json_encode($data);
  exit();
}

if (!userSharedTT($ttID, $loggedUser->email, $recipientEmail)) {
  $ustt = new user_shares_tt();
  $ustt->sender = $loggedUser->email;
  $ustt->recipient = $recipientEmail;
  $ustt->teachingtip_id = $ttID;
  if ($message != '')
    $ustt->message = $message;
  $usttID = $ustt->insert();
  
  if ($usttID) {
    if ($loggedUserID != $tt->author_id) createNotification($tt->author_id,$ustt->id,'share','tts_activity');
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
    
    sendEmailShare($usttID);
    
    $data['success'] = '1';    
  } else
    $data['success'] = '0';
} else
  $data['error'] = 'You already shared this Teaching Tip with ' . $recipientEmail;

echo json_encode($data);

