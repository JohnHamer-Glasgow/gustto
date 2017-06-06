<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');
require_once(__DIR__.'/../lib/formfunctions.php');

function tt_seen($tt, $k) {
  global $seen;
  return !isset($seen[$tt['id']]);
}

$uinfo = checkLoggedInUser();

if ($uinfo == false) exit();
if (!isset($_GET['keyword'])) exit();

$keyword = sanitize_input($_GET['keyword']);
$tts = searchTitlesByKeyword($keyword);
global $seen;
$seen = array();
foreach ($tts as $tt)
  $seen[$tt['id']] = 1;
$keys = array_values(array_filter(searchKeywordsByKeyword($keyword), 'tt_seen', ARRAY_FILTER_USE_BOTH));
echo json_encode(array('tts' => $tts,
		       'users' => searchUsersByKeyword($keyword),
		       'keywords' => $keys));
