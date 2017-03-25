<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');

$uinfo = checkLoggedInUser();

if ($uinfo == false) exit();

if (isset($_GET['offset']) && is_numeric($_GET['offset']) && $_GET['offset'] % 15 == 0 )
  $offset = dataConnection::safe($_GET['offset']);
else
  exit();

$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;

$data['notifications'] = htmlspecialchars(notificationsPrinting(notification::getNotifications($loggedUserID, 15, 2, $offset)));
echo json_encode($data);
