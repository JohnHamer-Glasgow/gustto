<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');

if (!isset($_GET['userId'])) exit();

$user = user::retrieve_user($_GET['userId']);
$data = $user->get_top_teaching_tips(); 
echo json_encode(array_slice($data, 3)); // The top 3 are already displayed
