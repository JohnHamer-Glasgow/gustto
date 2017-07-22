<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');
require_once(__DIR__.'/../lib/formfunctions.php');

$uinfo = checkLoggedInUser(false, $error);
if (!$uinfo)
  exit();

if (!isset($_GET['keyword']) || empty($_GET['keyword']))
  exit();

echo json_encode(searchUsersByEmailKeyword(sanitize_input($_GET['keyword'])));
