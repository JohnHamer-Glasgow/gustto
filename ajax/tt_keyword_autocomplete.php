<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');
require_once(__DIR__.'/../lib/formfunctions.php');

if (isset($_GET['keyword']) && !empty($_GET['keyword']))
  echo json_encode(array_values(searchTTKeywordsByKeyword(sanitize_input($_GET['keyword']))));
else
  echo json_encode(array());
