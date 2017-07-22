<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../lib/database.php');
require_once(__DIR__ . '/../lib/sharedfunctions.php');
require_once(__DIR__ . '/../corelib/dataaccess.php');

$uinfo = checkLoggedInUser(false, $error);
if (!$uinfo)
  $tts = array();
else
  $tts = user::get_most_tts(10, isset($_GET['offset']) ? intval($_GET['offset']) : 0);

echo json_encode($tts);
