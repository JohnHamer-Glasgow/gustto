<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../lib/database.php');
require_once(__DIR__ . '/../lib/sharedfunctions.php');
require_once(__DIR__ . '/../corelib/dataaccess.php');

$uinfo = checkLoggedInUser();
if ($uinfo == false) exit();

if (!isset($_GET['offset']) || !is_numeric($_GET['offset']))
  exit();

echo json_encode(user::get_most_tts(10, $_GET['offset']));
