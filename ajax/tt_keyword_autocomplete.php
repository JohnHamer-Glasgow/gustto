<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');
require_once(__DIR__.'/../lib/formfunctions.php');

$uinfo = checkLoggedInUser();
$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;


if($uinfo==false)
{
	header("Location: ../login.php");
	exit();
}

if (isset($_GET['keyword']) && !empty($_GET['keyword'])) {
	$keyword = sanitize_input($_GET['keyword']);

	
} else exit();

// allow only letters and spaces for keyword
//if (!preg_match('/^[a-zA-Z0-9@\s]*$/i', $keyword)) exit();

$kws_matching = searchTTKeywordsByKeyword($keyword);
if ($kws_matching) $kws_matching = array_values($kws_matching);
echo json_encode($kws_matching);

?>