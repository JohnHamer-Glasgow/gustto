<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');
require_once(__DIR__.'/../lib/formfunctions.php');

$uinfo = checkLoggedInUser();

if ($uinfo == false) exit();
if (!isset($_GET['keyword'])) exit();

$keyword = sanitize_input($_GET['keyword']);
$tts_matching = searchTitlesByKeyword($keyword);
$tts_keywords = searchKeywordsByKeyword($keyword);
$tts_keywords_new = array(); 
foreach (array_diff($tts_keywords, array_intersect($tts_keywords, $tts_matching)) as $tt)
  array_push($tts_keywords_new, teachingtip::retrieve_teachingtip($tt->id));

echo json_encode(array('tts' => $tts_matching,
		       'users' => searchUsersByKeyword($keyword),
		       'keywords' => $tts_keywords_new));
