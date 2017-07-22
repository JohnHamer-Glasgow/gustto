<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');

$uinfo = checkLoggedInUser(false, $error);
$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;

if($uinfo==false) exit();

if (isset($_GET['notificationId']) && is_numeric($_GET['notificationId']) && $_GET['notificationId'] >= 0 ) {
	$notificationId = dataConnection::safe($_GET['notificationId']);
	
} else exit();


$query ="UPDATE notification SET seen=1  WHERE id=$notificationId";
$result = dataConnection::runquery($query); 

?>