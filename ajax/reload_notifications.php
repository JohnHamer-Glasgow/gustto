<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');

$uinfo = checkLoggedInUser(false, $error);
if ($uinfo == false) exit();

$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;

$data = array();
$data['notificationNo'] = "(" . sizeof(notification::getNotifications($loggedUserID, false, 0)) . ")";
$data['notifications'] = htmlspecialchars(notifications($dbUser));

echo json_encode($data);


