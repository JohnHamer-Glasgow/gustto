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


if($uinfo==false)
{
	header("Location: ../login.php");
	exit();
}

if (isset($_GET['offset']) && is_numeric($_GET['offset']) && $_GET['offset']%15==0 ) {

	$offset = dataConnection::safe($_GET['offset']);
	
} else exit();

$notifications = notification::getNotifications($loggedUserID,15,2,$offset);
$notificationsHtml = notificationsPrinting($notifications);
$data['notifications'] = htmlspecialchars($notificationsHtml);

echo json_encode($data);


?>