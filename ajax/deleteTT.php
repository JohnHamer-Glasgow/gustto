<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');
require_once(__DIR__.'/../lib/formfunctions.php');
require_once(__DIR__.'/../lib/constants.php');

$newStatus = 'deleted';
$uinfo = checkLoggedInUser(false, $error);
if ($uinfo) {
  $dbUser = getUserRecord($uinfo);
  $loggedUserID = $dbUser->id;
  session_start();
  if (isset($_POST['csrf_token']) &&
      $_POST['csrf_token'] === $_SESSION['csrf_token'] &&
      isset($_POST['ttId']) &&
      is_numeric($_POST['ttId']) &&
      $_POST['ttId'] >= 0) {
    $ttId = dataConnection::safe(sanitize_input($_POST['ttId']));
    $tt = teachingtip::retrieve_teachingtip($ttId);
    
    if ($tt && ($loggedUserID == $tt->author_id || $dbUser->isadmin == 1)) {
      switch ($tt->status) {
      case 'draft': $newStatus = 'deleted'; break;
      case 'deleted': $newStatus = 'draft'; break;
      case 'active': $newStatus = 'draft';; break;
      default: $newStatus = 'draft'; // "cannot happen"
      }

      dataConnection::runQuery("update teachingtip set status = '$newStatus' where id = $ttId");
    }
  }
}

echo json_encode($newStatus);
