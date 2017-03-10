<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');

if (isset($_GET['userId']) ) {
	$userId = $_GET['userId'];
	
} else exit();

$user = user::retrieve_user($userId);
$data = $user->get_top_teaching_tips(); 
$more_than_three = array_slice($data,3); // as the top 3 are already displayed

echo json_encode($more_than_three);


?>