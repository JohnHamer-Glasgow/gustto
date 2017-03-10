<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');

$uinfo = checkLoggedInUser();
$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;

if($uinfo==false) exit();

$data = array();

// Notification No
if (notification::getNotifications($loggedUserID,false,0) == false) $notificationNo = 0;
else $notificationNo = sizeof(notification::getNotifications($loggedUserID,false,0));

$notificationNo = " (".$notificationNo.") ";


$data['notificationNo'] = $notificationNo;

// Notifications

$notifications = notifications($dbUser);
//encoding html as text to be passed
$notifications = htmlspecialchars($notifications);

$data['notifications'] = $notifications;



echo json_encode($data);

?>


