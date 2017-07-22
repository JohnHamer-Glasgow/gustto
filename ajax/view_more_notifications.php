<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');

$uinfo = checkLoggedInUser(false, $error);
if (!$uinfo)
  $response = 'done';
else {
  $dbUser = getUserRecord($uinfo);
  $notifications = notification::getNotifications($dbUser->id, 15, 2, isset($_GET['offset']) ? intval($_GET['offset']) : 0);
  if (empty($notifications))
    $response = 'done';
  else
    $response = array('notifications' => htmlspecialchars(notificationsPrinting($notifications)));
}

echo json_encode($response);
