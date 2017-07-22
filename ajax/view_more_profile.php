<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../lib/database.php');
require_once(__DIR__ . '/../lib/sharedfunctions.php');
require_once(__DIR__ . '/../corelib/dataaccess.php');

$uinfo = checkLoggedInUser(false, $error);
if ($uinfo == false) exit();

if (!isset($_GET['userId']) || !is_numeric($_GET['userId']))
  exit();

$user = user::retrieve_user(dataConnection::safe($_GET['userId']));
echo json_encode(array_slice($user->get_top_teaching_tips(), 3)); // The top 3 are already displayed
